<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceTrackingEvidence extends Model
{
    public $timestamps = false;

    protected $table = 'service_tracking_evidence';

    protected $fillable = [
        'tracking_id',
        'image',
        'caption',
    ];

    public function tracking()
    {
        return $this->belongsTo(TaskServiceTracking::class, 'tracking_id');
    }
}
