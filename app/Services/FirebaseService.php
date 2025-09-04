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
        // Obtener todos los admins de la tienda con tokens FCM
        $adminTokens = FcmToken::whereHas('user', function($query) use ($shopId) {
            $query->where('shop_id', $shopId)
                  ->whereHas('roles', function($q) {
                      $q->whereIn('roles.id', [1, 2]); // admin/superadmin - especificar tabla
                  });
        })->pluck('token')->toArray();

        if (empty($adminTokens)) {
            Log::info("No FCM tokens found for shop {$shopId} admins");
            return false;
        }

        $notification = Notification::create($title, $body);

        foreach ($adminTokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification)
                    ->withData($data);

                $this->messaging->send($message);
            } catch (\Exception $e) {
                Log::error("FCM failed for shop {$shopId}: " . $e->getMessage());
            }
        }

        Log::info("FCM sent to " . count($adminTokens) . " admins of shop {$shopId}");
        return true;
    }
}