<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $permision = [
            'create_categories',
            'read_categories',
            'update_categories',
            'delete_categories',

            'create_attributes',
            'read_attributes',
            'update_attributes',
            'delete_attributes',

            'create_products',
            'read_products',
            'update_products',
            'delete_products',

            'create_variants',
            'read_variants',
            'update_variants',
            'delete_variants',

            'create_warehouses',
            'read_warehouses',
            'update_warehouses',
            'delete_warehouses',

            'create_suppliers',
            'read_suppliers',
            'update_suppliers',
            'delete_suppliers',

            'create_purchases',
            'read_purchases',
            'update_purchases',
            'delete_purchases',

            'create_customers',
            'read_customers',
            'update_customers',
            'delete_customers',

            'create_sales',
            'read_sales',
            'update_sales',
            'delete_sales',

            'create_users',
            'read_users',
            'update_users',
            'delete_users',

            'create_roles',
            'read_roles',
            'update_roles',
            'delete_roles',

            'create_permissions',
            'read_permissions',
            'update_permissions',
            'delete_permissions',

            'create_companies',
            'read_companies',
            'update_companies',
            'delete_companies',

            'create_reports',
            'read_reports',
            'update_reports',
            'delete_reports',

            'create_members',
            'read_members',
            'update_members',
            'delete_members',
            'activate_portal_members',

            'create_membership_plans',
            'read_membership_plans',
            'update_membership_plans',
            'delete_membership_plans',
            'toggle_membership_plans',
            'activity_log_membership_plans',

            'create_subscriptions',
            'read_subscriptions',
            'update_subscriptions',
            'delete_subscriptions',
            'freeze_subscriptions',
            'unfreeze_subscriptions',

            'create_attendances',
            'read_attendances',
            'update_attendances',
            'delete_attendances',
            'checkin_attendances',
            'checkout_attendances',

            'create_unit_of_measures',
            'read_unit_of_measures',
            'update_unit_of_measures',
            'delete_unit_of_measures',

            'create_taxes',
            'read_taxes',
            'update_taxes',
            'delete_taxes',
            'toggle_taxes',

            'create_journals',
            'read_journals',
            'update_journals',
            'delete_journals',
            'reset_journals_sequence',

            'create_posconfig',
            'read_posconfig',
            'update_posconfig',
            'delete_posconfig',

            'create_pos_sessions',
            'read_pos_sessions',
            'update_pos_sessions',
            'delete_pos_sessions',

            'read_pos_config_sessions',
            'read_pos_orders',

            'access_pos',
            'process_pos_payments',
            'close_pos_sessions',
            'refund_pos',

            'read_settings',
            'update_settings_profile',
            'update_settings_password',
            'update_settings_two_factor',

            'access_dashboard',
        ];

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permision as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        Permission::where('guard_name', 'web')
            ->whereNotIn('name', $permision)
            ->delete();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
            ->syncPermissions(Permission::all());

        Role::firstOrCreate(['name' => 'almacen', 'guard_name' => 'web'])
            ->givePermissionTo([
                'create_categories',
                'read_categories',
                'update_categories',
                'delete_categories',
                'create_attributes',
                'read_attributes',
                'update_attributes',
                'delete_attributes',
                'create_products',
                'read_products',
                'update_products',
                'delete_products',
                'create_variants',
                'read_variants',
                'update_variants',
                'delete_variants',
                'create_warehouses',
                'read_warehouses',
                'update_warehouses',
                'delete_warehouses',
                'create_suppliers',
                'read_suppliers',
                'update_suppliers',
                'delete_suppliers',
            ]);

        Role::firstOrCreate(['name' => 'lector', 'guard_name' => 'web'])
            ->givePermissionTo([
                'read_categories',
                'read_attributes',
                'read_products',
                'read_variants',
                'read_warehouses',
                'read_suppliers',
                'read_purchases',
                'read_customers',
                'read_sales',
                'read_users',
                'read_roles',
                'read_permissions',
                'read_companies',
                'read_reports',
                'read_members',
                'read_membership_plans',
                'read_subscriptions',
                'read_attendances',
                'read_unit_of_measures',
                'read_taxes',
                'read_journals',
                'read_posconfig',
                'read_pos_sessions',
                'read_settings',
            ]);

        Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web'])
            ->givePermissionTo([
                'create_categories',
                'read_categories',
                'update_categories',
                'delete_categories',
                'create_attributes',
                'read_attributes',
                'update_attributes',
                'delete_attributes',
                'create_products',
                'read_products',
                'update_products',
                'delete_products',
                'create_variants',
                'read_variants',
                'update_variants',
                'delete_variants',
                'create_warehouses',
                'read_warehouses',
                'update_warehouses',
                'delete_warehouses',
                'create_suppliers',
                'read_suppliers',
                'update_suppliers',
                'delete_suppliers',
            ]);

        Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web'])
            ->givePermissionTo([
                'read_categories',
                'read_attributes',
                'read_products',
                'read_variants',
                'read_warehouses',
                'read_suppliers',
                'read_purchases',
                'read_customers',
                'read_sales',
                'read_users',
                'read_roles',
                'read_permissions',
                'read_companies',
                'read_reports',
                'read_members',
                'read_membership_plans',
                'read_subscriptions',
                'read_attendances',
                'read_unit_of_measures',
                'read_taxes',
                'read_journals',
                'read_posconfig',
                'read_pos_sessions',
                'read_settings',
            ]);

        Role::firstOrCreate(['name' => 'cajero', 'guard_name' => 'web'])
            ->givePermissionTo([
                'access_dashboard',
                'access_pos',
                'process_pos_payments',
                'close_pos_sessions',
                'refund_pos',
                'read_posconfig',
                'read_pos_sessions',
                'read_attendances',
                'checkin_attendances',
                'read_categories',
                'read_products',
                'read_variants',
                'read_warehouses',
                'read_customers',
                'create_customers',
                'create_sales',
                'read_sales',
                'update_sales',
            ]);

        $user = User::query()->find(1);
        if ($user) {
            $user->syncRoles(['admin']);
        }
    }
}
