<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseLog extends Model
{
    protected $guarded=[];
    
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

}
