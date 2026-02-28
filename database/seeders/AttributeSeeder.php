<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attributesWithValues = [
            'Color' => ['Rojo', 'Verde', 'Azul', 'Negro', 'Blanco', 'Amarillo', 'Gris', 'Naranja', 'Morado'],
            'Talla' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            'Material' => ['Algodón', 'Poliéster', 'Seda', 'Lana', 'Cuero', 'Lino', 'Denim'],
            'Estilo' => ['Casual', 'Formal', 'Deportivo', 'Vintage', 'Bohemio', 'Urbano'],
            'Capacidad' => ['16 GB', '32 GB', '64 GB', '128 GB', '256 GB', '512 GB', '1 TB'],
            'Voltaje' => ['110V', '220V'],
            'Acabado' => ['Mate', 'Brillante', 'Satinado', 'Metálico'],
        ];

        foreach ($attributesWithValues as $attributeName => $values) {
            $attribute = Attribute::create([
                'name' => $attributeName,
                'is_active' => true,
            ]);

            foreach ($values as $value) {
                $attribute->attributeValues()->create(['value' => $value]);
            }
        }
    }
}
