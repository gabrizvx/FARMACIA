<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Medicine;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SaleController extends Controller
{
    private const FACTOR_IGV = 18 / 118;

    private const VISTA_INDICE = 'management.sales.index';
    private const VISTA_CREAR = 'management.sales.create';
    private const VISTA_DETALLE = 'management.sales.show';

    public function index(): View
    {
        $ventas = Sale::with(['customer', 'user'])
            ->latest('date')
            ->get();

        return view(self::VISTA_INDICE, [
            'sales' => $ventas,
        ]);
    }

    public function create(): View
    {
        return view(self::VISTA_CREAR, [
            'customers' => Customer::query()->orderBy('name')->get(),
            'medicines' => Medicine::query()->where('stock', '>', 0)->orderBy('name')->get(),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(Request $request): RedirectResponse
    {
        $entrada = $this->validarSolicitud($request);

        DB::beginTransaction();

        try {
            $cabeceraVenta = new Sale();
            $cabeceraVenta->fill([
                'customer_id' => $entrada['customer_id'],
                'user_id' => auth()->id(),
                'date' => now(),
                'total' => 0,
                'igv' => 0,
            ])->save();

            $lineasDetalle = $this->construirDetallesYDescontarStock($entrada['items']);
            $cabeceraVenta->details()->createMany($lineasDetalle);

            $totalGeneral = collect($lineasDetalle)->sum('subtotal');

            $cabeceraVenta->fill([
                'total' => $totalGeneral,
                'igv' => round($totalGeneral * self::FACTOR_IGV, 2),
            ])->save();

            DB::commit();

            return redirect()
                ->route('sales.show', $cabeceraVenta)
                ->with('ok', 'Venta registrada');
        } catch (Throwable $excepcion) {
            DB::rollBack();
            throw $excepcion;
        }
    }

    public function show(Sale $sale): View
    {
        $sale->loadMissing(['customer', 'user', 'details.medicine']);

        return view(self::VISTA_DETALLE, [
            'sale' => $sale,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function destroy(Sale $sale): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $sale->details()->each(function ($linea): void {
                Medicine::query()
                    ->where('id', $linea->medicine_id)
                    ->increment('stock', $linea->amount);
            });

            $sale->delete();

            DB::commit();

            return redirect()
                ->route('sales.index')
                ->with('ok', 'Venta anulada y stock devuelto');
        } catch (Throwable $excepcion) {
            DB::rollBack();
            throw $excepcion;
        }
    }

    private function validarSolicitud(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'integer', 'distinct', 'exists:medicines,id'],
            'items.*.amount' => ['required', 'integer', 'min:1'],
        ], [
            'items.required' => 'Debe agregar al menos un medicamento.',
            'items.*.medicine_id.distinct' => 'No repita el mismo medicamento, aumente la cantidad.',
        ]);
    }

    private function construirDetallesYDescontarStock(array $items): array
    {
        $detallesPreparados = [];

        foreach ($items as $item) {
            $producto = Medicine::query()
                ->lockForUpdate()
                ->findOrFail($item['medicine_id']);

            $unidades = (int) $item['amount'];

            if ($producto->stock < $unidades) {
                throw ValidationException::withMessages([
                    'items' => "Stock insuficiente para {$producto->name} (disponible: {$producto->stock}).",
                ]);
            }

            $producto->decrement('stock', $unidades);

            $detallesPreparados[] = [
                'medicine_id' => $producto->id,
                'amount' => $unidades,
                'price_unit' => $producto->price,
                'subtotal' => (float) $producto->price * $unidades,
            ];
        }

        return $detallesPreparados;
    }
}
