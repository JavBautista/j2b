<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatRegimenFiscal extends Model
{
    public $timestamps = false;

    protected $table = 'sat_regimenes_fiscales';

    protected $fillable = ['code', 'description', 'aplica_fisica', 'aplica_moral', 'aplica_emisor', 'vigente'];

    protected $casts = [
        'aplica_fisica' => 'boolean',
        'aplica_moral'  => 'boolean',
        'aplica_emisor' => 'boolean',
        'vigente'       => 'boolean',
    ];

    /**
     * Usos CFDI compatibles con este régimen (matriz SAT régimen→uso).
     * Relación por `code` a través de la tabla pivot sat_regimen_uso.
     */
    public function usos()
    {
        return $this->belongsToMany(
            SatUsoCfdi::class,
            'sat_regimen_uso',
            'regimen_code',
            'uso_code',
            'code',
            'code'
        );
    }
}
