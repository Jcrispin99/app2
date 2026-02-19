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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            $table->string('serie');
            $table->string('correlative');

            $table->foreignId('journal_id')->constrained('journals')->onDelete('cascade');

            $table->timestamp('date')->useCurrent();

            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');

            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');

            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->decimal('total', 10, 2)->default(0.00);

            $table->string('observation')->nullable();

            // Estados de ciclo de compra y pago
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');

            // Datos de factura del proveedor (opcional)
            $table->string('vendor_bill_number')->nullable();
            $table->date('vendor_bill_date')->nullable();

            $table->timestamps();

            // Índices y unicidad útil
            $table->index(['status']);
            $table->index(['payment_status']);
            $table->unique(['company_id', 'serie', 'correlative'], 'purchase_unique_company_voucher_serie_corr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
