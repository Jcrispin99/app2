<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $mainOffice = Company::where('is_main', true)->first();
        $branches = Company::where('is_main', false)->get();

        // Usuario 1: Admin - Acceso a TODAS las compañías
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
        ]);
        // Asignar todas las compañías
        $allCompanyIds = Company::pluck('id')->toArray();
        // $admin->companies()->sync($allCompanyIds);

        // Usuario 2: Manager - Solo casa matriz y primera sucursal
        if ($branches->count() > 0) {
            $manager = User::create([
                'name' => 'Manager User',
                'email' => 'manager@gmail.com',
                'password' => Hash::make('password'),
            ]);
            // $manager->companies()->sync([
            //     $mainOffice->id,
            //     $branches->first()->id,
            // ]);
        }

        // Usuario 3: Branch User - Solo una sucursal
        if ($branches->count() > 0) {
            $branchUser = User::create([
                'name' => 'Branch User',
                'email' => 'branch@gmail.com',
                'password' => Hash::make('password'),
            ]);
            // $branchUser->companies()->sync([
            //     $branches->first()->id,
            // ]);
        }
    }
}
