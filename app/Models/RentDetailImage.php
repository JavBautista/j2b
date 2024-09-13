<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentDetailImage extends Model
{
    use HasFactory;
    public $timestamps=false;
    protected $guarded=[];

    public function rentDetail()
    {
        return $this->belongsTo(RentDetail::class);
    }
}
