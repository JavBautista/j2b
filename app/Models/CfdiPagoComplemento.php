<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CfdiPagoComplemento extends Model
{
    protected $table = 'cfdi_pago_complementos';

    protected $guarded = [];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_timbrado' => 'datetime',
        'fecha_cancelacion' => 'datetime',
        'request_json' => 'json',
        'response_json' => 'json',
        'consolidated_partial_payment_ids' => 'array',
    ];

    const STATUS_PENDING   = 'pending';
    const STATUS_VIGENTE   = 'vigente';
    const STATUS_CANCELADO = 'cancelado';
    const STATUS_FAILED    = 'failed';

    /**
     * True si este complemento abarca varios abonos previos consolidados.
     */
    public function isConsolidated(): bool
    {
        return is_array($this->consolidated_partial_payment_ids)
            && count($this->consolidated_partial_payment_ids) > 0;
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function cfdiInvoice()
    {
        return $this->belongsTo(CfdiInvoice::class);
    }

    public function partialPayment()
    {
        return $this->belongsTo(PartialPayments::class);
    }
}
