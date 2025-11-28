<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    protected $guarded=[];

    protected $casts = [
        'tracking_active' => 'boolean',
        'tracking_started_at' => 'datetime',
        'tracking_finished_at' => 'datetime',
    ];

    public function client(){
        return $this->belongsTo(Client::class);
    }

    public function images(){
        return $this->hasMany(TaskImage::class);
    }

    public function logs(){
        return $this->hasMany(TaskLog::class)->orderBy('created_at', 'desc');;
    }

    // Relación con usuario asignado (técnico)
    public function assignedUser(){
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    // Relación con usuario cliente que solicitó el servicio (cuando origin='client')
    public function requestedBy(){
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    // Relación con histórico de tracking
    public function trackingHistory(){
        return $this->hasMany(TaskTrackingHistory::class);
    }
}
