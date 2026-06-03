<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatUsoCfdi extends Model
{
    public $timestamps = false;

    protected $table = 'sat_usos_cfdi';

    protected $fillable = ['code', 'description', 'aplica_fisica', 'aplica_moral', 'vigente'];

    protected $casts = [
        'aplica_fisica' => 'boolean',
        'aplica_moral'  => 'boolean',
        'vigente'       => 'boolean',
    ];
}
