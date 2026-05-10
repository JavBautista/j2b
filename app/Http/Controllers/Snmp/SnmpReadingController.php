<?php

namespace App\Http\Controllers\Snmp;

use App\Http\Controllers\Controller;
use App\Models\EquipmentCounterReading;
use App\Models\Rent;
use App\Models\RentDetail;
use App\Models\SnmpAgentToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SnmpReadingController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token_cliente' => 'sometimes|string',
            'datos_equipo' => 'required|array',
            'datos_equipo.modelo' => 'nullable|string|max:120',
            'datos_equipo.serie' => 'required|string|max:120',
            'datos_equipo.contadores' => 'nullable|array',
            'datos_equipo.contadores.negro' => 'nullable|integer|min:0',
            'datos_equipo.contadores.color' => 'nullable|integer|min:0',
            'datos_equipo.niveles_toner' => 'nullable|array',
            'datos_equipo.niveles_toner.K' => 'nullable|integer|min:0|max:100',
            'datos_equipo.niveles_toner.C' => 'nullable|integer|min:0|max:100',
            'datos_equipo.niveles_toner.M' => 'nullable|integer|min:0|max:100',
            'datos_equipo.niveles_toner.Y' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        /** @var SnmpAgentToken $tokenModel */
        $tokenModel = $request->attributes->get('snmp_token');

        $datos = $request->input('datos_equipo');
        $serie = $datos['serie'];
        $modelo = $datos['modelo'] ?? null;
        $contadores = $datos['contadores'] ?? [];
        $toners = $datos['niveles_toner'] ?? [];

        // Si el token tiene rent_id, restringir match solo a esa renta.
        // Si rent_id es NULL (token legacy), comportamiento previo: todas las rentas del cliente.
        if ($tokenModel->rent_id) {
            $rentIds = [$tokenModel->rent_id];
        } else {
            $rentIds = Rent::where('client_id', $tokenModel->client_id)->pluck('id');
        }

        $rentDetail = RentDetail::where('serial_number', $serie)
            ->whereIn('rent_id', $rentIds)
            ->where('monitor_enabled', true)
            ->first();

        $reading = EquipmentCounterReading::create([
            'shop_id' => $tokenModel->shop_id,
            'client_id' => $tokenModel->client_id,
            'rent_detail_id' => $rentDetail?->id,
            'raw_serial' => $serie,
            'raw_model' => $modelo,
            'counter_mono' => $contadores['negro'] ?? null,
            'counter_color' => $contadores['color'] ?? null,
            'toner_k' => $toners['K'] ?? null,
            'toner_c' => $toners['C'] ?? null,
            'toner_m' => $toners['M'] ?? null,
            'toner_y' => $toners['Y'] ?? null,
            'matched' => $rentDetail !== null,
            'source' => 'snmp',
            'raw_payload' => $request->all(),
            'ip_address' => $request->ip(),
            'read_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'matched' => $reading->matched,
            'rent_detail_id' => $reading->rent_detail_id,
            'reading_id' => $reading->id,
            'message' => $reading->matched
                ? 'Lectura registrada y asociada al equipo.'
                : 'Lectura registrada sin coincidencia (equipo no encontrado).',
        ], 200);
    }
}
