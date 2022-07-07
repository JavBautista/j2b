<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function detail(){
        return $this->hasMany(ReceiptDetail::class);
    }

    public function partialPayments(){
        return $this->hasMany(PartialPayments::class);
    }

    public function client(){
        return $this->belongsTo(Client::class);
    }
}
