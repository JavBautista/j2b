<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopReceiptSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'show_qr' => 'boolean',
        'show_logo' => 'boolean',
        'show_signature' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
