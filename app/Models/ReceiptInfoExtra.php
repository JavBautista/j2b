<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptInfoExtra extends Model
{
    use HasFactory;
    protected $table = 'receipt_info_extra';
    protected $guarded=[];
    public $timestamps=false;

    public function receipt()
    {
        return $this->belongsTo(Receipt::class, 'receipt_id');
    }
}
