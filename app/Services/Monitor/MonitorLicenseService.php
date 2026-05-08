<?php

namespace App\Services\Monitor;

use App\Models\Client;
use App\Models\RentDetail;

class MonitorLicenseService
{
    /**
     * Devuelve si el cliente puede activar la licencia en un equipo más.
     *
     * Si $excluding se pasa y ya tiene monitor_enabled=true, no cuenta dos
     * veces (caso típico: edición de un equipo que ya tenía licencia).
     */
    public function canEnable(Client $client, ?RentDetail $excluding = null): bool
    {
        $used = $client->monitor_licenses_used;

        if ($excluding && $excluding->monitor_enabled) {
            $used = max(0, $used - 1);
        }

        return $used < (int) $client->monitor_licenses_total;
    }

    /**
     * Resumen de licencias del cliente para mostrar en UI.
     */
    public function summary(Client $client): array
    {
        return [
            'monitor_active' => (bool) $client->monitor_active,
            'total' => (int) $client->monitor_licenses_total,
            'used' => $client->monitor_licenses_used,
            'available' => $client->monitor_licenses_available,
        ];
    }

    /**
     * Lista de equipos que actualmente consumen una licencia.
     * Útil cuando se quiere reducir el cupo y hay que avisar cuáles desactivar.
     */
    public function enabledEquipments(Client $client)
    {
        return RentDetail::whereIn('rent_id', $client->rents()->pluck('id'))
            ->where('monitor_enabled', true)
            ->get(['id', 'rent_id', 'trademark', 'model', 'serial_number', 'local_ip']);
    }
}
