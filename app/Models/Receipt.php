<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;
    protected $guarded=[];

    const STATUS_POR_COBRAR    = 'POR COBRAR';
    const STATUS_PAGADA        = 'PAGADA';
    const STATUS_POR_FACTURAR  = 'POR FACTURAR';
    const STATUS_CANCELADA     = 'CANCELADA';
    const STATUS_DEVOLUCION    = 'DEVOLUCION';
    const STATUS_NUEVA_COMPRA  = 'NUEVA COMPRA';

    public static function statusesValidos(): array
    {
        return [
            self::STATUS_POR_COBRAR,
            self::STATUS_PAGADA,
            self::STATUS_POR_FACTURAR,
            self::STATUS_CANCELADA,
            self::STATUS_DEVOLUCION,
            self::STATUS_NUEVA_COMPRA,
        ];
    }

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
