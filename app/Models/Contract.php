<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'client_id', 'contract_template_id', 'contract_data', 'contract_content', 'pdf_path', 'signature_path', 'status', 'start_date', 'expiration_date', 'cancelled_at', 'cancellation_reason', 'cancelled_by'
    ];

    protected $casts = [
        'contract_data' => 'array',
        'start_date' => 'date',
        'expiration_date' => 'date',
        'cancelled_at' => 'datetime'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function template()
    {
        return $this->belongsTo(ContractTemplate::class, 'contract_template_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['draft', 'generated', 'sent']);
    }

    public function logs()
    {
        return $this->hasMany(ContractLog::class)->orderBy('created_at', 'desc');
    }
}
