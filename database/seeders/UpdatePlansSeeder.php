<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdatePlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Este seeder es IDEMPOTENTE (seguro ejecutar múltiples veces)
        // Solo actualiza si los planes existen

        // Desactivar plan FREE (ID 1) si existe
        if (DB::table('plans')->where('id', 1)->exists()) {
            DB::table('plans')->where('id', 1)->update([
                'active' => 0,
                'description' => 'Plan gratuito DESHABILITADO - Solo para referencia histórica'
            ]);
        }

        // Actualizar plan BASIC (ID 2) si existe
        if (DB::table('plans')->where('id', 2)->exists()) {
            DB::table('plans')->where('id', 2)->update([
                'name' => 'BASIC',
                'description' => 'Plan básico para negocios pequeños',
                'price' => 999.00, // Precio CON IVA
                'price_without_iva' => 861.21, // 999 / 1.16
                'currency' => 'MXN',
                'iva_percentage' => 16.00,
                'billing_period' => 'monthly',
                'sort_order' => 1,
                'active' => 1,
            ]);
        }

        // Actualizar plan PREMIUM (ID 3) si existe
        if (DB::table('plans')->where('id', 3)->exists()) {
            DB::table('plans')->where('id', 3)->update([
                'name' => 'PREMIUM',
                'description' => 'Plan premium para negocios en crecimiento',
                'price' => 1999.00, // Precio CON IVA
                'price_without_iva' => 1723.28, // 1999 / 1.16
                'currency' => 'MXN',
                'iva_percentage' => 16.00,
                'billing_period' => 'monthly',
                'sort_order' => 2,
                'active' => 1,
            ]);
        }

        // Actualizar plan ENTERPRISE/STANDARD (ID 4) si existe
        if (DB::table('plans')->where('id', 4)->exists()) {
            DB::table('plans')->where('id', 4)->update([
                'name' => 'ENTERPRISE',
                'description' => 'Plan empresarial sin límites',
                'price' => 3999.00, // Precio CON IVA
                'price_without_iva' => 3447.41, // 3999 / 1.16
                'currency' => 'MXN',
                'iva_percentage' => 16.00,
                'billing_period' => 'monthly',
                'sort_order' => 3,
                'active' => 1,
            ]);
        }
    }
}
