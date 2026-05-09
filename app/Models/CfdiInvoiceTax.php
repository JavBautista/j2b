<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfdiInvoiceTax extends Model
{
    protected $guarded = [];

    protected $casts = [
        'concepto_index' => 'integer',
        'tasa' => 'decimal:6',
        'base' => 'decimal:2',
        'importe' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CfdiInvoice::class, 'cfdi_invoice_id');
    }

    public function scopeTraslados($q)
    {
        return $q->where('tipo', 'traslado');
    }

    public function scopeRetenciones($q)
    {
        return $q->where('tipo', 'retencion');
    }

    public function scopeGlobales($q)
    {
        return $q->whereNull('concepto_index');
    }

    public function scopePorConcepto($q)
    {
        return $q->whereNotNull('concepto_index');
    }
}
