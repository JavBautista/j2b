<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'plan_id',
        'user_id',
        'price_without_iva',
        'iva_amount',
        'total_amount',
        'currency',
        'payment_method',
        'transaction_id',
        'invoice_id',
        'billing_period',
        'starts_at',
        'ends_at',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // Relaciones
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
