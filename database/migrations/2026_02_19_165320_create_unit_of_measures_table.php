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
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->string('family')->nullable();

            $table->foreignId('base_unit_id')
                ->nullable()
                ->constrained('unit_of_measures')
                ->nullOnDelete();

            $table->decimal('factor', 20, 8)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['family', 'name']);
            $table->index(['base_unit_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
