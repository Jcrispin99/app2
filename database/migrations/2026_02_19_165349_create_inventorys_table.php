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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('detail')->nullable();

            // Entrada
            $table->integer('quantity_in')->default(0);
            $table->decimal('cost_in', 10, 2)->default(0);
            $table->decimal('total_in', 10, 2)->default(0);

            // Salida
            $table->integer('quantity_out')->default(0);
            $table->decimal('cost_out', 10, 2)->default(0);
            $table->decimal('total_out', 10, 2)->default(0);

            // Balance (Saldo)
            $table->integer('quantity_balance')->default(0);
            $table->decimal('cost_balance', 10, 2)->default(0);
            $table->decimal('total_balance', 10, 2)->default(0);

            // Relaciones
            $table->foreignId('product_product_id')->constrained('product_products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->morphs('inventoryable'); // Polimórfico: puede ser compra, venta, ajuste, etc.

            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['product_product_id', 'warehouse_id', 'created_at'], 'inventory_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
