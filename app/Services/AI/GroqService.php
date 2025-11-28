<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio para Groq AI
 *
 * Groq proporciona inferencia ultra rápida (~450-500 tokens/segundo)
 * Compatible 100% con OpenAI API format
 *
 * Rate Limits FREE:
 * - llama-3.1-8b-instant: 14.4K RPD, 500K TPD
 * - llama-3.3-70b-versatile: 1K RPD, 100K TPD
 *
 * @see https://console.groq.com/docs/
 */
class GroqService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('groq.api_key');
        $this->baseUrl = config('groq.base_url');
        $this->model = config('groq.model');
        $this->timeout = config('groq.timeout', 30);

        // Validar que tenemos API key
        if (empty($this->apiKey)) {
            throw new Exception('Groq API key no configurada en .env (GROQ_API_KEY)');
        }
    }

    /**
     * Generar texto simple con un prompt
     *
     * @param string $prompt El texto de entrada
     * @param array $options Opciones adicionales (model, max_tokens, temperature)
     * @return array ['success' => bool, 'content' => string, 'usage' => array]
     */
    public function generateText(string $prompt, array $options = []): array
    {
        try {
            // Log para debugging
            Log::info('Groq Request', [
                'prompt_length' => strlen($prompt),
                'prompt_preview' => substr($prompt, 0, 100) . '...',
                'model' => $options['model'] ?? $this->model
            ]);

            // Preparar mensajes
            $messages = [
                ['role' => 'user', 'content' => $prompt]
            ];

            // Hacer la petición
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->retry(config('groq.retry.times', 3), config('groq.retry.sleep', 1000))
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $options['model'] ?? $this->model,
                    'messages' => $messages,
                    'max_tokens' => (int) ($options['max_tokens'] ?? config('groq.max_tokens')),
                    'temperature' => (float) ($options['temperature'] ?? config('groq.temperature')),
                ]);

            // Verificar respuesta exitosa
            if (!$response->successful()) {
                $errorBody = $response->json();

                Log::error('Groq API Error', [
                    'status' => $response->status(),
                    'error' => $errorBody['error'] ?? $response->body()
                ]);

                // Detectar error de rate limit
                if ($response->status() === 429) {
                    throw new Exception('Rate limit alcanzado. Espera unos minutos o cambia a un modelo con límites más altos (ej: llama-3.1-8b-instant)');
                }

                throw new Exception('Error en la API de Groq: ' . ($errorBody['error']['message'] ?? $response->body()));
            }

            $data = $response->json();

            // Log respuesta exitosa
            Log::info('Groq Response Success', [
                'usage' => $data['usage'] ?? null,
                'model_used' => $data['model'] ?? null,
                'finish_reason' => $data['choices'][0]['finish_reason'] ?? null
            ]);

            return [
                'success' => true,
                'content' => $data['choices'][0]['message']['content'] ?? '',
                'usage' => $data['usage'] ?? [],
                'model' => $data['model'] ?? $this->model,
                'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
                'raw_response' => $data
            ];

        } catch (Exception $e) {
            Log::error('Groq Service Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'content' => null
            ];
        }
    }

    /**
     * Chat con contexto de conversación
     *
     * @param array $messages Array de mensajes [['role' => 'user', 'content' => '...']]
     * @param array $options Opciones adicionales
     * @return array
     */
    public function chat(array $messages, array $options = []): array
    {
        try {
            // Validar formato de mensajes
            foreach ($messages as $message) {
                if (!isset($message['role']) || !isset($message['content'])) {
                    throw new Exception('Formato de mensajes inválido. Cada mensaje debe tener "role" y "content"');
                }
            }

            Log::info('Groq Chat Request', [
                'messages_count' => count($messages),
                'model' => $options['model'] ?? $this->model
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->retry(config('groq.retry.times', 3), config('groq.retry.sleep', 1000))
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $options['model'] ?? $this->model,
                    'messages' => $messages,
                    'max_tokens' => (int) ($options['max_tokens'] ?? config('groq.max_tokens')),
                    'temperature' => (float) ($options['temperature'] ?? config('groq.temperature')),
                    'stream' => $options['stream'] ?? false,
                ]);

            if (!$response->successful()) {
                $errorBody = $response->json();

                if ($response->status() === 429) {
                    throw new Exception('Rate limit alcanzado. Considera usar llama-3.1-8b-instant (14.4K requests/día)');
                }

                throw new Exception('Error en la API: ' . ($errorBody['error']['message'] ?? $response->body()));
            }

            $data = $response->json();

            return [
                'success' => true,
                'content' => $data['choices'][0]['message']['content'] ?? '',
                'usage' => $data['usage'] ?? [],
                'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
                'model' => $data['model'] ?? $this->model,
            ];

        } catch (Exception $e) {
            Log::error('Groq Chat Error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'content' => null
            ];
        }
    }

    /**
     * Verificar estado de la API
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            // Hacer una petición simple para verificar la conexión
            $result = $this->generateText('Responde solo con "OK" si recibes este mensaje.', [
                'max_tokens' => 10,
                'temperature' => 0.0
            ]);

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => '✅ Conexión exitosa con Groq AI',
                    'model' => $this->model,
                    'response' => $result['content'],
                    'usage' => $result['usage'] ?? null,
                    'speed_info' => 'Groq es ~450-500 tokens/segundo (15X más rápido que promedio)'
                ];
            }

            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener lista de modelos disponibles en config
     *
     * @return array
     */
    public function getAvailableModels(): array
    {
        return [
            'success' => true,
            'current_model' => $this->model,
            'models' => config('groq.available_models', [])
        ];
    }

    /**
     * Generar descripción de producto optimizada para e-commerce
     *
     * @param array $productoData Datos del producto (nombre, categoría, características)
     * @param string $tipo Tipo de descripción: 'corta', 'larga', 'seo'
     * @return array
     */
    public function generarDescripcionProducto(array $productoData, string $tipo = 'larga'): array
    {
        $nombre = $productoData['nombre'] ?? 'Producto';
        $categoria = $productoData['categoria'] ?? '';
        $caracteristicas = $productoData['caracteristicas'] ?? '';

        $prompts = [
            'corta' => "Genera una descripción breve y atractiva (2-3 oraciones) para este producto de e-commerce:\n\nNombre: {$nombre}\nCategoría: {$categoria}\nCaracterísticas: {$caracteristicas}\n\nDescripción comercial:",

            'larga' => "Genera una descripción detallada y profesional para este producto de catálogo B2B:\n\nNombre: {$nombre}\nCategoría: {$categoria}\nCaracterísticas actuales: {$caracteristicas}\n\nCrea una descripción que incluya:\n1. Descripción general del producto\n2. Beneficios principales\n3. Aplicaciones o usos recomendados\n4. Características técnicas relevantes\n\nDescripción:",

            'seo' => "Genera una descripción optimizada para SEO de este producto:\n\nNombre: {$nombre}\nCategoría: {$categoria}\nCaracterísticas: {$caracteristicas}\n\nIncluye palabras clave relevantes de forma natural. Descripción SEO:"
        ];

        $prompt = $prompts[$tipo] ?? $prompts['larga'];

        return $this->generateText($prompt, [
            'temperature' => 0.7,
            'max_tokens' => $tipo === 'corta' ? 150 : 500
        ]);
    }
}
