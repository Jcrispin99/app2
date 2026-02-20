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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            $table->boolean('is_customer')->default(false);
            $table->boolean('is_member')->default(false);
            $table->boolean('is_supplier')->default(false);

            $table->enum('document_type', ['DNI', 'RUC', 'CE', 'Passport'])
                ->default('DNI');
            $table->string('document_number', 20);

            $table->string('name', 200)->nullable();

            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('photo_url')->nullable();

            $table->text('address')->nullable();
            $table->string('ubigeo', 6)->nullable();

            $table->date('birth_date')->nullable();
            $table->enum('gender', ['M', 'F', 'Other'])->nullable();

            $table->integer('payment_terms')->nullable();
            $table->decimal('credit_limit', 10, 2)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('business_license', 100)->nullable();
            $table->string('provider_category', 50)->nullable(); // 'equipment', 'supplements', 'services'

            $table->enum('status', ['active', 'inactive', 'suspended', 'blacklisted'])
                ->default('active');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['document_type', 'document_number']);

            $table->index('is_customer');
            $table->index('is_member');
            $table->index('is_supplier');
            $table->index('email');
            $table->index('status');
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
