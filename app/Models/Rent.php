<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rent extends Model
{
    use HasFactory;
    protected $guarded=[];
    public function rentDetail(){
        return $this->hasMany(RentDetail::class);
    }
}
