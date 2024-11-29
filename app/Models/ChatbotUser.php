<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotUser extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'email',
        'password',
        'api_token', // Si usas tokens personalizados
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
