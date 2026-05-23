<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Rent;
use App\Models\RentDetail;
use Illuminate\Database\QueryException;

trait HandlesSerialConflict
{
    /**
     * Detecta si el serial ya está usado en la tienda y devuelve un 422 con mensaje
     * detallado del conflicto. Si no hay conflicto, devuelve null.
     *
     * Usar ANTES del save() para mensajes amigables. Combinar con handleDuplicateSerialException()
     * para defensa en profundidad ante race conditions.
     */
    protected function serialConflictResponse(int $shopId, string $serial, ?int $ignoreId)
    {
        $query = RentDetail::where('shop_id', $shopId)
            ->where('serial_number', $serial);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $existing = $query->first();
        if (!$existing) return null;

        $isInactive = (int) $existing->active === 0;
        $inInventory = !$isInactive && (int) $existing->rent_id === 0;

        $conflict = [
            'rent_detail_id' => $existing->id,
            'trademark' => $existing->trademark,
            'model' => $existing->model,
            'serial_number' => $existing->serial_number,
            'in_inventory' => $inInventory,
            'is_inactive' => $isInactive,
        ];

        if ($isInactive) {
            $message = "La serie '{$serial}' está reservada por un equipo DESACTIVADO de tu inventario: {$existing->trademark} {$existing->model} (id #{$existing->id}). Para liberar el serial, cambia el serial actual por otro distinto o pide limpieza del equipo desactivado.";
        } elseif ($inInventory) {
            $message = "El equipo {$existing->trademark} {$existing->model} con serial '{$serial}' ya existe en tu inventario. Asígnalo a esta renta en lugar de crearlo de nuevo.";
        } else {
            $rent = Rent::with('client')->find($existing->rent_id);
            $clientName = $rent && $rent->client ? $rent->client->name : 'cliente desconocido';
            $folio = $rent ? $rent->folio : null;

            $conflict['client_id'] = $rent ? $rent->client_id : null;
            $conflict['client_name'] = $clientName;
            $conflict['rent_id'] = $existing->rent_id;
            $conflict['rent_folio'] = $folio;

            $message = "El número de serie '{$serial}' ya está en uso por el equipo {$existing->trademark} {$existing->model} del cliente '{$clientName}' (renta #{$folio}).";
        }

        return response()->json([
            'ok' => false,
            'code' => 'serial_in_use',
            'message' => $message,
            'conflict' => $conflict,
        ], 422);
    }

    /**
     * Defensa en profundidad: convierte QueryException 1062 sobre el UNIQUE de serial
     * en una respuesta 422 amigable. Si no es ese caso, re-lanza.
     */
    protected function handleDuplicateSerialException(QueryException $e, string $serial)
    {
        $isDuplicate = ($e->errorInfo[1] ?? null) === 1062
            && str_contains($e->getMessage(), 'rent_details_shop_serial_unique');

        if (!$isDuplicate) {
            throw $e;
        }

        return response()->json([
            'ok' => false,
            'code' => 'serial_in_use',
            'message' => "El número de serie '{$serial}' ya está registrado en tu tienda. Refresca la página e intenta de nuevo; si el problema persiste, contacta a soporte.",
            'conflict' => ['serial_number' => $serial, 'is_inactive' => null],
        ], 422);
    }
}
