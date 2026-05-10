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
     * Incluye el toggle monitor_active del cliente + el cupo global de la Shop
     * (asignado por superadmin).
     */
    public function show(Client $client, MonitorLicenseService $service)
    {
        $shop = Auth::user()->shop;

        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'monitor' => array_merge(
                ['monitor_active' => (bool) $client->monitor_active],
                $service->summary($shop)
            ),
        ]);
    }

    /**
     * Activar/desactivar el servicio J2 Monitor para este cliente.
     * El cupo total lo controla el superadmin a nivel Shop.
     */
    public function update(Client $client, Request $request, MonitorLicenseService $service)
    {
        $shop = Auth::user()->shop;

        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $request->validate([
            'monitor_active' => 'required|boolean',
        ]);

        $client->monitor_active = $request->boolean('monitor_active');
        $client->save();

        return response()->json([
            'ok' => true,
            'message' => 'Configuración de J2 Monitor actualizada.',
            'monitor' => array_merge(
                ['monitor_active' => (bool) $client->monitor_active],
                $service->summary($shop)
            ),
        ]);
    }
}
