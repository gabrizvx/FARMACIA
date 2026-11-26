<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class ReportSheet implements ShouldAutoSize, WithStyles
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, mixed>>
     */
    protected function reportHeaderRows(
        string $title,
        array $filters,
        CarbonInterface $generatedAt,
        bool $canFilterBySeller,
    ): array {
        return [
            ['FARM SJB'],
            ['Sistema de Venta de Medicamentos'],
            [$title],
            [' '],
            [
                'Fecha de generación',
                Date::dateTimeToExcel($generatedAt),
                '',
                'Periodo',
                $filters['period'],
                '',
                'Cliente',
                $filters['customer_name'] ?? 'Todos',
            ],
            [
                'Desde',
                $this->formatFilterDate($filters['start_date'] ?? null),
                '',
                'Hasta',
                $this->formatFilterDate($filters['end_date'] ?? null),
                '',
                'Vendedor',
                $canFilterBySeller ? ($filters['seller_name'] ?? 'Todos') : 'No aplica',
            ],
            [' '],
        ];
    }

    protected function applyReportStyles(Worksheet $sheet, string $lastColumn, int $lastRow): array
    {
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->mergeCells("A3:{$lastColumn}3");

        $sheet->getStyle("A1:{$lastColumn}3")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:{$lastColumn}3")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF111827');
        $sheet->getStyle('A1')
            ->getFont()
            ->setBold(true)
            ->setSize(16)
            ->getColor()
            ->setARGB('FFFFFFFF');
        $sheet->getStyle("A2:{$lastColumn}3")
            ->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFFFF');
        $sheet->getStyle("A5:{$lastColumn}6")->getFont()->setBold(true);

        $headerRange = "A8:{$lastColumn}8";
        $tableRange = "A8:{$lastColumn}{$lastRow}";
        $sheet->getStyle($tableRange)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setARGB('FFD1D5DB');
        $sheet->getStyle($headerRange)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFFF0A41');
        $sheet->getStyle($headerRange)
            ->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFFFF');
        $sheet->getStyle($headerRange)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setAutoFilter($tableRange);
        $sheet->freezePane('A9');

        return [];
    }

    private function formatFilterDate(?string $date): string
    {
        return $date === null ? 'No aplica' : Carbon::parse($date)->format('d/m/Y');
    }
}
