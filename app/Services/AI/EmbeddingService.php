<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para interactuar con el microservicio Python de embeddings
 * J2Biznes - Sistema Multi-Tenant
 *
 * Este servicio se comunica con FastAPI (Python) para:
 * - Búsqueda semántica de productos
 * - Generación de embeddings
 * - Búsqueda vectorial en Qdrant con filtro por shop_id
 */
class EmbeddingService
{
    /**
     * URL base del microservicio Python
     * @var string
     */
    protected $baseUrl;

    /**
     * Timeout para peticiones HTTP (segundos)
     * @var int
     */
    protected $timeout;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->baseUrl = config('services.embedding.url', 'http://localhost:8001');
        $this->timeout = config('services.embedding.timeout', 5);
    }

    /**
     * Verificar si el servicio está disponible
     *
     * @return bool
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/");

            return $response->successful() &&
                   $response->json('status') === 'ok';
        } catch (\Exception $e) {
            Log::warning('EmbeddingService: Health check failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Búsqueda semántica de productos (MULTI-TENANT)
     *
     * @param string $query Consulta del usuario (ej: "cables hdmi")
     * @param int $shopId ID de la tienda (REQUERIDO)
     * @param int $limit Número máximo de resultados (default: 5)
     * @param int $userLevel Nivel de precio del usuario (1=retail, 2=wholesale, 3=premium)
     * @return array|null Array con productos encontrados o null si hay error
     */
    public function searchSemantic(string $query, int $shopId, int $limit = 5, int $userLevel = 1): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/search/semantic", [
                    'query' => $query,
                    'limit' => $limit,
                    'shop_id' => $shopId,  // FILTRO MULTI-TENANT
                    'user_level' => $userLevel
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('EmbeddingService: Búsqueda semántica exitosa', [
                    'query' => $query,
                    'shop_id' => $shopId,
                    'found' => $data['found'] ?? false,
                    'products_count' => count($data['products'] ?? []),
                    'query_time_ms' => $data['query_time_ms'] ?? 0
                ]);

                return $data;
            }

            Log::error('EmbeddingService: Error en búsqueda semántica', [
                'query' => $query,
                'shop_id' => $shopId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('EmbeddingService: Excepción en búsqueda semántica', [
                'query' => $query,
                'shop_id' => $shopId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generar embedding de un texto
     *
     * @param string $text Texto a convertir en vector
     * @return array|null Array con vector y dimensiones o null si hay error
     */
    public function generateEmbedding(string $text): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/embed", [
                    'text' => $text
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('EmbeddingService: Error generando embedding', [
                'text' => $text,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Formatear productos del microservicio al formato esperado por el chat
     *
     * @param array $products Productos del microservicio Python
     * @return array Productos formateados para Groq
     */
    public function formatProductsForChat(array $products): array
    {
        return array_map(function ($product) {
            return [
                'id' => $product['id'],
                'key' => $product['key'],
                'name' => $product['name'],
                'price' => $product['price'],
                'stock' => $product['stock'],
                'category' => $product['category'] ?? 'Sin categoría',
                'score' => $product['score'] ?? 0,
            ];
        }, $products);
    }

    /**
     * Buscar productos con texto semántico y formatear para el chat
     *
     * @param string $query Consulta del usuario
     * @param int $shopId ID de la tienda (REQUERIDO)
     * @param int $limit Número de productos
     * @param int $userLevel Nivel del usuario
     * @return array Array con productos formateados
     */
    public function searchAndFormat(string $query, int $shopId, int $limit = 5, int $userLevel = 1): array
    {
        $result = $this->searchSemantic($query, $shopId, $limit, $userLevel);

        if (!$result || !$result['found']) {
            return [];
        }

        return $this->formatProductsForChat($result['products']);
    }

    /**
     * Determinar si una consulta requiere búsqueda de productos
     *
     * Este método evita búsquedas innecesarias para saludos u otros textos
     * que claramente NO son búsqueda de productos.
     *
     * @param string $query Consulta del usuario
     * @return bool True si debe buscar productos, False si no
     */
    public function shouldSearchProducts(string $query): bool
    {
        // Palabras que indican búsqueda de productos
        $searchKeywords = [
            'tienes', 'tienen', 'venden', 'hay', 'busco', 'necesito',
            'quiero', 'producto', 'productos', 'artículo', 'articulo',
            'mostrar', 'dame', 'encuentro', 'relacionados', 'similares',
            'tipo', 'clase', 'categoría', 'existencias', 'stock',
            'disponible', 'vender', 'comprar', 'precio', 'precios',
            'cuesta', 'cuestan', 'cuánto', 'cuanto'
        ];

        // Saludos y frases que NO deben buscar productos
        $greetings = [
            'hola', 'hello', 'hi', 'hey', 'qué tal', 'que tal',
            'buenos días', 'buenas tardes', 'buenas noches',
            'buen día', 'buena tarde', 'buena noche',
            'cómo estás', 'como estas', 'saludos', 'holi',
            'qué onda', 'que onda', 'cómo vas', 'como vas'
        ];

        $queryLower = mb_strtolower($query);
        $wordCount = str_word_count($query);

        // 1. Si es solo saludo (≤3 palabras), NO buscar
        foreach ($greetings as $greeting) {
            if (stripos($queryLower, $greeting) !== false && $wordCount <= 3) {
                Log::info('EmbeddingService: Detectado saludo, NO buscar productos', [
                    'query' => $query,
                    'greeting_detected' => $greeting
                ]);
                return false;
            }
        }

        // 2. Si tiene keywords de búsqueda explícita, SÍ buscar
        foreach ($searchKeywords as $keyword) {
            if (stripos($queryLower, $keyword) !== false) {
                Log::info('EmbeddingService: Detectado keyword de búsqueda, SÍ buscar', [
                    'query' => $query,
                    'keyword_detected' => $keyword
                ]);
                return true;
            }
        }

        // 3. Por defecto, buscar si tiene más de 3 palabras
        $shouldSearch = $wordCount > 3;

        Log::info('EmbeddingService: Evaluación por longitud de texto', [
            'query' => $query,
            'word_count' => $wordCount,
            'should_search' => $shouldSearch
        ]);

        return $shouldSearch;
    }

    // =========================================================================
    // BUSQUEDA DE CLIENTES
    // =========================================================================

    /**
     * Búsqueda semántica de clientes (MULTI-TENANT)
     *
     * @param string $query Consulta del usuario (ej: "Juan Perez")
     * @param int $shopId ID de la tienda (REQUERIDO)
     * @param int $limit Número máximo de resultados
     * @return array|null Array con clientes encontrados o null si hay error
     */
    public function searchClients(string $query, int $shopId, int $limit = 5): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/search/clients", [
                    'query' => $query,
                    'limit' => $limit,
                    'shop_id' => $shopId,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('EmbeddingService: Búsqueda de clientes exitosa', [
                    'query' => $query,
                    'shop_id' => $shopId,
                    'found' => $data['found'] ?? false,
                    'clients_count' => count($data['clients'] ?? []),
                ]);

                return $data;
            }

            Log::error('EmbeddingService: Error en búsqueda de clientes', [
                'query' => $query,
                'shop_id' => $shopId,
                'status' => $response->status(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('EmbeddingService: Excepción buscando clientes', [
                'query' => $query,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Formatear clientes encontrados como texto para contexto del LLM
     *
     * @param array|null $searchResult Resultado de searchClients()
     * @return string Texto formateado para inyectar al LLM
     */
    public function formatClientsForChat(?array $searchResult): string
    {
        if (!$searchResult || !($searchResult['found'] ?? false)) {
            return '';
        }

        $clients = $searchResult['clients'] ?? [];
        if (empty($clients)) {
            return '';
        }

        $context = "[CLIENTES ENCONTRADOS]\n";
        foreach ($clients as $client) {
            $parts = ["- {$client['name']}"];

            if (!empty($client['company'])) {
                $parts[] = "Empresa: {$client['company']}";
            }
            if (!empty($client['phone'])) {
                $parts[] = "Tel: {$client['phone']}";
            }
            if (!empty($client['email'])) {
                $parts[] = "Email: {$client['email']}";
            }
            if (!empty($client['city'])) {
                $location = $client['city'];
                if (!empty($client['state'])) {
                    $location .= ", {$client['state']}";
                }
                $parts[] = "Ciudad: {$location}";
            }
            if (!empty($client['plan_name'])) {
                $parts[] = "Plan: {$client['plan_name']}";
            }
            if (!empty($client['observations'])) {
                $parts[] = "Obs: {$client['observations']}";
            }

            $context .= implode(' | ', $parts) . "\n";
        }
        $context .= "[FIN CLIENTES]";

        return $context;
    }

    /**
     * Indexar clientes de una tienda en Qdrant
     *
     * @param int $shopId ID de la tienda
     * @return array Resultado con indexed y errors
     */
    public function indexClients(int $shopId): array
    {
        $response = Http::timeout(120)
            ->post("{$this->baseUrl}/index/clients", [
                'shop_id' => $shopId
            ]);

        if ($response->failed()) {
            throw new \Exception('Error al indexar clientes: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Obtener conteo de items indexados por tipo para una tienda
     *
     * @param int $shopId ID de la tienda
     * @return array Array con conteos de productos y servicios
     */
    public function getIndexedCounts(int $shopId): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/stats/shop/{$shopId}");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error("EmbeddingService::getIndexedCounts error", [
                'shop_id' => $shopId,
                'error' => $e->getMessage()
            ]);
        }

        return ['products' => 0, 'services' => 0];
    }

    /**
     * Indexar solo productos de una tienda
     *
     * @param int $shopId ID de la tienda
     * @return array Resultado con indexed y errors
     */
    public function indexProducts(int $shopId): array
    {
        $response = Http::timeout(120)
            ->post("{$this->baseUrl}/index/products", [
                'shop_id' => $shopId
            ]);

        if ($response->failed()) {
            throw new \Exception('Error al conectar con servicio de embeddings: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Indexar solo servicios de una tienda
     *
     * @param int $shopId ID de la tienda
     * @return array Resultado con indexed y errors
     */
    public function indexServices(int $shopId): array
    {
        $response = Http::timeout(120)
            ->post("{$this->baseUrl}/index/services", [
                'shop_id' => $shopId
            ]);

        if ($response->failed()) {
            throw new \Exception('Error al conectar con servicio de embeddings: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Indexar catálogo completo (productos + servicios)
     *
     * @param int $shopId ID de la tienda
     * @return array Resultado con products, services y errors
     */
    public function indexCatalog(int $shopId): array
    {
        $response = Http::timeout(180)
            ->post("{$this->baseUrl}/index/catalog", [
                'shop_id' => $shopId
            ]);

        if ($response->failed()) {
            throw new \Exception('Error al conectar con servicio de embeddings: ' . $response->body());
        }

        return $response->json();
    }
}
