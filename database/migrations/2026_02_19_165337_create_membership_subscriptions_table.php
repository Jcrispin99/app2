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
        Schema::create('membership_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->foreignId('membership_plan_id')->constrained('membership_plans')->onDelete('restrict');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            $table->date('start_date');
            $table->date('end_date');
            $table->date('original_end_date')->comment('Fecha original antes de congelamientos');

            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_method', 50)->nullable(); // efectivo, tarjeta, transferencia
            $table->string('payment_reference', 100)->nullable();

            // Control de uso
            $table->integer('entries_used')->default(0)->comment('Entradas usadas en el mes');
            $table->date('last_entry_date')->nullable();
            $table->integer('entries_this_month')->default(0);
            $table->date('current_month_start')->nullable();

            // Congelamiento
            $table->integer('total_days_frozen')->default(0);
            $table->integer('remaining_freeze_days')->default(0);

            // Estado
            $table->enum('status', ['active', 'frozen', 'expired', 'cancelled'])->default('active');
            $table->text('notes')->nullable();

            // Usuario que registró/vendió
            $table->foreignId('sold_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['partner_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index(['end_date', 'status']);
            $table->index('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_subscriptions');
    }
};
