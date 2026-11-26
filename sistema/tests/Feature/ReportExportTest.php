<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Medicine;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_download_uses_report_filters_and_keeps_both_report_sheets(): void
    {
        $administratorRole = Role::create(['name' => 'Administrador']);
        $administrator = User::factory()->create(['role_id' => $administratorRole->id]);
        $firstCustomer = Customer::create(['name' => 'Cliente filtrado', 'dni' => '11111111']);
        $secondCustomer = Customer::create(['name' => 'Cliente excluido', 'dni' => '22222222']);
        $this->createSale($administrator, $firstCustomer);
        $this->createSale($administrator, $secondCustomer);
        $category = Category::create(['name' => 'Analgésicos']);
        Medicine::create([
            'category_id' => $category->id,
            'name' => 'Ibuprofeno',
            'price' => 12.50,
            'stock' => 7,
        ]);

        $startDate = now()->startOfMonth()->format('Y-m-d');
        $endDate = now()->endOfMonth()->format('Y-m-d');
        $response = $this->actingAs($administrator)
            ->get(route('reports.export.xlsx', [
                'period' => 'custom',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'seller_id' => $administrator->id,
                'customer_id' => $firstCustomer->id,
            ]));

        $response->assertOk()->assertDownload();
        $filePath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $spreadsheet = IOFactory::load($filePath);
        try {
            $this->assertSame(['Ventas', 'Medicamentos'], $spreadsheet->getSheetNames());

            $salesSheet = $spreadsheet->getSheetByName('Ventas');
            $this->assertSame('Cliente filtrado', $salesSheet->getCell('C9')->getValue());
            $this->assertSame(9, $salesSheet->getHighestDataRow());
            $this->assertSame('000001', $salesSheet->getCell('A9')->getFormattedValue());
            $this->assertSame('Cliente filtrado', $salesSheet->getCell('H5')->getValue());
            $this->assertSame('custom', $salesSheet->getCell('E5')->getValue());
            $this->assertSame(now()->startOfMonth()->format('d/m/Y'), $salesSheet->getCell('B6')->getValue());
            $this->assertSame(now()->endOfMonth()->format('d/m/Y'), $salesSheet->getCell('E6')->getValue());
            $this->assertSame($administrator->name, $salesSheet->getCell('H6')->getValue());
            $this->assertSame('dd/mm/yyyy hh:mm', $salesSheet->getStyle('B9')->getNumberFormat()->getFormatCode());
            $this->assertSame('"S/" #,##0.00', $salesSheet->getStyle('E9')->getNumberFormat()->getFormatCode());

            $medicinesSheet = $spreadsheet->getSheetByName('Medicamentos');
            $this->assertSame('Ibuprofeno', $medicinesSheet->getCell('A9')->getValue());
            $this->assertSame('Stock bajo', $medicinesSheet->getCell('E9')->getValue());
            $this->assertSame('"S/" #,##0.00', $medicinesSheet->getStyle('C9')->getNumberFormat()->getFormatCode());
            $this->assertSame('#,##0', $medicinesSheet->getStyle('D9')->getNumberFormat()->getFormatCode());
        } finally {
            $spreadsheet->disconnectWorksheets();
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function test_excel_download_remains_restricted_to_report_roles(): void
    {
        $cashierRole = Role::create(['name' => 'Cajero']);
        $cashier = User::factory()->create(['role_id' => $cashierRole->id]);

        $this->actingAs($cashier)
            ->get(route('reports.export.xlsx'))
            ->assertForbidden();
    }

    private function createSale(User $seller, Customer $customer): void
    {
        Sale::create([
            'user_id' => $seller->id,
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 115,
            'igv' => 15,
        ]);
    }
}
