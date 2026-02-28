<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\MembershipPlan;
use App\Models\ProductProduct;
use App\Models\ProductTemplate;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener la primera compañía (o ajustar según necesites)
        $company = Company::first();

        if (! $company) {
            $this->command->warn('No hay compañías en la base de datos. Crea una primero.');

            return;
        }

        $category = Category::firstOrCreate(
            ['name' => 'Suscripciones', 'parent_id' => null],
            [
                'name' => 'Suscripciones',
                'full_name' => 'Suscripciones',
                'description' => 'Productos internos para planes y suscripciones.',
                'is_active' => false,
            ],
        );

        $plans = [
            // Plan 1: Básico Matutino
            [
                'company_id' => $company->id,
                'name' => 'Básico Mañana',
                'description' => 'Acceso solo en horario matutino, de lunes a viernes. Ideal para madrugadores.',
                'duration_days' => 30,
                'price' => 80.00,
                'max_entries_per_month' => 12, // 3 veces por semana
                'max_entries_per_day' => 1,
                'time_restricted' => true,
                'allowed_time_start' => '06:00:00',
                'allowed_time_end' => '12:00:00',
                'allowed_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'allows_freezing' => false,
                'max_freeze_days' => 0,
                'is_active' => true,
            ],

            // Plan 2: Básico Tarde
            [
                'company_id' => $company->id,
                'name' => 'Básico Tarde',
                'description' => 'Acceso en horario de tarde, de lunes a viernes. Perfecto para después del trabajo.',
                'duration_days' => 30,
                'price' => 85.00,
                'max_entries_per_month' => 12,
                'max_entries_per_day' => 1,
                'time_restricted' => true,
                'allowed_time_start' => '14:00:00',
                'allowed_time_end' => '20:00:00',
                'allowed_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'allows_freezing' => false,
                'max_freeze_days' => 0,
                'is_active' => true,
            ],

            // Plan 3: Premium Full Access Mensual
            [
                'company_id' => $company->id,
                'name' => 'Premium Full Access',
                'description' => 'Acceso ilimitado todos los días, todo el horario. Incluye 7 días de congelamiento.',
                'duration_days' => 30,
                'price' => 150.00,
                'max_entries_per_month' => null, // Ilimitado
                'max_entries_per_day' => 1,
                'time_restricted' => false,
                'allowed_time_start' => null,
                'allowed_time_end' => null,
                'allowed_days' => null, // Todos los días
                'allows_freezing' => true,
                'max_freeze_days' => 7,
                'is_active' => true,
            ],

            // Plan 4: Premium Trimestral
            [
                'company_id' => $company->id,
                'name' => 'Premium Trimestral',
                'description' => 'Plan de 3 meses con acceso ilimitado. Incluye 15 días de congelamiento.',
                'duration_days' => 90,
                'price' => 400.00,
                'max_entries_per_month' => null,
                'max_entries_per_day' => 1,
                'time_restricted' => false,
                'allowed_time_start' => null,
                'allowed_time_end' => null,
                'allowed_days' => null,
                'allows_freezing' => true,
                'max_freeze_days' => 15,
                'is_active' => true,
            ],

            // Plan 5: VIP Anual
            [
                'company_id' => $company->id,
                'name' => 'VIP Anual',
                'description' => 'Plan anual con acceso ilimitado y 2 entradas por día. Incluye 30 días de congelamiento.',
                'duration_days' => 365,
                'price' => 1500.00,
                'max_entries_per_month' => null,
                'max_entries_per_day' => 2, // Puede entrar 2 veces al día
                'time_restricted' => false,
                'allowed_time_start' => null,
                'allowed_time_end' => null,
                'allowed_days' => null,
                'allows_freezing' => true,
                'max_freeze_days' => 30,
                'is_active' => true,
            ],

            // Plan 6: Weekend Warrior
            [
                'company_id' => $company->id,
                'name' => 'Weekend Warrior',
                'description' => 'Solo fines de semana. Ideal para quienes trabajan entre semana.',
                'duration_days' => 30,
                'price' => 60.00,
                'max_entries_per_month' => 8, // 2 veces por semana
                'max_entries_per_day' => 1,
                'time_restricted' => false,
                'allowed_time_start' => null,
                'allowed_time_end' => null,
                'allowed_days' => ['saturday', 'sunday'],
                'allows_freezing' => false,
                'max_freeze_days' => 0,
                'is_active' => true,
            ],

            // Plan 7: Estudiante
            [
                'company_id' => $company->id,
                'name' => 'Estudiante',
                'description' => 'Plan especial para estudiantes. Horario tarde/noche entre semana.',
                'duration_days' => 30,
                'price' => 70.00,
                'max_entries_per_month' => 20,
                'max_entries_per_day' => 1,
                'time_restricted' => true,
                'allowed_time_start' => '16:00:00',
                'allowed_time_end' => '22:00:00',
                'allowed_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'allows_freezing' => false,
                'max_freeze_days' => 0,
                'is_active' => true,
            ],

            // Plan 8: Black (Corporativo)
            [
                'company_id' => $company->id,
                'name' => 'Black Corporativo',
                'description' => 'Plan premium sin restricciones. Acceso 24/7 con servicios VIP.',
                'duration_days' => 30,
                'price' => 200.00,
                'max_entries_per_month' => null,
                'max_entries_per_day' => 3, // Puede entrar hasta 3 veces
                'time_restricted' => false,
                'allowed_time_start' => null,
                'allowed_time_end' => null,
                'allowed_days' => null,
                'allows_freezing' => true,
                'max_freeze_days' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            $membershipPlan = MembershipPlan::create($plan);

            $template = ProductTemplate::create([
                'name' => "Plan: {$membershipPlan->name}",
                'description' => $membershipPlan->description,
                'price' => $membershipPlan->price,
                'category_id' => $category->id,
                'is_active' => true,
                'is_pos_visible' => false,
                'tracks_inventory' => false,
            ]);

            $variant = ProductProduct::create([
                'product_template_id' => $template->id,
                'sku' => null,
                'barcode' => null,
                'price' => $membershipPlan->price,
                'cost_price' => 0,
                'is_principal' => true,
            ]);

            $membershipPlan->update([
                'product_product_id' => $variant->id,
            ]);
        }

        $this->command->info('✅ Se crearon ' . count($plans) . ' planes de membresía con productos asociados.');
    }
}
