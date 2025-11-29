<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptDetail extends Model
{
    use HasFactory;
    protected $guarded=[];
    public $timestamps=false;

    /**
     * Relación con el producto (solo para type='product')
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
