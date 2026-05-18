<?php

namespace App\Services\Monitor;

use App\Models\MonitorPricingTier;
use App\Models\Shop;

class MonitorBillingService
{
    public function resolveTier(int $equipmentCount): ?MonitorPricingTier
    {
        if ($equipmentCount <= 0) {
            return null;
        }

        return MonitorPricingTier::active()
            ->orderBy('sort_order')
            ->get()
            ->first(fn (MonitorPricingTier $tier) => $tier->applies($equipmentCount));
    }

    /**
     * Calcula el cobro mensual del servicio J2 Monitor para una shop.
     *
     * Reglas:
     *  - Si monitor_billing_enabled = false → no se cobra.
     *  - Si monitor_licenses_total <= 0 → no se cobra.
     *  - Si shop.monitor_tier_locked_id está set → usa ese tier + precios congelados (override).
     *  - Si no, resuelve tier vigente del catálogo según licenses_total.
     *  - is_flat_rate → monitor_amount = flat_amount (independiente del conteo).
     *  - includes_base_plan → el flujo de pago debe anular el plan_amount.
     */
    public function calculateForShop(Shop $shop): array
    {
        $base = [
            'enabled' => false,
            'equipment_count' => (int) $shop->monitor_licenses_total,
            'tier' => null,
            'unit_price' => null,
            'flat_amount' => null,
            'monitor_amount' => 0.0,
            'includes_base_plan' => false,
        ];

        if (! $shop->monitor_billing_enabled) {
            return $base;
        }

        $count = (int) $shop->monitor_licenses_total;
        if ($count <= 0) {
            return $base;
        }

        $tier = $shop->monitor_tier_locked_id
            ? $shop->monitorLockedTier
            : $this->resolveTier($count);

        if (! $tier) {
            return $base;
        }

        $isFlat = $tier->is_flat_rate;
        $lockedUnit = $shop->monitor_locked_price_per_equipment !== null
            ? (float) $shop->monitor_locked_price_per_equipment
            : null;
        $lockedFlat = $shop->monitor_locked_flat_amount !== null
            ? (float) $shop->monitor_locked_flat_amount
            : null;

        if ($isFlat) {
            $flat = $lockedFlat ?? (float) $tier->flat_amount;
            return [
                'enabled' => true,
                'equipment_count' => $count,
                'tier' => $tier,
                'unit_price' => null,
                'flat_amount' => $flat,
                'monitor_amount' => round($flat, 2),
                'includes_base_plan' => (bool) $tier->includes_base_plan,
            ];
        }

        $unit = $lockedUnit ?? (float) $tier->price_per_equipment;

        return [
            'enabled' => true,
            'equipment_count' => $count,
            'tier' => $tier,
            'unit_price' => $unit,
            'flat_amount' => null,
            'monitor_amount' => round($unit * $count, 2),
            'includes_base_plan' => (bool) $tier->includes_base_plan,
        ];
    }
}
