<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attribute_value_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_product_id')->constrained('product_products')->onDelete('cascade');
            $table->foreignId('attribute_value_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['product_product_id', 'attribute_value_id'], 'product_product_attribute_value_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_value_products');
    }
};
