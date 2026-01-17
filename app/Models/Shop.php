<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;
    protected $guarded=[];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'is_trial' => 'boolean',
        'active' => 'boolean',
        'is_exempt' => 'boolean',
    ];

    public function extraFields()
    {
        return $this->hasMany(ExtraFieldShop::class);
    }

    public function contractTemplates()
    {
        return $this->hasMany(ContractTemplate::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // Relaciones de suscripción
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function planFeatures()
    {
        return $this->hasOneThrough(
            PlanFeature::class,
            Plan::class,
            'id',           // Foreign key en plans
            'plan_id',      // Foreign key en plan_features
            'plan_id',      // Local key en shops
            'id'            // Local key en plans
        );
    }

    /**
     * Verifica si el shop está activo (no bloqueado)
     */
    public function isActive()
    {
        // Si está en trial y no ha vencido
        if ($this->is_trial && $this->trial_ends_at && $this->trial_ends_at > now()) {
            return true;
        }

        // Si tiene suscripción activa y no ha vencido
        if ($this->subscription_status === 'active' && $this->subscription_ends_at && $this->subscription_ends_at > now()) {
            return true;
        }

        // Si está en periodo de gracia
        if ($this->subscription_status === 'grace_period' && $this->grace_period_ends_at && $this->grace_period_ends_at > now()) {
            return true;
        }

        return false;
    }

    /**
     * Días restantes de suscripción (entero)
     */
    public function daysRemaining()
    {
        if ($this->is_trial && $this->trial_ends_at) {
            return (int) now()->diffInDays($this->trial_ends_at, false);
        }

        if ($this->subscription_ends_at) {
            return (int) now()->diffInDays($this->subscription_ends_at, false);
        }

        return 0;
    }

    /**
     * Obtener precio efectivo segun ciclo de facturacion
     * Prioridad: precio de la tienda > precio del plan
     */
    public function getEffectivePrice($cycle = null)
    {
        $cycle = $cycle ?? $this->billing_cycle ?? 'monthly';

        if ($cycle === 'yearly') {
            // Precio anual: tienda > plan > mensual * 12
            return $this->yearly_price
                ?? $this->plan?->yearly_price
                ?? ($this->getEffectivePrice('monthly') * 12);
        }

        // Precio mensual: tienda > plan
        return $this->monthly_price ?? $this->plan?->price ?? 0;
    }

    /**
     * Verificar si la tienda paga anualmente
     */
    public function isYearlyBilling()
    {
        return $this->billing_cycle === 'yearly';
    }

    /**
     * Accessor para obtener la URL pública de la firma del representante legal
     */
    public function getLegalRepresentativeSignatureUrlAttribute()
    {
        if ($this->legal_representative_signature_path) {
            return asset('storage/' . $this->legal_representative_signature_path);
        }
        return null;
    }
}
