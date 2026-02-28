<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear casa matriz
        $mainOffice = Company::create([
            'business_name' => 'KRAKEN GYM S.A.C.',
            'trade_name' => 'BLACK Gym',
            'ruc' => '20123456789',
            'address' => 'Av. Principal 123',
            'phone' => '987654321',
            'email' => 'contacto@krakengym.com',
            'ubigeo' => '150101',
            'active' => true,
            'parent_id' => null,
            'branch_code' => null,
            'is_main' => true,
        ]);
    }
}
