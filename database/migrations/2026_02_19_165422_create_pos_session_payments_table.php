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
        Schema::create('pos_session_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_session_id')->constrained('pos_sessions')->onDelete('cascade');
            $table->foreignId('sale_id')
                ->nullable()
                ->constrained('sales')
                ->onDelete('set null');
            $table->foreignId('payment_method_id')->constrained('payment_methods');
            $table->decimal('amount', 10, 2);
            $table->foreignId('reference_sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_session_payments');
    }
};
