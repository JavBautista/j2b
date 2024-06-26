<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function client(){
        return $this->belongsTo(Client::class);
    }

    public function images(){
        return $this->hasMany(TaskImage::class);
    }

    public function logs(){
        return $this->hasMany(TaskLog::class)->orderBy('created_at', 'desc');;
    }
}
