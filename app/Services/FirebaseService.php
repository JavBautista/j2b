<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(config('firebase.credentials'));
        $this->messaging = $factory->createMessaging();
    }

    public function sendToUser($userId, $title, $body, $data = [])
    {
        $tokens = FcmToken::where('user_id', $userId)->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::info("No FCM tokens found for user {$userId}");
            return false;
        }

        $notification = Notification::create($title, $body);

        foreach ($tokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification)
                    ->withData($data);

                $this->messaging->send($message);
                Log::info("FCM sent to user {$userId}");
            } catch (\Exception $e) {
                Log::error("FCM failed for user {$userId}: " . $e->getMessage());
            }
        }

        return true;
    }

    public function sendToShopAdmins($shopId, $title, $body, $data = [])
    {
        Log::info("🔍 FCM: Buscando tokens para shop {$shopId}");
        
        // Primero verificar los usuarios de la tienda
        $shopUsers = \App\Models\User::where('shop_id', $shopId)
            ->whereHas('roles', function($q) {
                $q->whereIn('roles.id', [1, 2]); // admin/superadmin
            })
            ->get();
            
        Log::info("👥 FCM: Usuarios admin encontrados en shop {$shopId}", [
            'count' => $shopUsers->count(),
            'user_ids' => $shopUsers->pluck('id')->toArray()
        ]);
        
        // Obtener todos los admins de la tienda con tokens FCM
        $adminTokens = FcmToken::whereHas('user', function($query) use ($shopId) {
            $query->where('shop_id', $shopId)
                  ->whereHas('roles', function($q) {
                      $q->whereIn('roles.id', [1, 2]); // admin/superadmin - especificar tabla
                  });
        })->get();
        
        Log::info("📱 FCM: Tokens encontrados para shop {$shopId}", [
            'count' => $adminTokens->count(),
            'tokens' => $adminTokens->map(function($token) {
                return [
                    'user_id' => $token->user_id,
                    'token_preview' => substr($token->token, 0, 20) . '...',
                    'device_type' => $token->device_type,
                    'last_used_at' => $token->last_used_at
                ];
            })->toArray()
        ]);

        if ($adminTokens->isEmpty()) {
            Log::warning("⚠️ FCM: No se encontraron tokens FCM para admins de shop {$shopId}");
            return ['sent' => 0, 'failed' => 0, 'message' => 'No FCM tokens found'];
        }

        $notification = Notification::create($title, $body);
        $sent = 0;
        $failed = 0;

        foreach ($adminTokens as $fcmToken) {
            try {
                Log::info("📤 FCM: Enviando a usuario {$fcmToken->user_id}", [
                    'token_preview' => substr($fcmToken->token, 0, 20) . '...'
                ]);
                
                $message = CloudMessage::withTarget('token', $fcmToken->token)
                    ->withNotification($notification)
                    ->withData($data);

                $result = $this->messaging->send($message);
                $sent++;
                
                Log::info("✅ FCM: Enviado exitosamente a usuario {$fcmToken->user_id}", [
                    'result' => $result
                ]);
            } catch (\Exception $e) {
                $failed++;
                Log::error("❌ FCM: Error enviando a usuario {$fcmToken->user_id}", [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]);
            }
        }

        Log::info("📊 FCM: Resumen de envío para shop {$shopId}", [
            'sent' => $sent,
            'failed' => $failed,
            'total' => $adminTokens->count()
        ]);
        
        return [
            'sent' => $sent, 
            'failed' => $failed,
            'total' => $adminTokens->count()
        ];
    }
}