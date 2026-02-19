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
        Schema::create('product_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_template_id')->constrained('product_templates')->onDelete('cascade');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->default(0)->comment('Último precio de compra');
            // $table->integer('stock')->default(0);
            $table->boolean('is_principal')->default(false);
            $table->timestamps();

            $table->index(['product_template_id', 'sku'], 'product_products_template_sku_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_products');
    }
};
