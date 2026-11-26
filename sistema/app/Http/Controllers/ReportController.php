<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\ReportsExport;
use App\Models\Customer;
use App\Models\Medicine;
use App\Models\Sale;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $datos = $this->construirReporte($request);

        return view('principal.reports.index', $datos);
    }

    public function exportXlsx(Request $request): BinaryFileResponse
    {
        $datos = $this->construirReporte($request);
        $nombreArchivo = 'reporte-farmacia-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new ReportsExport($datos), $nombreArchivo);
    }

    public function exportPdf(Request $request): Response
    {
        $datos = $this->construirReporte($request);

        $pdf = Pdf::loadView('principal.reports.pdf', $datos)
            ->setPaper('a4', 'landscape');

        return $pdf->download('reporte-farmacia-' . now()->format('Y-m-d-His') . '.pdf');
    }

    private function construirReporte(Request $request): array
    {
        $datos = $request->validate([
            'period' => ['nullable', 'in:day,week,month,custom'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'seller_id' => ['nullable', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ]);

        $usuario = $request->user();
        $periodo = $datos['period'] ?? 'month';
        $inicio = ! empty($datos['start_date']) ? now()->parse($datos['start_date'])->startOfDay() : null;
        $fin = ! empty($datos['end_date']) ? now()->parse($datos['end_date'])->endOfDay() : null;

        if ($inicio && $fin && $inicio->gt($fin)) {
            [$inicio, $fin] = [$fin->startOfDay(), $inicio->endOfDay()];
        }

        $query = Sale::query()->with(['customer', 'user']);

        if ($periodo === 'custom' && $inicio && $fin) {
            $query->whereBetween('date', [$inicio, $fin]);
        } elseif ($periodo === 'day') {
            $query->whereDate('date', now()->toDateString());
        } elseif ($periodo === 'week') {
            $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
        } else {
            $query->whereMonth('date', now()->month)->whereYear('date', now()->year);
        }

        $sellerId = (int) ($datos['seller_id'] ?? 0);
        $customerId = (int) ($datos['customer_id'] ?? 0);

        if ($sellerId > 0 && $usuario->hasRole('Administrador')) {
            $query->where('user_id', $sellerId);
        }
        if ($customerId > 0) {
            $query->where('customer_id', $customerId);
        }

        $ventas = $query->latest('date')->get();
        $total = (float) $ventas->sum('total');
        $igv = (float) $ventas->sum('igv');
        $operaciones = $ventas->count();

        $medicamentos = $usuario->hasRole('Administrador', 'Químico farmacéutico')
            ? Medicine::query()->with('category')->orderBy('name')->get()
            : collect();

        $vendedores = $usuario->hasRole('Administrador')
            ? User::query()->orderBy('name')->get()->map(function (User $vendedor) use ($ventas) {
                $filtradas = $ventas->where('user_id', $vendedor->id);
                $totalVendido = (float) $filtradas->sum('total');
                $cantidad = $filtradas->count();

                return (object) [
                    'name' => $vendedor->name,
                    'count' => $cantidad,
                    'total' => $totalVendido,
                    'average' => $cantidad > 0 ? $totalVendido / $cantidad : 0,
                ];
            })
            : collect();

        $selectedSeller = $sellerId > 0 ? User::find($sellerId)?->name : null;
        $selectedCustomer = $customerId > 0 ? Customer::find($customerId)?->name : null;

        return [
            'sales' => $ventas,
            'medicines' => $medicamentos,
            'sellers' => $vendedores,
            'customers' => Customer::query()->orderBy('name')->get(),
            'users' => $usuario->hasRole('Administrador') ? User::query()->orderBy('name')->get() : collect(),
            'user' => $usuario,
            'summary' => [
                'total' => $total,
                'operations' => $operaciones,
                'igv' => $igv,
                'subtotal' => $total - $igv,
                'average' => $operaciones > 0 ? $total / $operaciones : 0,
            ],
            'filters' => [
                'period' => $periodo,
                'start_date' => $inicio?->format('Y-m-d'),
                'end_date' => $fin?->format('Y-m-d'),
                'seller_id' => $sellerId,
                'customer_id' => $customerId,
                'seller_name' => $selectedSeller,
                'customer_name' => $selectedCustomer,
            ],
            'generatedAt' => now(),
        ];
    }

}
