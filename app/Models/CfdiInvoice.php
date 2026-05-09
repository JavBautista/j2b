<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CfdiInvoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_timbrado' => 'datetime',
        'fecha_cancelacion' => 'datetime',
        'request_json' => 'json',
        'response_json' => 'json',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function emisor()
    {
        return $this->belongsTo(CfdiEmisor::class, 'cfdi_emisor_id');
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function complementos()
    {
        return $this->hasMany(CfdiPagoComplemento::class);
    }

    public function taxes()
    {
        return $this->hasMany(CfdiInvoiceTax::class);
    }

    public function traslados()
    {
        return $this->taxes()->where('tipo', 'traslado');
    }

    public function retenciones()
    {
        return $this->taxes()->where('tipo', 'retencion');
    }

    /**
     * Saldo insoluto = total factura - suma de complementos vigentes
     */
    public function saldoInsoluto(): float
    {
        $pagado = $this->complementos()
            ->where('status', CfdiPagoComplemento::STATUS_VIGENTE)
            ->sum('imp_pagado');
        return max(0, (float) $this->total - (float) $pagado);
    }
}
