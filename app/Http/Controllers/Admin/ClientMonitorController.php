<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\Monitor\MonitorLicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientMonitorController extends Controller
{
    /**
     * Resumen del servicio J2 Monitor del cliente.
     */
    public function show(Client $client, MonitorLicenseService $service)
    {
        $shop = Auth::user()->shop;

        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'monitor' => $service->summary($client),
        ]);
    }

    /**
     * Configurar el servicio J2 Monitor del cliente
     * (activar/desactivar y asignar total de licencias contratadas).
     */
    public function update(Client $client, Request $request, MonitorLicenseService $service)
    {
        $shop = Auth::user()->shop;

        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $request->validate([
            'monitor_active' => 'required|boolean',
            'monitor_licenses_total' => 'required|integer|min:0|max:10000',
        ]);

        $newTotal = (int) $request->monitor_licenses_total;
        $used = $client->monitor_licenses_used;

        if ($newTotal < $used) {
            $enabled = $service->enabledEquipments($client);
            return response()->json([
                'ok' => false,
                'message' => "No se puede reducir a {$newTotal} licencias: hay {$used} equipos con licencia asignada. Desactiva primero los equipos sobrantes.",
                'enabled_equipments' => $enabled,
            ], 422);
        }

        $client->monitor_active = $request->boolean('monitor_active');
        $client->monitor_licenses_total = $newTotal;
        $client->save();

        return response()->json([
            'ok' => true,
            'message' => 'Configuración de J2 Monitor actualizada.',
            'monitor' => $service->summary($client->fresh()),
        ]);
    }
}
