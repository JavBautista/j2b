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

    public function shop(){
        return $this->belongsTo(Shop::class);
    }

    public function infoExtra()
    {
        return $this->hasMany(ReceiptInfoExtra::class, 'receipt_id');
    }

    public function cfdiInvoice()
    {
        return $this->hasOne(CfdiInvoice::class);
    }
}
