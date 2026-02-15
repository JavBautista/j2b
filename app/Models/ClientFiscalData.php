<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientFiscalData extends Model
{
    protected $table = 'client_fiscal_data';

    protected $fillable = [
        'client_id',
        'rfc',
        'razon_social',
        'regimen_fiscal',
        'uso_cfdi',
        'codigo_postal',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
