<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('monitor_pricing_tiers')->insert([
            [
                'name' => '1 a 10 equipos',
                'min_equipment' => 1, 'max_equipment' => 10,
                'price_per_equipment' => 45.00,
                'is_flat_rate' => false, 'flat_amount' => null,
                'includes_base_plan' => false,
                'currency' => 'MXN', 'active' => true, 'sort_order' => 10,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => '11 a 25 equipos',
                'min_equipment' => 11, 'max_equipment' => 25,
                'price_per_equipment' => 25.00,
                'is_flat_rate' => false, 'flat_amount' => null,
                'includes_base_plan' => false,
                'currency' => 'MXN', 'active' => true, 'sort_order' => 20,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => '26 a 50 equipos',
                'min_equipment' => 26, 'max_equipment' => 50,
                'price_per_equipment' => 15.00,
                'is_flat_rate' => false, 'flat_amount' => null,
                'includes_base_plan' => false,
                'currency' => 'MXN', 'active' => true, 'sort_order' => 30,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => '51 a 100 equipos',
                'min_equipment' => 51, 'max_equipment' => 100,
                'price_per_equipment' => 13.00,
                'is_flat_rate' => false, 'flat_amount' => null,
                'includes_base_plan' => false,
                'currency' => 'MXN', 'active' => true, 'sort_order' => 40,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => '101 a 200 equipos',
                'min_equipment' => 101, 'max_equipment' => 200,
                'price_per_equipment' => 11.00,
                'is_flat_rate' => false, 'flat_amount' => null,
                'includes_base_plan' => false,
                'currency' => 'MXN', 'active' => true, 'sort_order' => 50,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => '201 a 380 equipos',
                'min_equipment' => 201, 'max_equipment' => 380,
                'price_per_equipment' => 9.00,
                'is_flat_rate' => false, 'flat_amount' => null,
                'includes_base_plan' => false,
                'currency' => 'MXN', 'active' => true, 'sort_order' => 60,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => '381+ equipos (tarifa plana)',
                'min_equipment' => 381, 'max_equipment' => null,
                'price_per_equipment' => null,
                'is_flat_rate' => true, 'flat_amount' => 4000.00,
                'includes_base_plan' => true,
                'currency' => 'MXN', 'active' => true, 'sort_order' => 70,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('monitor_pricing_tiers')->truncate();
    }
};
