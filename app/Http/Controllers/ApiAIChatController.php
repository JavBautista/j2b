<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AI\GroqService;
use App\Services\AI\EmbeddingService;
use App\Models\ShopAiSettings;
use Illuminate\Support\Facades\Log;

/**
 * API Controller para Chat IA
 * Usado por la app Ionic (autenticado con JWT)
 *
 * Incluye:
 * - Verificación de acceso (can_use_ai)
 * - Prompt personalizado por tienda (ShopAiSettings)
 * - Búsqueda semántica de productos (RAG con embeddings)
 */
class ApiAIChatController extends Controller
{
    protected GroqService $aiService;
    protected EmbeddingService $embeddingService;

    public function __construct(GroqService $aiService, EmbeddingService $embeddingService)
    {
        $this->aiService = $aiService;
        $this->embeddingService = $embeddingService;
    }

    /**
     * Procesar mensaje de chat con IA
     * POST /api/auth/ia/chat
     *
     * Incluye RAG con embeddings para búsqueda semántica de productos
     */
    public function chat(Request $request)
    {
        $user = auth()->user();

        // 1. Verificar acceso al asistente IA
        if (!$user->can_use_ai) {
            return response()->json([
                'ok' => false,
                'error' => 'No tienes acceso al asistente de IA. Contacta al administrador.'
            ], 403);
        }

        $request->validate([
            'prompt' => 'required|string|min:3|max:2000'
        ]);

        $prompt = $request->input('prompt');
        $shopId = $user->shop_id;

        Log::info('API IA Chat Request', [
            'user_id' => $user->id,
            'shop_id' => $shopId,
            'prompt_length' => strlen($prompt)
        ]);

        // 2. Obtener prompt personalizado de la tienda o usar fallback
        $systemPrompt = $this->getSystemPrompt($user);

        // 3. Buscar productos con embeddings si aplica
        $productContext = '';
        if ($this->embeddingService->shouldSearchProducts($prompt)) {
            $productContext = $this->searchProductsContext($prompt, $shopId, $user);
        }

        // 4. Construir mensajes para Groq
        $messages = $this->buildMessages($systemPrompt, $productContext, $prompt);

        // 5. Enviar a Groq
        $result = $this->aiService->chat($messages);

        if ($result['success']) {
            return response()->json([
                'ok' => true,
                'response' => $result['content'],
                'usage' => $result['usage'],
                'model' => $result['model'] ?? 'unknown',
                'has_product_context' => !empty($productContext)
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
     * Obtener system prompt personalizado de ShopAiSettings o fallback
     */
    private function getSystemPrompt($user): string
    {
        $shopId = $user->shop_id;

        // Buscar prompt personalizado de la tienda
        $settings = ShopAiSettings::where('shop_id', $shopId)->first();

        if ($settings && !empty($settings->system_prompt)) {
            Log::info('ApiAIChatController: Usando prompt personalizado', [
                'shop_id' => $shopId
            ]);
            return $settings->system_prompt;
        }

        // Fallback: prompt genérico con nombre de tienda
        $shopName = $user->shop->name ?? 'tu negocio';

        Log::info('ApiAIChatController: Usando prompt fallback', [
            'shop_id' => $shopId,
            'shop_name' => $shopName
        ]);

        return "Eres un asistente inteligente para J2Biznes, un sistema de gestión de negocios.
Estás ayudando a un usuario de la tienda '{$shopName}'.
Ayudas con consultas sobre el sistema, ventas, inventario, clientes, tareas y reportes.
Responde de forma concisa, profesional y en español.
Si no sabes algo específico del negocio del usuario, indica que puedes ayudar con información general del sistema.";
    }

    /**
     * Buscar productos con embeddings y formatear como contexto
     */
    private function searchProductsContext(string $query, int $shopId, $user): string
    {
        try {
            // Determinar nivel de precio del usuario (1=retail, 2=wholesale, 3=premium)
            $userLevel = $user->level ?? 1;

            // Buscar productos con embeddings
            $products = $this->embeddingService->searchAndFormat($query, $shopId, 5, $userLevel);

            if (empty($products)) {
                Log::info('ApiAIChatController: No se encontraron productos', [
                    'query' => $query,
                    'shop_id' => $shopId
                ]);
                return '';
            }

            // Formatear productos como contexto para el LLM
            $context = "\n\n[PRODUCTOS ENCONTRADOS EN INVENTARIO]\n";
            foreach ($products as $product) {
                $context .= "- {$product['name']}";
                $context .= " | Código: {$product['key']}";
                $context .= " | Precio: \${$product['price']}";
                $context .= " | Stock: {$product['stock']}";
                $context .= " | Categoría: {$product['category']}";
                $context .= "\n";
            }
            $context .= "[FIN DE PRODUCTOS]\n";

            Log::info('ApiAIChatController: Productos encontrados para contexto', [
                'query' => $query,
                'shop_id' => $shopId,
                'products_count' => count($products)
            ]);

            return $context;

        } catch (\Exception $e) {
            Log::error('ApiAIChatController: Error buscando productos', [
                'query' => $query,
                'shop_id' => $shopId,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Construir array de mensajes para Groq
     */
    private function buildMessages(string $systemPrompt, string $productContext, string $userPrompt): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ]
        ];

        // Si hay contexto de productos, agregarlo como mensaje del sistema
        if (!empty($productContext)) {
            $messages[] = [
                'role' => 'system',
                'content' => "Usa la siguiente información de productos para responder la consulta del usuario. Si el usuario pregunta por productos, basa tu respuesta en estos datos reales del inventario:{$productContext}"
            ];
        }

        // Mensaje del usuario
        $messages[] = [
            'role' => 'user',
            'content' => $userPrompt
        ];

        return $messages;
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
