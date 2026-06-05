<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Movimiento de la cuenta corriente del cliente (ledger del saldo a favor).
 * Fuente de verdad del saldo; se escribe SIEMPRE vía App\Services\ClientAccountService.
 */
class ClientAccountMovement extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount'        => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /** Tipos de movimiento (espejo del enum de la migración). */
    const TYPE_DEPOSITO_ANTICIPO = 'deposito_anticipo'; // (+) anticipo directo a cuenta
    const TYPE_SOBREPAGO_NOTA    = 'sobrepago_nota';     // (+) excedente de un abono
    const TYPE_DEVOLUCION        = 'devolucion';         // (+) devolución / nota de crédito
    const TYPE_AJUSTE_MANUAL     = 'ajuste_manual';      // (±) corrección del admin
    const TYPE_APLICACION_VENTA  = 'aplicacion_venta';   // (−) se usa el saldo para pagar (Fase 2)

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    /** Usuario admin que registró el movimiento. */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Filtra por tipo de movimiento. */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
