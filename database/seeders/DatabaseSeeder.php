<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Aseguramos el orden correcto de dependencias
        $this->call([
            CompanySeeder::class,
            WarehouseSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            AttributeSeeder::class,
            UnitOfMeasureSeeder::class,
            TaxSeeder::class,
            PartnerSeeder::class,
            ProductSeeder::class,
            MembershipPlanSeeder::class,
            JournalSeeder::class,
            PaymentMethodSeeder::class,
            PosConfigSeeder::class,
        ]);
    }
}
