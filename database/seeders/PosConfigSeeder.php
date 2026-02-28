<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\PosConfig;
use App\Models\Tax;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class PosConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener registros necesarios
        $company = Company::first();
        $warehouse = Warehouse::first();
        $defaultCustomer = Partner::customers()->first();
        $tax = Tax::where('rate_percent', 18)->first(); // IGV 18%

        if (!$company || !$warehouse) {
            $this->command->error('❌ No hay Companies o Warehouses. Ejecuta sus seeders primero.');
            return;
        }

        // --- POS 1: Principal (F004/B004) ---
        $posConfig1 = PosConfig::create([
            'company_id' => $company->id,
            'name' => 'POS Principal',
            'warehouse_id' => $warehouse->id,
            'default_customer_id' => $defaultCustomer?->id,
            'tax_id' => $tax?->id,
            'apply_tax' => false, // Modificado: sin IGV
            'prices_include_tax' => false,
            'is_active' => true,
        ]);

        // Obtener journals por código para evitar ambigüedades
        $invoiceJournal1 = Journal::where('code', 'F004')->first();
        $receiptJournal1 = Journal::where('code', 'B004')->first();

        if ($invoiceJournal1) {
            $posConfig1->journals()->attach($invoiceJournal1->id, [
                'document_type' => 'invoice',
                'is_default' => true,
            ]);
        }

        if ($receiptJournal1) {
            $posConfig1->journals()->attach($receiptJournal1->id, [
                'document_type' => 'receipt',
                'is_default' => true,
            ]);
        }

        $this->command->info('✅ POS Config creado: ' . $posConfig1->name);
        $this->command->info('   - Journals asociados: ' . $posConfig1->journals->count());

        // --- POS 2: Secundario (F001/B001) ---
        $posConfig2 = PosConfig::create([
            'company_id' => $company->id,
            'name' => 'POS Secundario',
            'warehouse_id' => $warehouse->id,
            'default_customer_id' => $defaultCustomer?->id,
            'tax_id' => $tax?->id,
            'apply_tax' => false, // Sin IGV
            'prices_include_tax' => false,
            'is_active' => true,
        ]);

        $invoiceJournal2 = Journal::where('code', 'F001')->first();
        $receiptJournal2 = Journal::where('code', 'B001')->first();

        if ($invoiceJournal2) {
            $posConfig2->journals()->attach($invoiceJournal2->id, [
                'document_type' => 'invoice',
                'is_default' => true,
            ]);
        }

        if ($receiptJournal2) {
            $posConfig2->journals()->attach($receiptJournal2->id, [
                'document_type' => 'receipt',
                'is_default' => true,
            ]);
        }

        $this->command->info('✅ POS Config creado: ' . $posConfig2->name);
        $this->command->info('   - Journals asociados: ' . $posConfig2->journals->count());
    }
}
