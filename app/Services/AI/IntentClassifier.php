<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

/**
 * Clasificador de intención para el chat IA
 *
 * Detecta el tipo de consulta del usuario para enrutarla
 * al servicio correcto (RAG vs SQL vs general).
 *
 * Intenciones soportadas:
 * - sales_query: Consultas sobre ventas, totales, ingresos
 * - debt_query: Consultas sobre adeudos, cobros, pagos pendientes
 * - expense_query: Consultas sobre gastos, egresos, categorías de gasto
 * - purchase_query: Consultas sobre compras a proveedores, órdenes de compra
 * - client_history: Historial de compras de un cliente específico
 * - client_search: Búsqueda de información de clientes
 * - product_search: Búsqueda de productos/servicios (RAG existente)
 * - general: Conversación general, ayuda, etc.
 */
class IntentClassifier
{
    /**
     * Servicio Groq para clasificación LLM (fallback opcional)
     */
    protected ?GroqService $groqService;

    public function __construct(?GroqService $groqService = null)
    {
        $this->groqService = $groqService;
    }

    /**
     * Keywords que indican consulta de ventas/financiera
     */
    protected array $salesKeywords = [
        'cuanto vendimos', 'cuanto hemos vendido', 'cuantas ventas',
        'cuánto vendimos', 'cuánto hemos vendido', 'cuántas ventas',
        'ventas de hoy', 'ventas del dia', 'ventas del día', 'ventas del mes',
        'ventas de la semana', 'total vendido', 'total de ventas',
        'ingresos de hoy', 'ingresos del mes', 'ingresos de la semana',
        'notas de venta', 'cuantas notas', 'cuántas notas',
        'facturacion', 'facturación', 'facturado',
        'resumen de ventas', 'reporte de ventas',
        'vendido hoy', 'vendido este mes', 'vendido esta semana',
        'mas vendido', 'más vendido', 'producto mas vendido', 'producto más vendido',
        'mejor vendido', 'top ventas', 'top productos',
    ];

    /**
     * Keywords que indican consulta de adeudos/cobros
     */
    protected array $debtKeywords = [
        'quien me debe', 'quién me debe', 'a quien le cobro', 'a quién le cobro',
        'cobrar esta semana', 'cobrar hoy', 'cobrar este mes',
        'adeudo', 'adeudos', 'pendiente de pago', 'pendientes de pago',
        'por cobrar', 'cuentas por cobrar',
        'deuda', 'deudas', 'moroso', 'morosos', 'vencido', 'vencidos',
        'pagos pendientes', 'pago pendiente',
        'credito pendiente', 'crédito pendiente', 'creditos pendientes', 'créditos pendientes',
        'rentas pendientes', 'cobro de rentas', 'cobros de renta',
        'quien no ha pagado', 'quién no ha pagado',
        'falta de pagar', 'faltan de pagar',
        'me deben', 'nos deben',
    ];

    /**
     * Keywords que indican consulta de gastos/egresos
     */
    protected array $expenseKeywords = [
        'gasto', 'gastos', 'gastamos', 'cuanto gastamos', 'cuánto gastamos',
        'gastos de hoy', 'gastos del dia', 'gastos del día', 'gastos del mes',
        'gastos de la semana', 'gastos del mes pasado',
        'total de gastos', 'resumen de gastos', 'reporte de gastos',
        'en que gastamos', 'en qué gastamos',
        'egreso', 'egresos',
    ];

    /**
     * Keywords que indican consulta de compras a proveedores
     */
    protected array $purchaseKeywords = [
        'compras de hoy', 'compras del mes', 'compras de la semana',
        'compras del mes pasado', 'compras totales',
        'cuanto compramos', 'cuánto compramos',
        'ordenes de compra', 'órdenes de compra', 'orden de compra',
        'proveedor', 'proveedores',
        'le debo a proveedor', 'deuda con proveedor', 'deudas con proveedores',
        'a quien le debo', 'a quién le debo',
        'cuentas por pagar', 'por pagar',
    ];

    /**
     * Keywords que indican búsqueda de clientes
     */
    protected array $clientKeywords = [
        // Con la palabra "cliente" explícita
        'info del cliente', 'información del cliente', 'datos del cliente',
        'buscar cliente', 'busca al cliente', 'busca el cliente',
        'telefono del cliente', 'teléfono del cliente',
        'direccion del cliente', 'dirección del cliente',
        'correo del cliente', 'email del cliente',
        'contacto del cliente', 'contactar al cliente',
        // Sin la palabra "cliente" (patrones comunes para buscar personas)
        'info de ', 'información de ', 'datos de ',
        'busca a ', 'contacto de ', 'contactar a ',
        'quien es ', 'quién es ',
    ];

    /**
     * Nombres de clientes activos (cache para detección inteligente)
     * Se llena en classify() si es necesario
     */
    protected ?array $clientNames = null;

    /**
     * Saludos que NO deben activar ninguna búsqueda
     */
    protected array $greetings = [
        'hola', 'hello', 'hi', 'hey', 'qué tal', 'que tal',
        'buenos días', 'buenas tardes', 'buenas noches',
        'buen día', 'buena tarde', 'buena noche',
        'cómo estás', 'como estas', 'saludos', 'holi',
        'qué onda', 'que onda', 'cómo vas', 'como vas',
    ];

