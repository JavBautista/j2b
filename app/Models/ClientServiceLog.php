<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientServiceLog extends Model
{
    use HasFactory;

    protected $guarded=[];

    public function clientService()
    {
        return $this->belongsTo(ClientService::class);
    }
}
