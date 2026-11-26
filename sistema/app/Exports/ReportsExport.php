<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Sheets\MedicinesReportSheet;
use App\Exports\Sheets\SalesReportSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportsExport implements Export, WithMultipleSheets
{
    /**
     * @param  array<string, mixed>  $report
     */
    public function __construct(private readonly array $report) {}

    public function sheets(): array
    {
        $sheets = [
            new SalesReportSheet(
                $this->report['sales'],
                $this->report['filters'],
                $this->report['generatedAt'],
                $this->report['user'],
            ),
        ];

        if ($this->report['user']->hasRole('Administrador', 'Químico farmacéutico')) {
            $sheets[] = new MedicinesReportSheet(
                $this->report['medicines'],
                $this->report['filters'],
                $this->report['generatedAt'],
                $this->report['user'],
            );
        }

        return $sheets;
    }
}
