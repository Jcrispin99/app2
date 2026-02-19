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
        Schema::create('journal_pos_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_config_id')->constrained('pos_configs')->cascadeOnDelete();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();

            // Tipo de documento que manejará este journal en el POS
            // 'invoice' = Factura, 'receipt' = Boleta, 'credit_note' = Nota de Crédito, 'debit_note' = Nota de Débito
            $table->enum('document_type', ['invoice', 'receipt', 'credit_note', 'debit_note']);

            // Marcar si este journal es el predeterminado para este tipo de documento en este POS
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            // Evitar duplicados: un POS no puede tener el mismo journal con el mismo tipo dos veces
            $table->unique(['pos_config_id', 'journal_id', 'document_type'], 'journal_pos_config_unique');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_pos_configs');
    }
};
