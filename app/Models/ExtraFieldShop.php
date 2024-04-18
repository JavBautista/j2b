<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraFieldShop extends Model
{
    use HasFactory;

    protected $table = 'extra_fields_shops';

    protected $guarded=[];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
