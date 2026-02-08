<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AI\GroqService;
use App\Services\AI\EmbeddingService;
use App\Services\AI\IntentClassifier;
use App\Services\AI\QueryService;
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
    protected IntentClassifier $intentClassifier;
    protected QueryService $queryService;

    public function __construct(
        GroqService $aiService,
        EmbeddingService $embeddingService,
        IntentClassifier $intentClassifier,
        QueryService $queryService
    ) {
        $this->aiService = $aiService;
        $this->embeddingService = $embeddingService;
        $this->intentClassifier = $intentClassifier;
        $this->queryService = $queryService;
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

        // 3. Clasificar intención y obtener contexto
        $intent = $this->intentClassifier->classify($prompt);
        $context = $this->resolveContext($intent, $prompt, $shopId, $user);

        // 4. Construir mensajes para Groq
        $messages = $this->buildMessages($systemPrompt, $context['text'], $prompt, $context['type']);

        // 5. Enviar a Groq
        $result = $this->aiService->chat($messages);

        if ($result['success']) {
            return response()->json([
                'ok' => true,
                'response' => $result['content'],
                'usage' => $result['usage'],
                'model' => $result['model'] ?? 'unknown',
                'context_type' => $context['type'],
                'has_product_context' => $context['type'] === 'products',
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
     * Resolver contexto según la intención clasificada
     */
    private function resolveContext(string $intent, string $prompt, int $shopId, $user): array
    {
        switch ($intent) {
            case 'sales_query':
                $period = $this->queryService->detectPeriod($prompt);
                $salesData = $this->queryService->salesSummary($shopId, $period);
                $text = $this->queryService->formatSalesSummary($salesData, $period);

                if ($this->queryService->asksForTopProducts($prompt)) {
                    $topProducts = $this->queryService->topProducts($shopId, $period);
                    $text .= "\n" . $this->queryService->formatTopProducts($topProducts);
                }
                return ['type' => 'sales', 'text' => $text];

            case 'debt_query':
                $p = mb_strtolower($prompt);
                if (str_contains($p, 'renta') || str_contains($p, 'rentas')) {
                    $rentals = $this->queryService->activeRentals($shopId);
                    $text = $this->queryService->formatActiveRentals($rentals);
                    $debts = $this->queryService->clientDebts($shopId);
                    if (!empty($debts)) {
                        $text .= "\n" . $this->queryService->formatClientDebts($debts);
                    }
                } else {
                    $debts = $this->queryService->clientDebts($shopId);
                    $text = $this->queryService->formatClientDebts($debts);
                }
                return ['type' => 'debts', 'text' => $text];

            case 'expense_query':
                $period = $this->queryService->detectPeriod($prompt);
                $expenseData = $this->queryService->expenseSummary($shopId, $period);
                $text = $this->queryService->formatExpenseSummary($expenseData, $period);
                if ($this->queryService->asksForTopExpenses($prompt)) {
                    $topCategories = $this->queryService->topExpenseCategories($shopId, $period);
                    $text .= "\n" . $this->queryService->formatTopExpenseCategories($topCategories);
                }
                return ['type' => 'expenses', 'text' => $text];

            case 'purchase_query':
                $period = $this->queryService->detectPeriod($prompt);
                $purchaseData = $this->queryService->purchaseSummary($shopId, $period);
                $text = $this->queryService->formatPurchaseSummary($purchaseData, $period);
                if ($this->queryService->asksForSupplierDebts($prompt)) {
                    $debts = $this->queryService->supplierDebts($shopId);
                    $text .= "\n" . $this->queryService->formatSupplierDebts($debts);
                }
                if ($this->queryService->asksForTopSuppliers($prompt)) {
                    $topSuppliers = $this->queryService->topSuppliers($shopId, $period);
                    $text .= "\n" . $this->queryService->formatTopSuppliers($topSuppliers);
                }
                return ['type' => 'purchases', 'text' => $text];

            case 'client_history':
                $client = $this->queryService->findClientByName($shopId, $prompt);
                if (!$client) {
                    $clientResult = $this->embeddingService->searchClients($prompt, $shopId, 1);
                    if ($clientResult && !empty($clientResult['clients'])) {
                        $found = $clientResult['clients'][0];
                        $client = \Illuminate\Support\Facades\DB::table('clients')
                            ->where('shop_id', $shopId)
                            ->where('id', $found['id'])
                            ->select('id', 'name', 'company', 'phone', 'email')
                            ->first();
                    }
                }
                if (!$client) {
                    return ['type' => 'client_history', 'text' => "[HISTORIAL DEL CLIENTE]\nNo se encontró un cliente con ese nombre.\n[FIN HISTORIAL CLIENTE]"];
                }
                $summary = $this->queryService->clientSummary($shopId, $client->id);
                $history = $this->queryService->clientPurchaseHistory($shopId, $client->id);
                $text = $this->queryService->formatClientHistory($client, $summary, $history);
                return ['type' => 'client_history', 'text' => $text];

            case 'client_search':
                $clientResult = $this->embeddingService->searchClients($prompt, $shopId);
                $text = $this->embeddingService->formatClientsForChat($clientResult);
                if (!empty($text)) {
                    return ['type' => 'clients', 'text' => $text];
                }
                return ['type' => 'none', 'text' => ''];

            case 'product_search':
                if ($this->embeddingService->shouldSearchProducts($prompt)) {
                    $text = $this->searchProductsContext($prompt, $shopId, $user);
                    if (!empty($text)) {
                        return ['type' => 'products', 'text' => $text];
                    }
                }
                return ['type' => 'none', 'text' => ''];

            default:
                return ['type' => 'none', 'text' => ''];
        }
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
    private function buildMessages(string $systemPrompt, string $context, string $userPrompt, string $contextType = 'none'): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ]
        ];

        if (!empty($context)) {
            $contextInstruction = match ($contextType) {
                'products' => "Usa la siguiente información de productos para responder la consulta del usuario. Si el usuario pregunta por productos, basa tu respuesta en estos datos reales del inventario:",
                'sales' => "Usa los siguientes datos REALES de ventas del negocio para responder. Estos datos vienen directamente de la base de datos y son exactos. Responde de forma clara y concisa:",
                'debts' => "Usa los siguientes datos REALES de adeudos y cobros del negocio para responder. Estos datos vienen directamente de la base de datos y son exactos. Si te preguntan a quién cobrar, prioriza los adeudos más grandes:",
                'expenses' => "Usa los siguientes datos REALES de gastos/egresos del negocio para responder. Estos datos vienen directamente de la base de datos y son exactos. Responde de forma clara y concisa:",
                'purchases' => "Usa los siguientes datos REALES de compras a proveedores del negocio para responder. Estos datos vienen directamente de la base de datos y son exactos. Responde de forma clara y concisa:",
                'client_history' => "Usa los siguientes datos REALES del historial de compras de este cliente para responder. Estos datos vienen directamente de la base de datos y son exactos. Presenta la información de forma organizada:",
                'clients' => "Usa la siguiente información REAL de clientes del negocio para responder. Estos datos vienen directamente de la base de datos. Proporciona los datos de contacto disponibles:",
                default => "Usa la siguiente información para responder:",
            };

            $messages[] = [
                'role' => 'system',
                'content' => $contextInstruction . "\n" . $context
            ];
        }

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
