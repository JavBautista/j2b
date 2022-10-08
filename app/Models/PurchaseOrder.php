<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    public function detail(){
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    public function partialPayments(){
        return $this->hasMany(PurchaseOrderPartialPayments::class);
    }

    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }
}
