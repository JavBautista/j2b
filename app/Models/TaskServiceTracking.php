<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskServiceTracking extends Model
{
    public $timestamps = false;

    protected $table = 'task_service_tracking';

    protected $fillable = [
        'task_id',
        'step_id',
        'changed_by_user_id',
        'notes',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function step()
    {
        return $this->belongsTo(ServiceTrackingStep::class, 'step_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
