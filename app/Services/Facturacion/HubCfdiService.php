<?php

namespace App\Services\Facturacion;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class HubCfdiService
{
    protected string $baseUrl;
    protected string $apiToken;
    protected string $clientId;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('hubcfdi.base_url'), '/');
        $this->apiToken = config('hubcfdi.api_token');
        $this->clientId = config('hubcfdi.client_id');
        $this->timeout = config('hubcfdi.timeout', 30);

        if (empty($this->apiToken)) {
            throw new Exception('HUB CFDI API token no configurado en .env (HUB_CFDI_API_TOKEN)');
        }

        if (empty($this->clientId)) {
            throw new Exception('HUB CFDI Client ID no configurado en .env (HUB_CFDI_CLIENT_ID)');
        }
    }

    /**
     * Timbrar (generar) una factura CFDI
     *
     * @param array $payload JSON con datos de la factura (emisor, receptor, conceptos, impuestos)
     * @return array ['success' => bool, 'data' => mixed, 'error' => string|null]
     */
    public function timbrar(array $payload): array
    {
        return $this->request('POST', '/v1/facturacion/timbrar', $payload);
    }

    /**
     * Consultar una factura por UUID
     *
     * @param string $uuid UUID del timbrado
     * @return array
     */
    public function consultar(string $uuid): array
    {
        return $this->request('GET', "/v1/facturacion/consultar/{$uuid}");
    }

    /**
     * Descargar factura en formato XML o PDF
     *
     * @param string $uuid UUID del timbrado
     * @param string $formato 'xml' o 'pdf'
     * @return array
     */
    public function descargar(string $uuid, string $formato = 'xml'): array
    {
        return $this->request('GET', "/v1/facturacion/descargar/{$uuid}/{$formato}");
    }

    /**
     * Cancelar una factura
     *
     * @param string $uuid UUID del timbrado
     * @param string $motivo Clave del motivo de cancelación SAT (01, 02, 03, 04)
     * @param string|null $folioSustitucion UUID de la factura que sustituye (solo motivo 01)
     * @return array
     */
    public function cancelar(string $uuid, string $motivo, ?string $folioSustitucion = null): array
    {
        $queryParams = ['motivo' => $motivo];

        if ($folioSustitucion) {
            $queryParams['folio_sustitucion'] = $folioSustitucion;
        }

        $queryString = http_build_query($queryParams);

        return $this->request('DELETE', "/v1/facturacion/cancelar/{$uuid}?{$queryString}");
    }

    /**
     * Registrar un emisor vía API de compatibilidad
     *
     * @param array $data Datos del emisor (RFC, CSD, etc.)
     * @return array
     */
    public function registrarEmisor(array $data): array
    {
        return $this->request('POST', "/v1/compatibilidad/{$this->clientId}/RegistraEmisor", $data);
    }

    /**
     * Obtener timbres disponibles de la cuenta
     *
     * @return array
     */
    public function obtenerTimbres(): array
    {
        return $this->request('POST', "/v1/compatibilidad/{$this->clientId}/ObtieneTimbresDisponibles");
    }

    /**
     * Asignar timbres a un emisor específico
     *
     * @param string $rfc RFC del emisor
     * @param int $cantidad Cantidad de timbres a asignar
     * @return array
     */
    public function asignarTimbres(string $rfc, int $cantidad): array
    {
        return $this->request('POST', "/v1/compatibilidad/{$this->clientId}/AsignaTimbresEmisor", [
            'RfcEmisor' => $rfc,
            'CantidadTimbres' => $cantidad,
        ]);
    }

    /**
     * Ejecutar petición HTTP a la API de HUB CFDI
     *
     * @param string $method HTTP method (GET, POST, DELETE)
     * @param string $endpoint Ruta del endpoint
     * @param array|null $data Datos a enviar
     * @return array ['success' => bool, 'data' => mixed, 'error' => string|null]
     */
    protected function request(string $method, string $endpoint, ?array $data = null): array
    {
        try {
            $retryConfig = config('hubcfdi.retry', ['times' => 2, 'sleep' => 1000]);

            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'X-CLIENT-ID' => $this->clientId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->retry($retryConfig['times'], $retryConfig['sleep']);

            $url = $this->baseUrl . $endpoint;

            Log::info('HubCfdi Request', [
                'method' => $method,
                'url' => $url,
                'payload_keys' => $data ? array_keys($data) : null,
            ]);

            $response = match (strtoupper($method)) {
                'GET' => $http->get($url, $data),
                'POST' => $http->post($url, $data ?? []),
                'DELETE' => $http->delete($url, $data ?? []),
                default => throw new Exception("Método HTTP no soportado: {$method}"),
            };

            if (!$response->successful()) {
                $errorBody = $response->json();

                Log::error('HubCfdi API Error', [
                    'status' => $response->status(),
                    'endpoint' => $endpoint,
                    'error' => $errorBody ?? $response->body(),
                ]);

                return [
                    'success' => false,
                    'data' => null,
                    'error' => $errorBody['message'] ?? $errorBody['Mensaje'] ?? $errorBody['error'] ?? $response->body(),
                ];
            }

            $responseData = $response->json();

            Log::info('HubCfdi Response Success', [
                'endpoint' => $endpoint,
                'response_keys' => is_array($responseData) ? array_keys($responseData) : null,
            ]);

            return [
                'success' => true,
                'data' => $responseData['data'] ?? $responseData,
                'error' => null,
            ];

        } catch (Exception $e) {
            Log::error('HubCfdi Service Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
