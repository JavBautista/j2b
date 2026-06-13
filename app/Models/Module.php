<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de módulos vendibles de la plataforma.
 * - is_core = true  → incluido siempre en toda tienda, no se vende suelto.
 * - is_core = false → activable/cobrable por tienda vía pivote shop_modules.
 */
class Module extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_core'     => 'boolean',
        'is_external' => 'boolean',
        'active'      => 'boolean',
        'base_price'  => 'decimal:2',
        'requires'    => 'array',
    ];

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'shop_modules')
            ->withPivot(['enabled', 'price', 'contracted_at', 'expires_at', 'assigned_by_user_id', 'notes'])
            ->withTimestamps();
    }
}
