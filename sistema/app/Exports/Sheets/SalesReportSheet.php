<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Models\Sale;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesReportSheet extends ReportSheet implements FromArray, WithColumnFormatting, WithTitle
{
    /**
     * @param  Collection<int, Sale>  $sales
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly Collection $sales,
        private readonly array $filters,
        private readonly CarbonInterface $generatedAt,
        private readonly User $user,
    ) {}

    public function array(): array
    {
        $rows = $this->reportHeaderRows(
            'Reporte de ventas',
            $this->filters,
            $this->generatedAt,
            $this->user->hasRole('Administrador'),
        );
        $rows[] = ['Código', 'Fecha', 'Cliente', 'Vendedor', 'Subtotal', 'IGV', 'Total', 'Estado'];

        foreach ($this->sales as $sale) {
            $rows[] = [
                (int) $sale->id,
                Date::dateTimeToExcel($sale->date),
                $sale->customer->name,
                $sale->user->name,
                (float) $sale->total - (float) $sale->igv,
                (float) $sale->igv,
                (float) $sale->total,
                'Completada',
            ];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'A' => '000000',
            'B' => 'dd/mm/yyyy hh:mm',
            'E' => '"S/" #,##0.00',
            'F' => '"S/" #,##0.00',
            'G' => '"S/" #,##0.00',
        ];
    }

    public function title(): string
    {
        return 'Ventas';
    }

    public function styles(Worksheet $sheet): ?array
    {
        return $this->applyReportStyles($sheet, 'H', 8 + $this->sales->count());
    }
}
