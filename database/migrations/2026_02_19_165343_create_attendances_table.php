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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->foreignId('membership_subscription_id')->nullable()->constrained('membership_subscriptions')->onDelete('set null');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->dateTime('check_in_time');
            $table->dateTime('check_out_time')->nullable();
            $table->integer('duration_minutes')->nullable(); // Calculado al check-out

            $table->enum('status', ['valid', 'denied', 'manual_override'])->default('valid');
            $table->text('validation_message')->nullable(); // Razón de denegación o notas

            $table->boolean('is_manual_entry')->default(false); // Si fue registrado manualmente por staff
            $table->foreignId('registered_by')->nullable()->constrained('users')->onDelete('set null'); // Staff que registró

            $table->timestamps();
            $table->softDeletes();

            // Indexes para búsquedas rápidas
            $table->index('check_in_time');
            $table->index(['partner_id', 'check_in_time']);
            $table->index(['company_id', 'check_in_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
