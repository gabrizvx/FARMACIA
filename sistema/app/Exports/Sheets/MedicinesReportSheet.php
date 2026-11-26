<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Models\Medicine;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MedicinesReportSheet extends ReportSheet implements FromArray, WithColumnFormatting, WithTitle
{
    /**
     * @param  Collection<int, Medicine>  $medicines
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly Collection $medicines,
        private readonly array $filters,
        private readonly CarbonInterface $generatedAt,
        private readonly User $user,
    ) {}

    public function array(): array
    {
        $rows = $this->reportHeaderRows(
            'Reporte de medicamentos',
            $this->filters,
            $this->generatedAt,
            $this->user->hasRole('Administrador'),
        );
        $rows[] = ['Medicamento', 'Categoría', 'Precio de venta', 'Stock', 'Estado'];

        foreach ($this->medicines as $medicine) {
            $rows[] = [
                $medicine->name,
                $medicine->category->name,
                (float) $medicine->price,
                (int) $medicine->stock,
                $medicine->stock === 0 ? 'Agotado' : ($medicine->stock <= 10 ? 'Stock bajo' : 'Disponible'),
            ];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'C' => '"S/" #,##0.00',
            'D' => '#,##0',
        ];
    }

    public function title(): string
    {
        return 'Medicamentos';
    }

    public function styles(Worksheet $sheet): ?array
    {
        return $this->applyReportStyles($sheet, 'E', 8 + $this->medicines->count());
    }
}
