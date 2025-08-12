<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'client_id', 'contract_template_id', 'contract_data', 'contract_content', 'pdf_path', 'signature_path', 'status'
    ];

    protected $casts = [
        'contract_data' => 'array'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function template()
    {
        return $this->belongsTo(ContractTemplate::class, 'contract_template_id');
    }
}
