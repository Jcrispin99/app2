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
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            $table->foreignId('product_product_id')
                ->nullable()
                ->constrained('product_products')
                ->nullOnDelete();

            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->integer('duration_days');
            $table->decimal('price', 10, 2);

            $table->integer('max_entries_per_month')->nullable()->comment('NULL = ilimitado');
            $table->integer('max_entries_per_day')->default(1);

            $table->boolean('time_restricted')->default(false);
            $table->time('allowed_time_start')->nullable();
            $table->time('allowed_time_end')->nullable();
            $table->json('allowed_days')->nullable()->comment('NULL = todos los días');

            $table->boolean('allows_freezing')->default(false);
            $table->integer('max_freeze_days')->default(0)->comment('Días máximos de congelamiento en toda la vigencia');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};
