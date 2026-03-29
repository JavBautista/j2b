<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceTrackingStep extends Model
{
    protected $fillable = [
        'shop_id',
        'name',
        'description',
        'color',
        'icon',
        'sort_order',
        'is_initial',
        'is_final',
        'active',
    ];

    protected $casts = [
        'is_initial' => 'boolean',
        'is_final' => 'boolean',
        'active' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function trackingEntries()
    {
        return $this->hasMany(TaskServiceTracking::class, 'step_id');
    }
}
