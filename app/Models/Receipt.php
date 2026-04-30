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
        return $this->hasOne(CfdiInvoice::class)->where('status', 'vigente');
    }

    public function cfdiInvoices()
    {
        return $this->hasMany(CfdiInvoice::class);
    }

    /**
     * Abonos previos al timbrado PPD que no tienen forma de pago SAT real.
     * Estos requieren que el usuario decida (separar/consolidar) antes de
     * que el complemento se mande al SAT, porque la forma '99' es rechazada.
     *
     * @return \Illuminate\Database\Eloquent\Collection<PartialPayments>
     */
    public function getAbonosPreviosPendientesMetodo()
    {
        return $this->partialPayments()
            ->where('amount', '>', 0)
            ->where(function ($q) {
                $q->whereNull('payment_method')->orWhere('payment_method', '99');
            })
            ->orderBy('id')
            ->get();
    }
}
