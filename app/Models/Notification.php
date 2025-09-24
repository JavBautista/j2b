<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notification extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'notification_group_id',
        'user_id',
        'description',
        'action',
        'type',
        'data',
        'read',
        'visible'
    ];

    protected $casts = [
        'read' => 'boolean',
        'visible' => 'boolean'
    ];

    // Scope para notificaciones visibles
    public function scopeVisible($query)
    {
        return $query->where('visible', true);
    }

    // Scope para notificaciones no leídas
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    // Scope para agrupar por notification_group_id
    public function scopeGrouped($query)
    {
        return $query->whereNotNull('notification_group_id');
    }

    // Relación con User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Método helper para generar notification_group_id único
    public static function generateGroupId()
    {
        return Str::uuid()->toString();
    }

    // Método para marcar como leídas todas las notificaciones de un grupo
    public static function markGroupAsRead($groupId)
    {
        // Debug: Log antes de la actualización
        \Log::info("markGroupAsRead: Iniciando para grupo $groupId");

        // Verificar qué notificaciones encontramos antes de actualizar
        $notifications = self::where('notification_group_id', $groupId)->get();
        \Log::info("markGroupAsRead: Encontradas " . $notifications->count() . " notificaciones");

        foreach ($notifications as $notif) {
            \Log::info("markGroupAsRead: ID={$notif->id}, USER_ID={$notif->user_id}, READ={$notif->read}");
        }

        // Ejecutar la actualización
        $updated = self::where('notification_group_id', $groupId)->update(['read' => true]);

        \Log::info("markGroupAsRead: Actualizado $updated registros");

        return $updated;
    }

    // Método para ocultar todas las notificaciones de un grupo (soft delete)
    public static function hideGroup($groupId)
    {
        return self::where('notification_group_id', $groupId)->update(['visible' => false]);
    }

    // Método para obtener estadísticas de un grupo
    public static function getGroupStats($groupId)
    {
        $notifications = self::where('notification_group_id', $groupId)->visible()->get();
        
        return [
            'total' => $notifications->count(),
            'read' => $notifications->where('read', true)->count(),
            'unread' => $notifications->where('read', false)->count(),
        ];
    }
}
