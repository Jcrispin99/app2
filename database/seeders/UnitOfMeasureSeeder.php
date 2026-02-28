<?php

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitOfMeasureSeeder extends Seeder
{
    public function run(): void
    {
        $unit = UnitOfMeasure::firstOrCreate(
            ['family' => 'Unidades', 'name' => 'Unidad'],
            [
                'symbol' => 'un',
                'base_unit_id' => null,
                'factor' => 1,
                'is_active' => true,
            ]
        );

        UnitOfMeasure::firstOrCreate(
            ['family' => 'Unidades', 'name' => 'Docena'],
            [
                'symbol' => 'dz',
                'base_unit_id' => $unit->id,
                'factor' => 12,
                'is_active' => true,
            ]
        );

        UnitOfMeasure::firstOrCreate(
            ['family' => 'Unidades', 'name' => 'Decena'],
            [
                'symbol' => 'dec',
                'base_unit_id' => $unit->id,
                'factor' => 10,
                'is_active' => true,
            ]
        );

        $kg = UnitOfMeasure::firstOrCreate(
            ['family' => 'Peso', 'name' => 'Kilogramo'],
            [
                'symbol' => 'kg',
                'base_unit_id' => null,
                'factor' => 1,
                'is_active' => true,
            ]
        );

        UnitOfMeasure::firstOrCreate(
            ['family' => 'Peso', 'name' => 'Gramo'],
            [
                'symbol' => 'g',
                'base_unit_id' => $kg->id,
                'factor' => 0.001,
                'is_active' => true,
            ]
        );
    }
}

