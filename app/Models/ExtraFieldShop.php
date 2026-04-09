<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraFieldShop extends Model
{
    use HasFactory;

    protected $table = 'extra_fields_shops';

    protected $guarded=[];

    protected $casts = [
        'active' => 'boolean',
        'filterable' => 'boolean',
        'apply_to_receipts' => 'boolean',
        'apply_to_tasks' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeForReceipts($query)
    {
        return $query->where('apply_to_receipts', true);
    }

    public function scopeForTasks($query)
    {
        return $query->where('apply_to_tasks', true);
    }
}
