<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CfdiInvoiceImpuestoLocal extends Model
{
    protected $table = 'cfdi_invoice_impuestos_locales';

    protected $guarded = [];

    protected $casts = [
        'tasa_porcentaje' => 'decimal:2',
        'base' => 'decimal:2',
        'importe' => 'decimal:2',
    ];

    public function cfdiInvoice()
    {
        return $this->belongsTo(CfdiInvoice::class);
    }
}
