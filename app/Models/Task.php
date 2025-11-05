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

    // Agregar tracking_history al JSON cuando está cargada
    protected $appends = ['tracking_history'];

    // Accessor para incluir tracking_history en serialización JSON
    public function getTrackingHistoryAttribute()
    {
        // Solo incluir si la relación fue cargada con eager loading
        if ($this->relationLoaded('trackingHistory')) {
            return $this->trackingHistory;
        }
        return null;
    }

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

    // Relación con histórico de tracking
    public function trackingHistory(){
        return $this->hasMany(TaskTrackingHistory::class);
    }
}
