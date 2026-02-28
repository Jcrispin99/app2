<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // ========================================
        // CUSTOMER DEFAULT (Cliente Varios)
        // ========================================

        Partner::create([
            'is_customer' => true,
            'document_type' => 'DNI',
            'document_number' => '00000000',
            'name' => 'Varios',
            'email' => 'varios@krakengym.com',
            'status' => 'active',
        ]);
    }
}
