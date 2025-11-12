<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'trial_days',
                'value' => '30',
                'type' => 'integer',
                'label' => 'Días de prueba gratuita',
                'description' => 'Número de días de trial para nuevas tiendas',
            ],
            [
                'key' => 'grace_period_days',
                'value' => '7',
                'type' => 'integer',
                'label' => 'Días de gracia después del vencimiento',
                'description' => 'Días adicionales antes de bloquear la tienda cuando vence la suscripción (como Spotify)',
            ],
            [
                'key' => 'email_reminder_7_days',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'Email 7 días antes de vencer',
                'description' => 'Enviar recordatorio por email 7 días antes del vencimiento',
            ],
            [
                'key' => 'email_reminder_3_days',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'Email 3 días antes de vencer',
                'description' => 'Enviar recordatorio por email 3 días antes del vencimiento',
            ],
            [
                'key' => 'email_reminder_expiry_day',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'Email el día del vencimiento',
                'description' => 'Enviar recordatorio el día que vence la suscripción',
            ],
            [
                'key' => 'default_currency',
                'value' => 'MXN',
                'type' => 'string',
                'label' => 'Moneda predeterminada',
                'description' => 'MXN o USD',
            ],
            [
                'key' => 'iva_percentage',
                'value' => '16.00',
                'type' => 'decimal',
                'label' => 'Porcentaje de IVA',
                'description' => 'Porcentaje de IVA aplicado a los planes (16% en México)',
            ],
        ];

        foreach ($settings as $setting) {
            // Usar updateOrCreate para evitar duplicados (idempotente)
            DB::table('subscription_settings')->updateOrInsert(
                ['key' => $setting['key']], // Condición de búsqueda
                array_merge($setting, [
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
