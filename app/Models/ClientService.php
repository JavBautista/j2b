<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientService extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function client(){
        return $this->belongsTo(Client::class);
    }


    public function logs(){
        return $this->hasMany(TaskLog::class)->orderBy('created_at', 'desc');;
    }
}
