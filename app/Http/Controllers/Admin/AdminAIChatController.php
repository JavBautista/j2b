<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AI\GroqService;
use App\Services\AI\EmbeddingService;
use App\Services\AI\IntentClassifier;
use App\Services\AI\QueryService;
use App\Models\ShopAiSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminAIChatController extends Controller
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

        // 3. Clasificar intención y obtener contexto
        $intent = $this->intentClassifier->classify($prompt);
        $context = $this->resolveContext($intent, $prompt, $shopId, $user);

        // 4. Construir mensajes para Groq
        $messages = $this->buildMessages($systemPrompt, $context['text'], $prompt, $context['type']);

        // 5. Enviar a Groq
        $result = $this->aiService->chat($messages);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'response' => $result['content'],
                'usage' => $result['usage'],
                'model' => $result['model'] ?? 'unknown',
                'context_type' => $context['type'],
                'has_product_context' => $context['type'] === 'products',
            ]);
        }

        return response()->json([
            'success' => false,
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
                return $this->resolveSalesContext($prompt, $shopId);

            case 'debt_query':
                return $this->resolveDebtContext($prompt, $shopId);

            case 'expense_query':
                return $this->resolveExpenseContext($prompt, $shopId);

            case 'purchase_query':
                return $this->resolvePurchaseContext($prompt, $shopId);

            case 'client_history':
                return $this->resolveClientHistoryContext($prompt, $shopId);

            case 'client_search':
                $clientResult = $this->embeddingService->searchClients($prompt, $shopId);
                $text = $this->embeddingService->formatClientsForChat($clientResult);
                if (!empty($text)) {
                    return ['type' => 'clients', 'text' => $text];
                }
                return ['type' => 'none', 'text' => ''];

            case 'product_search':
                // Usar el flujo existente de RAG con shouldSearchProducts
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
     * Resolver contexto de ventas
     */
    private function resolveSalesContext(string $prompt, int $shopId): array
    {
        $period = $this->queryService->detectPeriod($prompt);
        $salesData = $this->queryService->salesSummary($shopId, $period);
        $text = $this->queryService->formatSalesSummary($salesData, $period);

        // Si pide top productos, agregar esa info
        if ($this->queryService->asksForTopProducts($prompt)) {
            $topProducts = $this->queryService->topProducts($shopId, $period);
            $text .= "\n" . $this->queryService->formatTopProducts($topProducts);
        }

        return ['type' => 'sales', 'text' => $text];
    }

    /**
     * Resolver contexto de adeudos
     */
    private function resolveDebtContext(string $prompt, int $shopId): array
    {
        $p = mb_strtolower($prompt);

        // Si pregunta específicamente por rentas
        if (str_contains($p, 'renta') || str_contains($p, 'rentas')) {
            $rentals = $this->queryService->activeRentals($shopId);
            $text = $this->queryService->formatActiveRentals($rentals);

            // También agregar adeudos generales si hay
            $debts = $this->queryService->clientDebts($shopId);
            if (!empty($debts)) {
                $text .= "\n" . $this->queryService->formatClientDebts($debts);
            }

            return ['type' => 'debts', 'text' => $text];
        }

        // Adeudos generales
        $debts = $this->queryService->clientDebts($shopId);
        $text = $this->queryService->formatClientDebts($debts);

        return ['type' => 'debts', 'text' => $text];
    }

    /**
     * Resolver contexto de historial de cliente
     */
    private function resolveClientHistoryContext(string $prompt, int $shopId): array
    {
        // Paso 1: Buscar cliente por nombre en la BD
        $client = $this->queryService->findClientByName($shopId, $prompt);

        // Paso 2: Si no se encontró en BD, intentar con Qdrant (búsqueda semántica)
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

        // Paso 3: Obtener resumen y últimas compras
        $summary = $this->queryService->clientSummary($shopId, $client->id);
        $history = $this->queryService->clientPurchaseHistory($shopId, $client->id);

        $text = $this->queryService->formatClientHistory($client, $summary, $history);

        return ['type' => 'client_history', 'text' => $text];
    }

    /**
     * Resolver contexto de compras a proveedores
     */
    private function resolvePurchaseContext(string $prompt, int $shopId): array
    {
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
    }

    /**
     * Resolver contexto de gastos
     */
    private function resolveExpenseContext(string $prompt, int $shopId): array
    {
        $period = $this->queryService->detectPeriod($prompt);
        $expenseData = $this->queryService->expenseSummary($shopId, $period);
        $text = $this->queryService->formatExpenseSummary($expenseData, $period);

        if ($this->queryService->asksForTopExpenses($prompt)) {
            $topCategories = $this->queryService->topExpenseCategories($shopId, $period);
            $text .= "\n" . $this->queryService->formatTopExpenseCategories($topCategories);
        }

        return ['type' => 'expenses', 'text' => $text];
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
    private function buildMessages(string $systemPrompt, string $context, string $userPrompt, string $contextType = 'none'): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ]
        ];

        // Agregar contexto según el tipo
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

        // Mensaje del usuario
        $messages[] = [
            'role' => 'user',
            'content' => $userPrompt
        ];

        return $messages;
    }
}
