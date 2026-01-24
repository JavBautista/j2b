<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'is_whatsapp',
        'company',
        'message',
        'is_read',
        'read_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'is_whatsapp' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== SCOPES ====================

    /**
     * Filtrar solo mensajes no leídos
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Filtrar solo mensajes leídos
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Ordenar por más recientes
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ==================== MÉTODOS ====================

    /**
     * Marcar como leído
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Marcar como no leído
     */
    public function markAsUnread(): bool
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Obtener teléfono formateado
     */
    public function getFormattedPhoneAttribute(): string
    {
        $phone = $this->phone;
        if (strlen($phone) === 10) {
            return sprintf('%s %s %s',
                substr($phone, 0, 2),
                substr($phone, 2, 4),
                substr($phone, 6, 4)
            );
        }
        return $phone;
    }

    /**
     * Obtener link de WhatsApp si aplica
     */
    public function getWhatsappLinkAttribute(): ?string
    {
        if ($this->is_whatsapp && $this->phone) {
            return 'https://wa.me/52' . $this->phone;
        }
        return null;
    }
}
