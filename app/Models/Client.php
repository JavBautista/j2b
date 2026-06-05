<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $guarded=[];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'location_image',
        'location_latitude',
        'location_longitude',
        'active',
        'shop_id',
        'level',
        'user_id',
        'origin'
    ];

    protected $casts = [
        'account_balance' => 'decimal:2',
        'credit_limit'    => 'decimal:2',
    ];

    public function rents(){
        return $this->hasMany(Rent::class);
    }

    public function shop(){
        return $this->belongsTo(Shop::class);
    }

    public function contracts(){
        return $this->hasMany(Contract::class);
    }

    public function addresses(){
        return $this->hasMany(ClientAddress::class);
    }

    public function activeAddresses(){
        return $this->hasMany(ClientAddress::class)->where('active', true);
    }

    public function primaryAddress(){
        return $this->hasOne(ClientAddress::class)->where('is_primary', true);
    }

    public function fiscalData(){
        return $this->hasMany(ClientFiscalData::class)->where('active', true);
    }

    public function defaultFiscalData(){
        return $this->hasOne(ClientFiscalData::class)->where('is_default', true)->where('active', true);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    /** Movimientos de la cuenta corriente (ledger del saldo a favor), más recientes primero. */
    public function accountMovements(){
        return $this->hasMany(ClientAccountMovement::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    /** Saldo a favor disponible (= account_balance cuando es positivo, 0 si hay adeudo). */
    public function saldoAFavor(): float
    {
        return max(0, (float) $this->account_balance);
    }

    /** True si la cuenta del cliente está en negativo (adeudo). */
    public function tieneAdeudo(): bool
    {
        return (float) $this->account_balance < 0;
    }
}
