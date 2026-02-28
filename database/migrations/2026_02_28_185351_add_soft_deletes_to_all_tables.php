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
        $tables = [
            'companies', 'company_users', 'partners', 'categories', 'attributes',
            'attribute_values', 'unit_of_measures', 'product_templates', 'product_products',
            'attribute_value_products', 'imageables', 'membership_plans', 'membership_subscriptions',
            'membership_freezes', 'attendances', 'warehouses', 'inventorys', 'sequences',
            'journals', 'taxes', 'purchases', 'productables', 'pos_configs', 'pos_sessions',
            'journal_pos_configs', 'payment_methods', 'sales', 'pos_session_payments', 'users',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'companies', 'company_users', 'partners', 'categories', 'attributes',
            'attribute_values', 'unit_of_measures', 'product_templates', 'product_products',
            'attribute_value_products', 'imageables', 'membership_plans', 'membership_subscriptions',
            'membership_freezes', 'attendances', 'warehouses', 'inventorys', 'sequences',
            'journals', 'taxes', 'purchases', 'productables', 'pos_configs', 'pos_sessions',
            'journal_pos_configs', 'payment_methods', 'sales', 'pos_session_payments', 'users',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
