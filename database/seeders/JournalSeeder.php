<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Journal;
use App\Models\Sequence;
use Illuminate\Database\Seeder;

final class JournalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainCompany = Company::whereNull('parent_id')->first() ?? Company::first();

        $journals = [
            ['name' => 'NOTA DE VENTA',          'type' => 'sale',           'code' => 'NV',   'document_type_code' => null, 'is_fiscal' => false],
            ['name' => 'FACTURA DE VENTA',       'type' => 'sale',           'code' => 'F004', 'document_type_code' => '01', 'is_fiscal' => true],
            ['name' => 'BOLETA DE VENTA',        'type' => 'sale',           'code' => 'B004', 'document_type_code' => '03', 'is_fiscal' => true],
            ['name' => 'FACTURA DE VENTA F001',  'type' => 'sale',           'code' => 'F001', 'document_type_code' => '01', 'is_fiscal' => true],
            ['name' => 'BOLETA DE VENTA B001',   'type' => 'sale',           'code' => 'B001', 'document_type_code' => '03', 'is_fiscal' => true],
            ['name' => 'Cotizaciones',           'type' => 'quote',          'code' => 'COT',  'document_type_code' => null, 'is_fiscal' => false],
            ['name' => 'Nota de Crédito Factura', 'type' => 'sale',          'code' => 'FC04', 'document_type_code' => '07', 'is_fiscal' => true],
            ['name' => 'Nota de Crédito Boleta', 'type' => 'sale',           'code' => 'BC04', 'document_type_code' => '07', 'is_fiscal' => true],
            ['name' => 'Nota de Débito Factura', 'type' => 'sale',           'code' => 'FD04', 'document_type_code' => '08', 'is_fiscal' => true],
            ['name' => 'Nota de Débito Boleta',  'type' => 'sale',           'code' => 'BD04', 'document_type_code' => '08', 'is_fiscal' => true],
            ['name' => 'Órdenes de Compra',      'type' => 'purchase-order', 'code' => 'OC',   'document_type_code' => null, 'is_fiscal' => false],
            ['name' => 'Compras',                'type' => 'purchase',       'code' => 'COMP', 'document_type_code' => null, 'is_fiscal' => false],
            ['name' => 'Cuadre de Caja',         'type' => 'cash',           'code' => 'CAJA', 'document_type_code' => null, 'is_fiscal' => false],
        ];

        foreach ($journals as $journalData) {
            // Crear sequence para cada journal
            $sequence = Sequence::create([
                'sequence_size' => 8,
                'step' => 1,
                'next_number' => 1,
            ]);

            Journal::updateOrCreate(
                ['code' => $journalData['code'], 'company_id' => $mainCompany->id],
                [
                    'name' => $journalData['name'],
                    'type' => $journalData['type'],
                    'document_type_code' => $journalData['document_type_code'],
                    'is_fiscal' => $journalData['is_fiscal'] ?? false,
                    'sequence_id' => $sequence->id,
                    'company_id' => $mainCompany->id,
                ]
            );
        }

        $this->command->info('✅ Se crearon '.count($journals).' diarios/journals.');
    }
}
