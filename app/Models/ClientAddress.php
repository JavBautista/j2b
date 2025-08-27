<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAddress extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'client_id',
        'name',
        'address',
        'num_ext',
        'num_int',
        'colony',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'location_image',
        'description',
        'phone',
        'email',
        'is_primary',
        'active'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_primary' => 'boolean',
        'active' => 'boolean'
    ];

    // Relación con Client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Scope para direcciones activas
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    // Scope para dirección principal
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    // Accessor para dirección completa
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->num_ext,
            $this->colony,
            $this->city,
            $this->state,
            $this->postal_code
        ]);
        
        return implode(', ', $parts);
    }
}
