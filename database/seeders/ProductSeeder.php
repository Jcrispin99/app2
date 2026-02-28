<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\ProductTemplate;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        $colorAttribute = Attribute::where('name', 'Color')->first();
        $sizeAttribute = Attribute::where('name', 'Talla')->first();

        if ($categories->isEmpty()) {
            $this->command->warn('No hay categorías. Por favor ejecute CategorySeeder primero.');

            return;
        }

        $productNames = [
            'Camiseta Básica', 'Pantalón Denim', 'Zapatillas Running', 'Chaqueta Impermeable',
            'Sudadera con Capucha', 'Shorts Deportivos', 'Polo Clásico', 'Jeans Slim Fit',
            'Vestido Casual', 'Falda Plisada', 'Blusa Formal', 'Abrigo de Lana',
            'Sandalias de Verano', 'Botas de Cuero', 'Gorra Deportiva', 'Bufanda de Algodón',
            'Cinturón de Cuero', 'Reloj Deportivo', 'Mochila Urbana', 'Laptop HP 15',
        ];

        foreach ($productNames as $index => $name) {
            $product = ProductTemplate::create([
                'name' => $name,
                'description' => "Descripción del producto {$name}",
                'price' => rand(20, 200) + (rand(0, 99) / 100),
                'category_id' => $categories->random()->id,
                'is_active' => rand(1, 100) > 10, // 90% activos
            ]);

            // 70% de productos tienen variantes
            $hasVariants = rand(1, 100) <= 70;

            if ($hasVariants && ($colorAttribute || $sizeAttribute)) {
                // Seleccionar atributo aleatorio
                $attribute = rand(0, 1) === 0 ? $colorAttribute : $sizeAttribute;

                if (! $attribute) {
                    $attribute = $colorAttribute ?? $sizeAttribute;
                }

                if ($attribute) {
                    $attributeValues = $attribute->attributeValues;

                    if ($attributeValues->isEmpty()) {
                        // Si no hay valores, crear product_product simple
                        $product->productProducts()->create([
                            'sku' => 'SKU-'.strtoupper(uniqid()),
                            'barcode' => fake()->unique()->ean13(),
                            'price' => $product->price,
                            'is_principal' => true,
                        ]);
                    } else {
                        // Seleccionar 2-4 valores aleatorios
                        $selectedValues = $attributeValues->random(min(rand(2, 4), $attributeValues->count()));

                        $selectedValues->each(function ($attributeValue, $index) use ($product) {
                            $productProduct = $product->productProducts()->create([
                                'sku' => 'SKU-'.strtoupper(uniqid()),
                                'barcode' => fake()->unique()->ean13(),
                                'price' => $product->price + rand(-10, 20),
                                'is_principal' => $index === 0,
                            ]);

                            $productProduct->attributeValues()->attach($attributeValue->id);
                        });
                    }
                }
            } else {
                // Producto simple: 1 product_product sin atributos
                $product->productProducts()->create([
                    'sku' => 'SKU-'.strtoupper(uniqid()),
                    'barcode' => fake()->unique()->ean13(),
                    'price' => $product->price,
                    'is_principal' => true,
                ]);
            }
        }

        $this->command->info('Se crearon '.count($productNames).' productos con variantes correctamente.');
    }
}
