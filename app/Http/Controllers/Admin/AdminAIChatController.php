<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AI\GroqService;

class AdminAIChatController extends Controller
{
    protected GroqService $aiService;

    public function __construct(GroqService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Procesar chat con IA para Admin
     *
     * Versión básica sin RAG ni integración con BD
     * Solo chat directo con Groq AI
     */
    public function chat(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|min:3|max:2000'
        ]);

        $prompt = $request->input('prompt');

        // Preparar mensajes con contexto de J2Biznes
        $messages = [
            [
                'role' => 'system',
                'content' => 'Eres un asistente administrativo inteligente para J2Biznes, un sistema de gestión de negocios. Ayudas a los administradores con consultas sobre gestión empresarial, ventas, inventario, clientes y tareas. Responde de forma concisa, profesional y en español.'
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
                'success' => true,
                'response' => $result['content'],
                'usage' => $result['usage'],
                'model' => $result['model'] ?? 'unknown'
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Error al procesar la solicitud'
        ], 500);
    }
}
