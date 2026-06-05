<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientAccountMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Lógica central de la cuenta corriente del cliente (saldo a favor).
 * ÚNICA puerta de escritura del saldo: garantiza ledger + caché consistentes y a prueba de carreras.
 */
class ClientAccountService
{
    /** Tipos de movimiento permitidos (espejo del enum de la migración). */
    private const TIPOS_VALIDOS = [
        ClientAccountMovement::TYPE_DEPOSITO_ANTICIPO,
        ClientAccountMovement::TYPE_SOBREPAGO_NOTA,
        ClientAccountMovement::TYPE_DEVOLUCION,
        ClientAccountMovement::TYPE_AJUSTE_MANUAL,
        ClientAccountMovement::TYPE_APLICACION_VENTA,
    ];

    /**
     * Registra un movimiento en la cuenta del cliente y actualiza el saldo cacheado.
     *
     * @param  float  $amount  CON signo: + aumenta el saldo a favor, − lo consume.
     * @param  array  $opts    reference (Model), description (string), created_by (int).
     */
    public function registrarMovimiento(Client $client, string $type, float $amount, array $opts = []): ClientAccountMovement
    {
        if (!in_array($type, self::TIPOS_VALIDOS, true)) {
            throw new InvalidArgumentException("Tipo de movimiento inválido: {$type}");
        }

        $amount = round($amount, 2);

        return DB::transaction(function () use ($client, $type, $amount, $opts) {
            // Bloquea la fila del cliente para evitar saldos inconsistentes ante abonos simultáneos.
            $locked = Client::lockForUpdate()->findOrFail($client->id);

            $balanceAfter = round((float) $locked->account_balance + $amount, 2);

            $movement = new ClientAccountMovement();
            $movement->client_id     = $locked->id;
            $movement->shop_id       = $locked->shop_id;
            $movement->type          = $type;
            $movement->amount        = $amount;
            $movement->balance_after = $balanceAfter;
            $movement->description   = $opts['description'] ?? null;
            $movement->created_by    = $opts['created_by'] ?? auth()->id();

            $reference = $opts['reference'] ?? null;
            if ($reference instanceof Model) {
                $movement->reference()->associate($reference);
            }

            $movement->save();

            // Actualiza el caché en clients (la fuente de verdad son los movimientos).
            $locked->account_balance = $balanceAfter;
            $locked->save();

            // Refleja el saldo nuevo en la instancia que recibió el caller.
            $client->account_balance = $balanceAfter;

            return $movement;
        });
    }

    /**
     * Respaldo ante descuadre: reconstruye account_balance sumando todo el ledger.
     */
    public function recalcularSaldo(Client $client): float
    {
        return DB::transaction(function () use ($client) {
            $locked = Client::lockForUpdate()->findOrFail($client->id);

            $saldo = round((float) ClientAccountMovement::where('client_id', $locked->id)->sum('amount'), 2);

            $locked->account_balance = $saldo;
            $locked->save();
            $client->account_balance = $saldo;

            return $saldo;
        });
    }
}
