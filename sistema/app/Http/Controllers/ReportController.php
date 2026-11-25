<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Medicine;
use App\Models\Sale;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
        $libro = new Spreadsheet();
        $ventasHoja = $libro->getActiveSheet();
        $ventasHoja->setTitle('Ventas');

        $this->aplicarCabeceraDocumento($ventasHoja, 'Reporte de ventas');
        $this->escribirFiltros($ventasHoja, $datos['filters'], $datos['generatedAt']);

        $encabezados = ['Código', 'Fecha', 'Cliente', 'Vendedor', 'Subtotal', 'IGV', 'Total', 'Estado'];
        $fila = 8;
        foreach ($encabezados as $columna => $titulo) {
            $ventasHoja->setCellValue([$columna + 1, $fila], $titulo);
        }

        foreach ($datos['sales'] as $sale) {
            $fila++;
            $subtotal = (float) $sale->total - (float) $sale->igv;
            $ventasHoja->setCellValueExplicit('A' . $fila, str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $ventasHoja->setCellValue('B' . $fila, $sale->date->format('d/m/Y H:i'));
            $ventasHoja->setCellValue('C' . $fila, $sale->customer->name);
            $ventasHoja->setCellValue('D' . $fila, $sale->user->name);
            $ventasHoja->setCellValue('E' . $fila, $subtotal);
            $ventasHoja->setCellValue('F' . $fila, (float) $sale->igv);
            $ventasHoja->setCellValue('G' . $fila, (float) $sale->total);
            $ventasHoja->setCellValue('H' . $fila, 'Completada');
        }

        $this->aplicarEstilosTabla($ventasHoja, 8, $fila, 8);
        $ventasHoja->getStyle('E9:G' . $fila)->getNumberFormat()->setFormatCode('"S/" #,##0.00');
        $ventasHoja->freezePane('A9');
        $ventasHoja->setAutoFilter('A8:H' . $fila);

        if ($datos['user']->hasRole('Administrador', 'Químico farmacéutico')) {
            $medicinasHoja = $libro->createSheet();
            $medicinasHoja->setTitle('Medicamentos');
            $this->aplicarCabeceraDocumento($medicinasHoja, 'Reporte de medicamentos');
            $this->escribirFiltros($medicinasHoja, $datos['filters'], $datos['generatedAt']);

            $titulosMedicamentos = ['Medicamento', 'Categoría', 'Precio de venta', 'Stock', 'Estado'];
            $filaMedicamentos = 8;
            foreach ($titulosMedicamentos as $columna => $titulo) {
                $medicinasHoja->setCellValue([$columna + 1, $filaMedicamentos], $titulo);
            }

            foreach ($datos['medicines'] as $medicine) {
                $filaMedicamentos++;
                $estado = $medicine->stock === 0 ? 'Agotado' : ($medicine->stock <= 10 ? 'Stock bajo' : 'Disponible');
                $medicinasHoja->setCellValue('A' . $filaMedicamentos, $medicine->name);
                $medicinasHoja->setCellValue('B' . $filaMedicamentos, $medicine->category->name);
                $medicinasHoja->setCellValue('C' . $filaMedicamentos, (float) $medicine->price);
                $medicinasHoja->setCellValue('D' . $filaMedicamentos, (int) $medicine->stock);
                $medicinasHoja->setCellValue('E' . $filaMedicamentos, $estado);
            }

            $this->aplicarEstilosTabla($medicinasHoja, 8, $filaMedicamentos, 5);
            $medicinasHoja->getStyle('C9:C' . $filaMedicamentos)->getNumberFormat()->setFormatCode('"S/" #,##0.00');
            $medicinasHoja->freezePane('A9');
            $medicinasHoja->setAutoFilter('A8:E' . $filaMedicamentos);
        }

        $ruta = storage_path('app/temp-reporte-' . now()->format('YmdHis') . '.xlsx');
        (new Xlsx($libro))->save($ruta);
        $libro->disconnectWorksheets();

        return response()->download($ruta, 'reporte-farmacia-' . now()->format('Y-m-d-His') . '.xlsx')->deleteFileAfterSend(true);
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

    private function aplicarCabeceraDocumento($hoja, string $titulo): void
    {
        $hoja->mergeCells('A1:H1');
        $hoja->setCellValue('A1', 'FARM SJB');
        $hoja->mergeCells('A2:H2');
        $hoja->setCellValue('A2', 'Sistema de Venta de Medicamentos');
        $hoja->mergeCells('A3:H3');
        $hoja->setCellValue('A3', $titulo);

        $hoja->getStyle('A1:H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FFFFFFFF');
        $hoja->getStyle('A1:A3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF111827');
        $hoja->getStyle('A2:A3')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
    }

    private function escribirFiltros($hoja, array $filters, $generado): void
    {
        $hoja->setCellValue('A5', 'Fecha de generación');
        $hoja->setCellValue('B5', $generado->format('d/m/Y H:i'));
        $hoja->setCellValue('D5', 'Periodo');
        $hoja->setCellValue('E5', $filters['period']);
        $hoja->setCellValue('G5', 'Cliente');
        $hoja->setCellValue('H5', $filters['customer_name'] ?? 'Todos');
        $hoja->getStyle('A5:H5')->getFont()->setBold(true);
    }

    private function aplicarEstilosTabla($hoja, int $filaEncabezado, int $ultimaFila, int $ultimaColumna): void
    {
        $rango = 'A' . $filaEncabezado . ':' . chr(64 + $ultimaColumna) . $ultimaFila;
        $hoja->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD1D5DB');

        $hoja->getStyle('A' . $filaEncabezado . ':' . chr(64 + $ultimaColumna) . $filaEncabezado)
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0A41');
        $hoja->getStyle('A' . $filaEncabezado . ':' . chr(64 + $ultimaColumna) . $filaEncabezado)
            ->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $hoja->getStyle('A' . $filaEncabezado . ':' . chr(64 + $ultimaColumna) . $filaEncabezado)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        for ($columna = 1; $columna <= $ultimaColumna; $columna++) {
            $letra = chr(64 + $columna);
            $hoja->getColumnDimension($letra)->setAutoSize(true);
        }
    }
}
