<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AI\GroqService;
use Illuminate\Support\Facades\Log;

/**
 * API Controller para Chat IA
 * Usado por la app Ionic (autenticado con JWT)
 */
class ApiAIChatController extends Controller
{
    protected GroqService $aiService;

    public function __construct(GroqService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Procesar mensaje de chat con IA
     * POST /api/auth/ia/chat
     */
    public function chat(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|min:3|max:2000'
        ]);

        $user = auth()->user();
        $prompt = $request->input('prompt');

        Log::info('API IA Chat Request', [
            'user_id' => $user->id,
            'shop_id' => $user->shop_id,
            'prompt_length' => strlen($prompt)
        ]);

        // Preparar mensajes con contexto de J2Biznes
        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt($user)
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];

        // Enviar a Groq
        $result = $this->aiService->chat($messages);

        if ($result['success']) {
            return response()->json([
                'ok' => true,
                'response' => $result['content'],
                'usage' => $result['usage'],
                'model' => $result['model'] ?? 'unknown'
            ]);
        }

        Log::error('API IA Chat Error', [
            'user_id' => $user->id,
            'error' => $result['error']
        ]);

        return response()->json([
            'ok' => false,
            'error' => $result['error'] ?? 'Error al procesar la solicitud'
        ], 500);
    }

    /**
     * Obtener system prompt personalizado
     */
    private function getSystemPrompt($user): string
    {
        $shopName = $user->shop->name ?? 'tu negocio';

        return "Eres un asistente inteligente para J2Biznes, un sistema de gestion de negocios.
Estas ayudando a un usuario de la tienda '{$shopName}'.
Ayudas con consultas sobre el sistema, ventas, inventario, clientes, tareas y reportes.
Responde de forma concisa, profesional y en espanol.
Si no sabes algo especifico del negocio del usuario, indica que puedes ayudar con informacion general del sistema.";
    }

    /**
     * Test de conexion con IA
     * GET /api/auth/ia/test
     */
    public function test()
    {
        $result = $this->aiService->testConnection();

        return response()->json([
            'ok' => $result['success'],
            'message' => $result['message'] ?? $result['error'],
            'model' => $result['model'] ?? null
        ]);
    }
}
