<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadLead extends Model
{
    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
    ];
}
