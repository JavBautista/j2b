<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanFeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            // Plan BASIC (ID 2)
            [
                'plan_id' => 2,
                'max_products' => 500,
                'max_clients' => 200,
                'max_collaborators' => 3,
                'max_tasks' => 100,
                'max_suppliers' => 50,
                'gps_tracking' => true,
                'reports_basic' => true,
                'reports_advanced' => false,
                'whatsapp_integration' => false,
                'email_marketing' => false,
                'custom_branding' => false,
                'api_access' => false,
                'multi_currency' => false,
                'support_level' => 'email',
            ],
            // Plan PREMIUM (ID 3)
            [
                'plan_id' => 3,
                'max_products' => -1, // Ilimitado
                'max_clients' => -1,
                'max_collaborators' => 10,
                'max_tasks' => -1,
                'max_suppliers' => -1,
                'gps_tracking' => true,
                'reports_basic' => true,
                'reports_advanced' => true,
                'whatsapp_integration' => true,
                'email_marketing' => false,
                'custom_branding' => false,
                'api_access' => false,
                'multi_currency' => true,
                'support_level' => 'email_chat',
            ],
            // Plan ENTERPRISE (ID 4)
            [
                'plan_id' => 4,
                'max_products' => -1,
                'max_clients' => -1,
                'max_collaborators' => -1, // Ilimitado
                'max_tasks' => -1,
                'max_suppliers' => -1,
                'gps_tracking' => true,
                'reports_basic' => true,
                'reports_advanced' => true,
                'whatsapp_integration' => true,
                'email_marketing' => true,
                'custom_branding' => true,
                'api_access' => true,
                'multi_currency' => true,
                'support_level' => 'priority',
            ],
        ];

        foreach ($features as $feature) {
            // Usar updateOrInsert para evitar duplicados (idempotente)
            DB::table('plan_features')->updateOrInsert(
                ['plan_id' => $feature['plan_id']], // Condición de búsqueda
                array_merge($feature, [
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
