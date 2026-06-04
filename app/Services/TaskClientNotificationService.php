<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Notification;
use App\Models\ServiceTrackingStep;
use App\Services\NotificationFcmService;
use Illuminate\Support\Facades\Log;

/**
 * Notifica al CLIENTE (usuario-app) los cambios de estatus de su servicio.
 *
 * Solo aplica cuando:
 *  - La tarea fue solicitada por el propio cliente (origin = 'client').
 *  - Existe el usuario-app solicitante (requested_by_user_id).
 *  - El estatus de seguimiento tiene activo el flag notify_client.
 *
 * Envía notificación in-app (bandeja) + push FCM. El push va aislado en
 * try/catch para nunca interrumpir el cambio de estatus si Firebase falla.
 *
 * Se usa desde el avance de paso tanto en API (Ionic admin) como en web.
 */
class TaskClientNotificationService
{
    private $fcmService;

    public function __construct(NotificationFcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    public function notifyStatusChange(Task $task, ServiceTrackingStep $step): void
    {
        // Filtros: solo servicios solicitados por el cliente y estatus marcado para avisar
        if ($task->origin !== 'client' || !$task->requested_by_user_id || !$step->notify_client) {
            return;
        }

        $clientUserId = $task->requested_by_user_id;

        // 1. Notificación in-app (bandeja del cliente)
        $new_ntf = new Notification();
        $new_ntf->user_id     = $clientUserId;
        $new_ntf->description = 'Tu servicio "' . $task->title . '" ahora está: ' . $step->name;
        $new_ntf->type        = 'task';
        $new_ntf->action      = 'task_id';
        $new_ntf->data        = $task->id;
        $new_ntf->read        = 0;
        $new_ntf->visible     = 1;
        $new_ntf->save();

        // 2. Push FCM (app cerrada). Aislado para no romper el cambio de estatus.
        try {
            $result = $this->fcmService->taskStatusChanged(
                $clientUserId,
                $task->title,
                $step->name,
                $task->id
            );

            Log::info('📱 FCM: Cambio de estatus notificado al cliente', [
                'task_id' => $task->id,
                'client_user_id' => $clientUserId,
                'step' => $step->name,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ FCM: Error notificando cambio de estatus al cliente', [
                'task_id' => $task->id,
                'client_user_id' => $clientUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
