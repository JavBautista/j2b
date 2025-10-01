<?php

namespace App\Services;

use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

/**
 * Servicio estandarizado para notificaciones FCM
 * USO SIMPLE: app(NotificationFcmService::class)->sendToShopAdmins($shopId, $title, $message)
 */
class NotificationFcmService
{
    private $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Envía notificación FCM a todos los admins de una tienda
     *
     * @param int $shopId ID de la tienda
     * @param string $title Título de la notificación
     * @param string $message Mensaje de la notificación
     * @param string $type Tipo de notificación (ej: 'task_completed', 'service_request', etc.)
     * @param array $extraData Datos adicionales opcionales
     * @return array Resultado del envío
     */
    public function sendToShopAdmins($shopId, $title, $message, $type = 'general', $extraData = [])
    {
        Log::info("🚀 FCM: Enviando notificación estandarizada", [
            'shop_id' => $shopId,
            'type' => $type,
            'title' => $title
        ]);

        try {
            // Preparar datos base
            $data = [
                'type' => $type,
                'shop_id' => (string) $shopId,
                'timestamp' => now()->toIso8601String(),
            ];

            // Agregar datos extra si existen
            if (!empty($extraData)) {
                $data = array_merge($data, $extraData);
            }

            // Enviar usando FirebaseService existente
            $result = $this->firebaseService->sendToShopAdmins($shopId, $title, $message, $data);

            Log::info("✅ FCM: Notificación enviada exitosamente", [
                'shop_id' => $shopId,
                'type' => $type,
                'sent' => $result['sent'],
                'failed' => $result['failed']
            ]);

            return [
                'success' => true,
                'sent' => $result['sent'],
                'failed' => $result['failed'],
                'total' => $result['total']
            ];

        } catch (\Exception $e) {
            Log::error("❌ FCM: Error en servicio estandarizado", [
                'shop_id' => $shopId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sent' => 0,
                'failed' => 0,
                'total' => 0
            ];
        }
    }

    /**
     * Envía notificación FCM a un usuario específico
     *
     * @param int $userId ID del usuario
     * @param string $title Título de la notificación
     * @param string $message Mensaje de la notificación
     * @param string $type Tipo de notificación
     * @param array $extraData Datos adicionales opcionales
     * @return array Resultado del envío
     */
    public function sendToUser($userId, $title, $message, $type = 'general', $extraData = [])
    {
        Log::info("🚀 FCM: Enviando notificación a usuario", [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title
        ]);

        try {
            // Preparar datos base
            $data = [
                'type' => $type,
                'user_id' => (string) $userId,
                'timestamp' => now()->toIso8601String(),
            ];

            // Agregar datos extra si existen
            if (!empty($extraData)) {
                $data = array_merge($data, $extraData);
            }

            // Enviar usando FirebaseService existente
            $result = $this->firebaseService->sendToUser($userId, $title, $message, $data);

            Log::info("✅ FCM: Notificación a usuario enviada", [
                'user_id' => $userId,
                'type' => $type,
                'success' => $result
            ]);

            return [
                'success' => $result,
                'user_id' => $userId
            ];

        } catch (\Exception $e) {
            Log::error("❌ FCM: Error enviando a usuario", [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'user_id' => $userId
            ];
        }
    }

    /**
     * Métodos de conveniencia para casos comunes
     */

    public function taskCompleted($shopId, $taskTitle, $employeeName, $taskId = null)
    {
        return $this->sendToShopAdmins(
            $shopId,
            '✅ Tarea Completada',
            "Tarea completada por {$employeeName}: {$taskTitle}",
            'task_completed',
            $taskId ? ['task_id' => (string) $taskId] : []
        );
    }

    public function serviceRequest($shopId, $clientName, $serviceTitle, $serviceId = null)
    {
        return $this->sendToShopAdmins(
            $shopId,
            '🔧 Nueva Solicitud de Servicio',
            "Solicitud de Servicio: {$clientName}: {$serviceTitle}",
            'service_request',
            $serviceId ? ['service_id' => (string) $serviceId] : []
        );
    }

    public function paymentReminder($shopId, $clientName, $amount, $contractId = null)
    {
        return $this->sendToShopAdmins(
            $shopId,
            '💰 Recordatorio de Pago',
            "Pago pendiente: {$clientName} - ${$amount}",
            'payment_reminder',
            $contractId ? ['contract_id' => (string) $contractId] : []
        );
    }
}