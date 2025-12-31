<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartialPayments extends Model
{
    use HasFactory;

    protected $fillable = ['receipt_id', 'amount', 'payment_type', 'payment_date'];

    /**
     * Relación con Receipt
     */
    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }
}
