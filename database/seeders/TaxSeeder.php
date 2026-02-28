<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tax;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            [
                'name' => '18% IGV',
                'description' => 'Gravado - Operación Onerosa',
                'invoice_label' => 'IGV 18%',
                'tax_type' => 'IGV',
                'affectation_type_code' => '10',
                'rate_percent' => 18.00,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => '0% Exo',
                'description' => 'Exonerado',
                'invoice_label' => 'EXO 0%',
                'tax_type' => 'IGV',
                'affectation_type_code' => '20',
                'rate_percent' => 0.00,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => '0% Ina',
                'description' => 'Inafecto',
                'invoice_label' => 'INA 0%',
                'tax_type' => 'IGV',
                'affectation_type_code' => '31',
                'rate_percent' => 0.00,
                'is_active' => false,
                'is_default' => false,
            ],
            [
                'name' => '0% GRA',
                'description' => 'Gravado 0% (sin IGV)',
                'invoice_label' => 'GRA 0%',
                'tax_type' => 'IGV',
                'affectation_type_code' => '10',
                'rate_percent' => 0.00,
                'is_active' => false,
                'is_default' => false,
            ],
            [
                'name' => '0% ISC',
                'description' => 'Impuesto Selectivo al Consumo 0%',
                'invoice_label' => 'ISC 0%',
                'tax_type' => 'ISC',
                'affectation_type_code' => null,
                'rate_percent' => 0.00,
                'is_active' => false,
                'is_default' => false,
            ],
            [
                'name' => '0% EXP',
                'description' => 'Exportación',
                'invoice_label' => 'EXP 0%',
                'tax_type' => 'IGV',
                'affectation_type_code' => '40',
                'rate_percent' => 0.00,
                'is_active' => false,
                'is_default' => false,
            ],
            [
                'name' => '18% Free Final',
                'description' => 'Gratuita/Bonificación',
                'invoice_label' => 'IGV 18% Gratuito',
                'tax_type' => 'IGV',
                'affectation_type_code' => '11',
                'rate_percent' => 18.00,
                'is_active' => false,
                'is_default' => false,
            ],
            [
                'name' => 'Retención 3%',
                'description' => 'Retención a proveedores (SUNAT)',
                'invoice_label' => 'RET 3%',
                'tax_type' => 'RETENCION',
                'affectation_type_code' => null,
                'rate_percent' => 3.00,
                'is_active' => false,
                'is_default' => false,
            ],
            [
                'name' => '18% TTC',
                'description' => 'IGV incluido en el precio',
                'invoice_label' => 'IGV 18% TTC',
                'tax_type' => 'IGV',
                'affectation_type_code' => '10',
                'rate_percent' => 18.00,
                'is_price_inclusive' => true,
                'is_active' => false,
                'is_default' => false,
            ],
        ];

        foreach ($taxes as $data) {
            Tax::updateOrCreate(
                ['name' => $data['name']],
                $data
            );
        }

        $this->command->info('✅ Se crearon ' . count($taxes) . ' tipos de impuestos.');
    }
}
