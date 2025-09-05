<?php
// Script de prueba FCM - J2B
// Ejecutar con: php test_fcm.php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\FcmToken;
use App\Services\FirebaseService;

echo "\n🔍 === TEST FCM - J2B ===\n\n";

// 1. Verificar configuración
echo "1. VERIFICANDO CONFIGURACIÓN:\n";
echo "   Firebase Project ID: " . config('firebase.project_id') . "\n";
echo "   Credentials Path: " . config('firebase.credentials') . "\n";

$credentialsPath = base_path(config('firebase.credentials'));
if (file_exists($credentialsPath)) {
    echo "   ✅ Archivo de credenciales existe\n";
    $credentials = json_decode(file_get_contents($credentialsPath), true);
    echo "   Project ID en credenciales: " . ($credentials['project_id'] ?? 'NO ENCONTRADO') . "\n";
} else {
    echo "   ❌ Archivo de credenciales NO existe\n";
}

echo "\n2. VERIFICANDO USUARIOS Y TOKENS:\n";

// Buscar admin de shop_id = 2 (o la que uses para pruebas)
$shopId = 2;
$adminUsers = User::where('shop_id', $shopId)
    ->whereHas('roles', function($q) {
        $q->whereIn('roles.id', [1, 2]);
    })
    ->get();

echo "   Admins en shop {$shopId}: " . $adminUsers->count() . "\n";
foreach ($adminUsers as $user) {
    echo "   - User ID {$user->id}: {$user->name} ({$user->email})\n";
    
    $tokens = FcmToken::where('user_id', $user->id)->get();
    if ($tokens->count() > 0) {
        echo "     📱 Tokens FCM: {$tokens->count()}\n";
        foreach ($tokens as $token) {
            echo "       • " . substr($token->token, 0, 30) . "... ({$token->device_type})\n";
            echo "         Last used: {$token->last_used_at}\n";
        }
    } else {
        echo "     ⚠️ Sin tokens FCM registrados\n";
    }
}

echo "\n3. PROBANDO ENVÍO FCM:\n";

if ($adminUsers->count() > 0 && FcmToken::whereIn('user_id', $adminUsers->pluck('id'))->exists()) {
    try {
        $firebaseService = app(FirebaseService::class);
        echo "   Enviando notificación de prueba a shop {$shopId}...\n";
        
        $result = $firebaseService->sendToShopAdmins(
            $shopId,
            '🧪 Test FCM',
            'Esta es una prueba desde el script test_fcm.php',
            [
                'type' => 'test',
                'timestamp' => now()->toIso8601String()
            ]
        );
        
        echo "   ✅ Resultado del envío:\n";
        echo "      • Enviados: {$result['sent']}\n";
        echo "      • Fallidos: {$result['failed']}\n";
        echo "      • Total: {$result['total']}\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Error al enviar: " . $e->getMessage() . "\n";
        echo "   Trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "   ⚠️ No hay tokens FCM registrados para probar\n";
}

echo "\n4. VERIFICANDO LOGS:\n";
echo "   Revisa: storage/logs/laravel-" . date('Y-m-d') . ".log\n";
echo "   Busca líneas con: FCM, Firebase, push\n";

echo "\n=== FIN DEL TEST ===\n\n";