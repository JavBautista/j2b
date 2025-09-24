<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractLog extends Model
{
    protected $fillable = [
        'contract_id',
        'user_id',
        'action',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime'
    ];

    // Relaciones
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Método estático para crear log fácilmente
    public static function log($contractId, $action, $description = null, $oldValues = null, $newValues = null, $userId = null)
    {
        return self::create([
            'contract_id' => $contractId,
            'user_id' => $userId ?: auth()->id(),
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    // Método para obtener el nombre de la acción en español
    public function getActionNameAttribute()
    {
        $actions = [
            'created' => 'Contrato creado',
            'updated' => 'Contrato editado',
            'cancelled' => 'Contrato cancelado',
            'deleted' => 'Contrato eliminado',
            'pdf_generated' => 'PDF generado',
            'viewed' => 'Contrato visualizado',
            'signed' => 'Contrato firmado'
        ];

        return $actions[$this->action] ?? $this->action;
    }

    // Método para obtener el ícono de la acción
    public function getActionIconAttribute()
    {
        $icons = [
            'created' => 'fa-plus text-success',
            'updated' => 'fa-edit text-info',
            'cancelled' => 'fa-ban text-danger',
            'deleted' => 'fa-trash text-danger',
            'pdf_generated' => 'fa-file-pdf-o text-primary',
            'viewed' => 'fa-eye text-muted',
            'signed' => 'fa-pencil text-success'
        ];

        return $icons[$this->action] ?? 'fa-info text-muted';
    }
}
