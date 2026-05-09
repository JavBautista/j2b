<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfdiPagoComplementoTax extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tasa' => 'decimal:6',
        'base' => 'decimal:2',
        'importe' => 'decimal:2',
    ];

    public function complemento(): BelongsTo
    {
        return $this->belongsTo(CfdiPagoComplemento::class, 'cfdi_pago_complemento_id');
    }

    public function scopeDoctoRelacionado($q)
    {
        return $q->where('scope', 'dr');
    }

    public function scopePago($q)
    {
        return $q->where('scope', 'p');
    }

    public function scopeTraslados($q)
    {
        return $q->where('tipo', 'traslado');
    }

    public function scopeRetenciones($q)
    {
        return $q->where('tipo', 'retencion');
    }
}
