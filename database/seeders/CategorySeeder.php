<?php


namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainCategories = [
            'Electrónica',
            'Moda y Accesorios',
            'Hogar y Jardín',
            'Salud y Belleza',
            'Deportes y Aire Libre',
            'Juguetes y Bebés',
            'Libros y Papelería',
            'Alimentos y Bebidas',
            'Automotriz',
            'Mascotas',
            'Herramientas',
            'Oficina y Papelería',
        ];

        foreach ($mainCategories as $categoryName) {
            Category::create([
                'name' => $categoryName,
                'full_name' => $categoryName,
                'description' => 'Descripción de la categoría ' . $categoryName,
                'is_active' => true,
            ]);
        }
    }
}
