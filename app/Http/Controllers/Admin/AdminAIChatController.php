<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AI\GroqService;
use App\Services\AI\EmbeddingService;
use App\Models\ShopAiSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminAIChatController extends Controller
{
    protected GroqService $aiService;
    protected EmbeddingService $embeddingService;

    public function __construct(GroqService $aiService, EmbeddingService $embeddingService)
    {
        $this->aiService = $aiService;
        $this->embeddingService = $embeddingService;
    }

    /**
     * Procesar chat con IA para Admin
     *
     * Incluye:
     * - Verificación de acceso (can_use_ai)
     * - Prompt personalizado por tienda (shop_ai_settings)
     * - Búsqueda semántica de productos (RAG con embeddings)
     */
    public function chat(Request $request)
    {
        $user = Auth::user();

        // 1. Verificar acceso al asistente IA
        if (!$user->can_use_ai) {
            return response()->json([
                'success' => false,
                'error' => 'No tienes acceso al asistente de IA. Contacta al administrador.'
            ], 403);
        }

        $request->validate([
            'prompt' => 'required|string|min:3|max:2000'
        ]);

        $prompt = $request->input('prompt');
        $shopId = $user->shop_id;

        // 2. Obtener prompt personalizado de la tienda o usar fallback
        $systemPrompt = $this->getSystemPrompt($shopId, $user);

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
                'success' => true,
                'response' => $result['content'],
                'usage' => $result['usage'],
                'model' => $result['model'] ?? 'unknown',
                'has_product_context' => !empty($productContext)
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Error al procesar la solicitud'
        ], 500);
    }

    /**
     * Obtener system prompt personalizado o fallback
     */
    private function getSystemPrompt(int $shopId, $user): string
    {
        // Buscar prompt personalizado de la tienda
        $settings = ShopAiSettings::where('shop_id', $shopId)->first();

        if ($settings && !empty($settings->system_prompt)) {
            Log::info('AdminAIChatController: Usando prompt personalizado', [
                'shop_id' => $shopId
            ]);
            return $settings->system_prompt;
        }

        // Fallback: prompt genérico con nombre de tienda
        $shopName = $user->shop->name ?? 'tu negocio';

        Log::info('AdminAIChatController: Usando prompt fallback', [
            'shop_id' => $shopId,
            'shop_name' => $shopName
        ]);

        return "Eres un asistente administrativo inteligente para '{$shopName}', usando el sistema J2Biznes. Ayudas con consultas sobre gestión empresarial, ventas, inventario, clientes y tareas. Responde de forma concisa, profesional y en español. Si no tienes información específica del negocio, indica que puedes ayudar con información general del sistema.";
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
                Log::info('AdminAIChatController: No se encontraron productos', [
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

            Log::info('AdminAIChatController: Productos encontrados para contexto', [
                'query' => $query,
                'shop_id' => $shopId,
                'products_count' => count($products)
            ]);

            return $context;

        } catch (\Exception $e) {
            Log::error('AdminAIChatController: Error buscando productos', [
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
}
