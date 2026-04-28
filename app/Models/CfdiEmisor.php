<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CfdiEmisor extends Model
{
    protected $table = 'cfdi_emisores';

    protected $guarded = [];

    protected $casts = [
        'password' => 'encrypted',
        'hub_response' => 'json',
        'is_registered' => 'boolean',
        'active' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function invoices()
    {
        return $this->hasMany(CfdiInvoice::class);
    }

    /**
     * Timbres disponibles (asignados - usados)
     */
    public function timbresDisponibles(): int
    {
        return $this->timbres_asignados - $this->timbres_usados;
    }

    /**
     * Incrementa folio_actual atómicamente y retorna el siguiente folio
     */
    public function siguienteFolio(): int
    {
        $this->increment('folio_actual');
        return $this->folio_actual;
    }

    /**
     * Revierte el folio si el timbrado falló (decrementa folio_actual)
     */
    public function revertirFolio(): void
    {
        $this->decrement('folio_actual');
    }

    /**
     * Incrementa folio_complemento_actual atómicamente y retorna el siguiente folio de complemento
     */
    public function siguienteFolioComplemento(): int
    {
        $this->increment('folio_complemento_actual');
        return $this->folio_complemento_actual;
    }

    /**
     * Revierte el folio de complemento si el timbrado falló
     */
    public function revertirFolioComplemento(): void
    {
        $this->decrement('folio_complemento_actual');
    }
}
