<?php

namespace App\Services\Monitor;

use App\Models\RentDetail;
use App\Models\Shop;

class MonitorLicenseService
{
    /**
     * Devuelve si la tienda puede activar la licencia en un equipo más.
     *
     * Si $excluding se pasa y ya tiene monitor_enabled=true, no cuenta dos
     * veces (caso típico: edición de un equipo que ya tenía licencia).
     */
    public function canEnable(Shop $shop, ?RentDetail $excluding = null): bool
    {
        $used = $shop->monitor_licenses_used;

        if ($excluding && $excluding->monitor_enabled) {
            $used = max(0, $used - 1);
        }

        return $used < (int) $shop->monitor_licenses_total;
    }

    /**
     * Resumen de licencias de la tienda para mostrar en UI.
     */
    public function summary(Shop $shop): array
    {
        return [
            'total' => (int) $shop->monitor_licenses_total,
            'used' => $shop->monitor_licenses_used,
            'available' => $shop->monitor_licenses_available,
        ];
    }

    /**
     * Lista de equipos que actualmente consumen una licencia en la tienda.
     * Útil cuando se quiere reducir el cupo y hay que avisar cuáles desactivar.
     */
    public function enabledEquipments(Shop $shop)
    {
        return RentDetail::where('shop_id', $shop->id)
            ->where('monitor_enabled', true)
            ->get(['id', 'rent_id', 'trademark', 'model', 'serial_number', 'local_ip']);
    }
}
