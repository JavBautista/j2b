<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentConsignmentItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function consignment()
    {
        return $this->belongsTo(RentConsignment::class, 'rent_consignment_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function qtyPendienteDevolucion(): int
    {
        return max(0, (int) $this->qty - (int) $this->qty_returned);
    }
}
