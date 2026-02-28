<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            ['name' => 'Efectivo', 'is_active' => true],
            ['name' => 'Tarjeta', 'is_active' => true],
            ['name' => 'Yape', 'is_active' => true],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->firstOrCreate(
                ['name' => $method['name']],
                ['is_active' => $method['is_active']]
            );
        }
    }
}
