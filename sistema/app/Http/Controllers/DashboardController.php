<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Medicine;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $dias = (int) $request->integer('days', 7);
        if (! in_array($dias, [7, 30], true)) {
            $dias = 7;
        }

        $ventas = Sale::query();
        $administrador = $usuario->hasRole('Administrador');

        // Cajeros solo ven sus propias ventas; el químico trabaja con inventario.
        if (! $administrador && $usuario->hasRole('Cajero')) {
            $ventas->where('user_id', $usuario->id);
        } elseif ($usuario->hasRole('Químico farmacéutico')) {
            $ventas->whereRaw('1 = 0');
        }

        $hoy = now()->startOfDay();
        $inicioMes = now()->startOfMonth();
        $inicioMesAnterior = now()->subMonth()->startOfMonth();
        $finMesAnterior = now()->subMonth()->endOfMonth();

        $ventasHoy = (clone $ventas)->whereDate('date', $hoy);
        $ventasMes = (clone $ventas)->whereBetween('date', [$inicioMes, now()]);
        $ventasMesAnterior = (clone $ventas)->whereBetween('date', [$inicioMesAnterior, $finMesAnterior]);

        $totalVentasHoy = (clone $ventasHoy)->sum('total');
        $cantidadVentasHoy = (clone $ventasHoy)->count();
        $ingresosMes = (clone $ventasMes)->sum('total');
        $ingresosMesAnterior = (clone $ventasMesAnterior)->sum('total');

        $comparacionMes = $this->comparacionPorcentaje($ingresosMes, $ingresosMesAnterior);

        $clientes = $administrador ? Customer::count() : (clone $ventas)->distinct('customer_id')->count('customer_id');
        $medicamentos = Medicine::count();
        $stockBajo = Medicine::where('stock', '>', 0)->where('stock', '<=', 10)->count();
        $agotados = Medicine::where('stock', 0)->count();

        $lineLabels = [];
        $lineData = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->startOfDay();
            $lineLabels[] = $fecha->format('d/m');
            $lineData[] = (float) (clone $ventas)->whereDate('date', $fecha)->sum('total');
        }

        $monthLabels = [];
        $monthData = [];
        for ($i = 11; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $monthLabels[] = $fecha->translatedFormat('M Y');
            $monthData[] = (float) (clone $ventas)
                ->whereYear('date', $fecha->year)
                ->whereMonth('date', $fecha->month)
                ->sum('total');
        }

        $masVendidos = DB::table('detail_sales')
            ->join('medicines', 'medicines.id', '=', 'detail_sales.medicine_id')
            ->join('sales', 'sales.id', '=', 'detail_sales.sale_id')
            ->when(! $administrador && $usuario->hasRole('Cajero'), fn ($q) => $q->where('sales.user_id', $usuario->id))
            ->select('medicines.name', DB::raw('SUM(detail_sales.amount) as amount'))
            ->groupBy('medicines.id', 'medicines.name')
            ->orderByDesc('amount')
            ->limit(10)
            ->get();

        $porCategoria = DB::table('detail_sales')
            ->join('medicines', 'medicines.id', '=', 'detail_sales.medicine_id')
            ->join('categories', 'categories.id', '=', 'medicines.category_id')
            ->join('sales', 'sales.id', '=', 'detail_sales.sale_id')
            ->when(! $administrador && $usuario->hasRole('Cajero'), fn ($q) => $q->where('sales.user_id', $usuario->id))
            ->select('categories.name', DB::raw('SUM(detail_sales.subtotal) as total'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get();

        $ultimasVentas = (clone $ventas)
            ->with(['customer', 'user'])
            ->latest('date')
            ->limit(10)
            ->get();

        $alertasStock = Medicine::query()
            ->with('category')
            ->where('stock', '<=', 10)
            ->orderBy('stock')
            ->limit(10)
            ->get();

        return view('principal.dashboard.index', [
            'totalVentasHoy' => $totalVentasHoy,
            'cantidadVentasHoy' => $cantidadVentasHoy,
            'ingresosMes' => $ingresosMes,
            'comparacionMes' => $comparacionMes,
            'clientes' => $clientes,
            'medicamentos' => $medicamentos,
            'stockBajo' => $stockBajo,
            'agotados' => $agotados,
            'dias' => $dias,
            'lineLabels' => $lineLabels,
            'lineData' => $lineData,
            'monthLabels' => $monthLabels,
            'monthData' => $monthData,
            'masVendidos' => $masVendidos,
            'porCategoria' => $porCategoria,
            'ultimasVentas' => $ultimasVentas,
            'alertasStock' => $alertasStock,
        ]);
    }

    private function comparacionPorcentaje(float|int|string $actual, float|int|string $anterior): ?float
    {
        $actual = (float) $actual;
        $anterior = (float) $anterior;

        if ($anterior <= 0) {
            return null;
        }

        return round((($actual - $anterior) / $anterior) * 100, 1);
    }
}
