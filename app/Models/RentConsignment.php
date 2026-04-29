<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentConsignment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'delivery_date' => 'date',
        'signed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    const STATUS_VIGENTE = 'vigente';
    const STATUS_CANCELADA = 'cancelada';

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function rent()
    {
        return $this->belongsTo(Rent::class);
    }

    public function items()
    {
        return $this->hasMany(RentConsignmentItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function folioCompleto(): string
    {
        return 'CSG-' . str_pad((string) $this->folio, 3, '0', STR_PAD_LEFT);
    }

    public function totalUnidades(): int
    {
        return (int) $this->items()->sum('qty');
    }

    public function estaFirmada(): bool
    {
        return $this->signed_at !== null;
    }

    public function estaVigente(): bool
    {
        return $this->status === self::STATUS_VIGENTE;
    }
}
