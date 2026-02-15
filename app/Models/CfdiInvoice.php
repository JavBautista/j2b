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
}
