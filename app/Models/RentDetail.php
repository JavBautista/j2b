<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentDetail extends Model
{
    use HasFactory;
    protected $guarded=[];



    public function consumables(){
        return $this->hasMany(Consumables::class);
    }

    public function images(){
        return $this->hasMany(RentDetailImage::class);
    }

}
