<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatProductCode extends Model
{
    public $timestamps = false;

    protected $fillable = ['code', 'description'];
}
