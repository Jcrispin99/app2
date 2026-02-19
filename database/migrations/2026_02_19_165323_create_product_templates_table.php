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
        Schema::create('product_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);

            // Relaciones
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();

            // Configuración
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pos_visible')->default(true);
            $table->boolean('tracks_inventory')->default(true);
            $table->boolean('is_service')->default(false);

            $table->timestamps();

            // Índices
            $table->index(['is_pos_visible', 'tracks_inventory']);
            $table->index('is_service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_templates');
    }
};
