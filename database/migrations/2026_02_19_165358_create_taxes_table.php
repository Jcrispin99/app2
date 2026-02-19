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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('invoice_label')->nullable();
            $table->string('tax_type'); // IGV, ISC, RETENCION, etc.
            $table->string('affectation_type_code')->nullable(); // SUNAT Cat. 07
            $table->decimal('rate_percent', 5, 2)->default(0);
            $table->boolean('is_price_inclusive')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index('tax_type');
            $table->index('affectation_type_code');
            $table->index('is_active');
            $table->index('is_default');
            $table->index('rate_percent');
            $table->index(['rate_percent', 'is_price_inclusive']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
