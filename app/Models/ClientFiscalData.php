<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
        'email',
        'nickname',
        'is_default',
        'active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'active'     => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoices()
    {
        return $this->hasMany(CfdiInvoice::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('active', true);
    }
}
