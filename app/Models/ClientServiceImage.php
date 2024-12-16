<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientServiceImage extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function ClientService()
    {
        return $this->belongsTo(ClientService::class);
    }
}
