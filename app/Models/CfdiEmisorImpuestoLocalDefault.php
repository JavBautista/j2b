<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CfdiEmisorImpuestoLocalDefault extends Model
{
    protected $table = 'cfdi_emisor_impuestos_locales_defaults';

    protected $guarded = [];

    protected $casts = [
        'tasa_porcentaje' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function emisor()
    {
        return $this->belongsTo(CfdiEmisor::class, 'cfdi_emisor_id');
    }
}
