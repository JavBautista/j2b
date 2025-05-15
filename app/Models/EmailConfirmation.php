<?php

namespace App\Models;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;


use Illuminate\Database\Eloquent\Model;

class EmailConfirmation extends Model
{
    protected $fillable = [
        'name',
        'shop',
        'phone',
        'email',
        'password',
        'avatar',
        'token',
        'expires_at',
    ];

    // Genera token único automáticamente si no se asigna
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->token = Str::uuid();
            $model->expires_at = Carbon::now()->addHours(24);
        });
    }

    protected $dates = ['expires_at'];
}
