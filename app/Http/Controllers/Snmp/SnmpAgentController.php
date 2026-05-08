<?php

namespace App\Http\Controllers\Snmp;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\RentDetail;
use App\Models\SnmpAgentToken;
use Illuminate\Http\Request;

class SnmpAgentController extends Controller
{
    /**
     * Endpoint que el agente Python consulta para saber qué equipos leer.
     * Devuelve solo los que tienen licencia de monitoreo asignada,
     * con IP local y serial capturados.
     *
     * Auth: Bearer (middleware snmp.token).
     */
    public function equipmentList(Request $request)
    {
        /** @var SnmpAgentToken $tokenModel */
        $tokenModel = $request->attributes->get('snmp_token');

        $client = Client::find($tokenModel->client_id);

        if (!$client) {
            return response()->json([
                'ok' => false,
                'message' => 'Cliente asociado al token no existe.',
            ], 404);
        }

        if (!$client->monitor_active) {
            return response()->json([
                'ok' => true,
                'monitor_active' => false,
                'equipos' => [],
                'message' => 'Servicio J2 Monitor inactivo para este cliente.',
            ]);
        }

        $rentIds = $client->rents()->pluck('id');

        $equipos = RentDetail::whereIn('rent_id', $rentIds)
            ->where('monitor_enabled', true)
            ->where('active', true)
            ->whereNotNull('local_ip')
            ->whereNotNull('serial_number')
            ->get(['id', 'serial_number', 'local_ip', 'trademark', 'model']);

        return response()->json([
            'ok' => true,
            'monitor_active' => true,
            'equipos' => $equipos->map(fn ($e) => [
                'serial_number' => $e->serial_number,
                'local_ip' => $e->local_ip,
                'modelo' => trim(($e->trademark ?? '') . ' ' . ($e->model ?? '')),
            ])->values(),
        ]);
    }
}
