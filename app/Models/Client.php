<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function rents(){
        return $this->hasMany(Rent::class);
    }

    public function shop(){
        return $this->belongsTo(Shop::class);
    }
}
