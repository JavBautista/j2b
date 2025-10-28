<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTrackingHistory extends Model
{
    protected $table = 'task_tracking_history';

    public $timestamps = false;

    protected $fillable = [
        'shop_id',
        'task_id',
        'assigned_user_id',
        'tracking_date',
        'start_lat',
        'start_lng',
        'start_timestamp',
        'end_lat',
        'end_lng',
        'end_timestamp',
        'gps_points_count',
        'distance_km',
        'duration_minutes',
        'avg_speed_kmh',
        'route_points',
        'firebase_path',
    ];

    protected $casts = [
        'route_points' => 'array',
        'start_timestamp' => 'datetime',
        'end_timestamp' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Relación con tarea
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    // Relación con usuario asignado (técnico)
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    // Relación con tienda
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
