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
        Schema::create('membership_freezes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_subscription_id')->constrained('membership_subscriptions')->onDelete('cascade');

            $table->date('freeze_start_date');
            $table->date('freeze_end_date')->nullable()->comment('NULL mientras esté activo');
            $table->integer('days_frozen')->default(0);
            $table->integer('planned_days')->default(0);

            $table->text('reason')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');

            $table->timestamps();

            $table->index(['membership_subscription_id', 'status']);
            $table->index('freeze_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_freezes');
    }
};
