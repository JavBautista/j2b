<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $guarded=[];

    public function attachments()
    {
        return $this->hasMany(ExpenseAttachment::class);
    }

    public function logs()
    {
        return $this->hasMany(ExpenseLog::class);
    }

}
