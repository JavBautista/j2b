<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcmToken extends Model
{
    protected $fillable = [
        'user_id', 'token', 'device_type', 'last_used_at'
    ];

    protected $dates = ['last_used_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
