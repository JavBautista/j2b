<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatFormaPago extends Model
{
    public $timestamps = false;

    protected $table = 'sat_formas_pago';

    protected $fillable = ['code', 'description', 'vigente'];

    protected $casts = [
        'vigente' => 'boolean',
    ];
}
