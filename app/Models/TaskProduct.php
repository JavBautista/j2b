<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskProduct extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
        'cost' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function deliveredBy()
    {
        return $this->belongsTo(User::class, 'delivered_by_user_id');
    }
}
