<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopAiSettings extends Model
{
    use HasFactory;

    protected $table = 'shop_ai_settings';

    protected $fillable = [
        'shop_id',
        'system_prompt',
        'last_embedding_sync',
    ];

    protected $casts = [
        'last_embedding_sync' => 'datetime',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