    /**
     * Clasificar la intención del usuario
     *
     * @param string $prompt Texto del usuario
     * @return string Intención detectada: sales_query|debt_query|expense_query|client_history|purchase_query|client_search|product_search|general
     */
    public function classify(string $prompt): string
    {
        $normalized = mb_strtolower(trim($prompt));
        $wordCount = str_word_count($prompt);

        // 1. Saludos cortos → general
        if ($wordCount <= 3) {
            foreach ($this->greetings as $greeting) {
                if (mb_strpos($normalized, $greeting) !== false) {
                    return $this->log('general', $prompt, 'saludo detectado');
                }
            }
        }

        // 2. Ventas / financiero (prioridad alta)
        foreach ($this->salesKeywords as $kw) {
            if (mb_strpos($normalized, $kw) !== false) {
                return $this->log('sales_query', $prompt, "keyword: {$kw}");
            }
        }

        // 3. Adeudos / cobros
        foreach ($this->debtKeywords as $kw) {
            if (mb_strpos($normalized, $kw) !== false) {
                return $this->log('debt_query', $prompt, "keyword: {$kw}");
            }
        }

        // 4. Gastos / egresos
        foreach ($this->expenseKeywords as $kw) {
            if (mb_strpos($normalized, $kw) !== false) {
                return $this->log('expense_query', $prompt, "keyword: {$kw}");
            }
        }

        // 5. Historial de cliente (patrón "qué ha comprado [nombre]")
        //    DEBE ir antes de purchase_query para evitar conflicto con "compras de..."
        if ($this->detectsClientHistoryPattern($normalized)) {
            return $this->log('client_history', $prompt, 'patrón de historial de cliente');
        }

        // 6. Compras / proveedores
        foreach ($this->purchaseKeywords as $kw) {
            if (mb_strpos($normalized, $kw) !== false) {
                return $this->log('purchase_query', $prompt, "keyword: {$kw}");
            }
        }

        // 7. Clientes (keywords explícitas)
        foreach ($this->clientKeywords as $kw) {
            if (mb_strpos($normalized, $kw) !== false) {
                return $this->log('client_search', $prompt, "keyword: {$kw}");
            }
        }

        // 8. Detección inteligente: si menciona un nombre de cliente conocido
        if ($this->mentionsKnownClient($normalized)) {
            return $this->log('client_search', $prompt, 'nombre de cliente detectado');
        }

        // 9. LLM fallback (si está habilitado)
        if (config('groq.classifier.enabled', false) && $this->groqService) {
            $llmIntent = $this->groqService->classifyIntent($prompt);
            if ($llmIntent) {
                return $this->log($llmIntent, $prompt, "LLM clasificó como: {$llmIntent}");
            }
        }

        // 10. Fallback final: delegar al detector de productos existente
        return 'product_search';
    }

    /**
     * Detectar patrón de historial de cliente
     *
     * Detecta frases como "qué ha comprado Peplitos", "historial de compras de Juan"
     *
     * @param string $normalized Texto normalizado en minúsculas
     * @return bool
     */
    private function detectsClientHistoryPattern(string $normalized): bool
    {
        // Patrones explícitos que siempre indican historial
        $historyPatterns = [
            'que ha comprado', 'qué ha comprado',
            'cuanto ha comprado', 'cuánto ha comprado',
            'cuanto me ha comprado', 'cuánto me ha comprado',
            'cuanto nos ha comprado', 'cuánto nos ha comprado',
            'historial de compras de', 'historial del cliente',
            'historial de compra de',
        ];

        foreach ($historyPatterns as $pattern) {
            if (mb_strpos($normalized, $pattern) !== false) {
                return true;
            }
        }

        // Patrones ambiguos: "compras de [nombre]" o "ventas a [nombre]"
        // Solo si el texto después del prefijo es un nombre de cliente conocido
        $prefixes = ['compras de ', 'ventas a ', 'ventas al cliente '];
        foreach ($prefixes as $prefix) {
            $pos = mb_strpos($normalized, $prefix);
            if ($pos !== false) {
                $afterPrefix = trim(mb_substr($normalized, $pos + mb_strlen($prefix)));
                if (!empty($afterPrefix) && $this->mentionsKnownClient($afterPrefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verificar si el texto menciona un nombre de cliente conocido de la tienda
     *
     * @param string $normalized Texto normalizado en minúsculas
     * @return bool
     */
    private function mentionsKnownClient(string $normalized): bool
    {
        // Solo verificar si hay un shopId disponible en el contexto de auth
        $user = auth()->user();
        if (!$user || !$user->shop_id) {
            return false;
        }

        // Cargar nombres de clientes (cache en memoria durante la request)
        if ($this->clientNames === null) {
            $this->clientNames = \App\Models\Client::where('shop_id', $user->shop_id)
                ->where('active', 1)
                ->pluck('name')
                ->map(fn($name) => mb_strtolower(trim($name)))
                ->filter(fn($name) => mb_strlen($name) >= 3) // Ignorar nombres muy cortos
                ->toArray();
        }

        foreach ($this->clientNames as $clientName) {
            if (mb_strpos($normalized, $clientName) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log de clasificación para debug
     */
    private function log(string $intent, string $prompt, string $reason): string
    {
        Log::info('IntentClassifier', [
            'intent' => $intent,
            'reason' => $reason,
            'prompt' => mb_substr($prompt, 0, 100),
        ]);

        return $intent;
    }
}
