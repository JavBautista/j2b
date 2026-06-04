<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatMetodoPago extends Model
{
    public $timestamps = false;

    protected $table = 'sat_metodos_pago';

    protected $fillable = ['code', 'description', 'vigente'];

    protected $casts = [
        'vigente' => 'boolean',
    ];
}
