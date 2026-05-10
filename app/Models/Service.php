<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $guarded=[];

    protected $casts = [
        'aplica_retencion_default' => 'boolean',
    ];

    public function shop(){
        return $this->belongsTo(Shop::class);
    }

}
