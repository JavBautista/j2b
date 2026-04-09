<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskInfoExtra extends Model
{
    use HasFactory;
    protected $table = 'task_info_extra';
    protected $guarded = [];
    public $timestamps = false;

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
