<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'max_products',
        'max_clients',
        'max_collaborators',
        'max_tasks',
        'max_suppliers',
        'gps_tracking',
        'reports_basic',
        'reports_advanced',
        'whatsapp_integration',
        'email_marketing',
        'custom_branding',
        'api_access',
        'multi_currency',
        'support_level',
    ];

    protected $casts = [
        'gps_tracking' => 'boolean',
        'reports_basic' => 'boolean',
        'reports_advanced' => 'boolean',
        'whatsapp_integration' => 'boolean',
        'email_marketing' => 'boolean',
        'custom_branding' => 'boolean',
        'api_access' => 'boolean',
        'multi_currency' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
