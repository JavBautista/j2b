<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartialPayments extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_id',
        'amount',
        'payment_type',
        'payment_date',
        'payment_method',
        'shop_bank_account_id',
        'bank_ord_code',
        'cta_ordenante',
        'is_foreign_bank_ord',
        'num_operacion',
    ];

    protected $casts = [
        'is_foreign_bank_ord' => 'boolean',
    ];

    /**
     * Forma de pago bancarizada según catálogo c_FormaPago SAT.
     * Determina si aplica capturar/enviar campos bancarios al complemento.
     */
    public function isBancarizada(): bool
    {
        return in_array($this->payment_method, ['02','03','04','05','06','28','29'], true);
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(ShopBankAccount::class, 'shop_bank_account_id');
    }
}
