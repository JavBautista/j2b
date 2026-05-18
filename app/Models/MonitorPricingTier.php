<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorPricingTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_equipment',
        'max_equipment',
        'price_per_equipment',
        'is_flat_rate',
        'flat_amount',
        'includes_base_plan',
        'currency',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'min_equipment' => 'integer',
        'max_equipment' => 'integer',
        'price_per_equipment' => 'decimal:2',
        'is_flat_rate' => 'boolean',
        'flat_amount' => 'decimal:2',
        'includes_base_plan' => 'boolean',
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function applies(int $equipmentCount): bool
    {
        if ($equipmentCount < $this->min_equipment) {
            return false;
        }

        if ($this->max_equipment === null) {
            return true;
        }

        return $equipmentCount <= $this->max_equipment;
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'monitor_tier_id');
    }
}
