<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function features()
    {
        return $this->hasOne(PlanFeature::class);
    }

    public function shops()
    {
        return $this->hasMany(Shop::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Calcular precio sin IVA
     */
    public function calculatePriceWithoutIva()
    {
        return round($this->price / (1 + ($this->iva_percentage / 100)), 2);
    }

    /**
     * Calcular monto del IVA
     */
    public function calculateIvaAmount()
    {
        return round($this->price - $this->calculatePriceWithoutIva(), 2);
    }
}
