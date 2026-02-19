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
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('pos_config_id')->constrained('pos_configs');
            $table->decimal('opening_balance', 10, 2);
            $table->text('opening_note')->nullable();
            $table->decimal('closing_balance', 10, 2)->nullable();
            $table->text('closing_note')->nullable();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->string('status'); // opening_control, opened, closing_control, closed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
