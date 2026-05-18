<?php

namespace App\Services\Facturacion;

use App\Services\Facturacion\Logging\LogFacturacion;
use Illuminate\Support\Facades\Http;
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
     * Timbrar un CFDI vía endpoint XML de compatibilidad.
     * Se envía el XML SIN sellar; TBT lo sella con el CSD del panel.
     * Usado para facturas con complementos no soportados por API JSON
     * (ej. implocal v1.0).
     *
     * @param string $xml CFDI 4.0 completo (raw XML)
     * @return array ['success' => bool, 'data' => mixed, 'error' => string|null]
     */
    public function timbrarCompat(string $xml): array
    {
        return $this->requestXml(
            'POST',
            "/v1/compatibilidad/{$this->clientId}/TimbraCFDI",
            $xml
        );
    }

    /**
     * Cancelar un CFDI timbrado por la API de compatibilidad.
     * NO usar el endpoint de cancelación JSON con UUIDs timbrados por compat.
     *
     * @param string $rfc RFC del emisor
     * @param string $uuid UUID a cancelar
     * @param string $motivo "01"..."04"
     * @param string|null $folioSustitucion UUID sustituto (solo motivo "01")
     * @return array
     */
    public function cancelarCompat(string $rfc, string $uuid, string $motivo, ?string $folioSustitucion = null): array
    {
        $body = [
            'Rfc' => $rfc,
            'Uuid' => $uuid,
            'Motivo' => $motivo,
        ];
        if ($folioSustitucion) {
            $body['FolioSustitucion'] = $folioSustitucion;
        }

        return $this->request('POST', "/v1/compatibilidad/{$this->clientId}/CancelaCFDI", $body);
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
        $startedAt = microtime(true);
        $url = $this->baseUrl . $endpoint;

        LogFacturacion::hub('cfdi.hub.request', [
            'method' => $method,
            'url' => $url,
            'endpoint' => $endpoint,
            'request_payload' => $data,
        ]);

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

            $response = match (strtoupper($method)) {
                'GET' => $http->get($url, $data),
                'POST' => $http->post($url, $data ?? []),
                'DELETE' => $http->delete($url, $data ?? []),
                default => throw new Exception("Método HTTP no soportado: {$method}"),
            };

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMsg = $errorBody['message'] ?? $errorBody['Mensaje'] ?? $errorBody['error'] ?? $response->body();

                LogFacturacion::hub('cfdi.hub.api_error', [
                    'endpoint' => $endpoint,
                    'http_status' => $response->status(),
                    'duration_ms' => $durationMs,
                    'request_payload' => $data,
                    'response_payload' => is_array($errorBody) ? $errorBody : ['raw' => $response->body()],
                    'error_message' => is_string($errorMsg) ? $errorMsg : json_encode($errorMsg),
                ], 'error');

                return [
                    'success' => false,
                    'data' => null,
                    'error' => $errorMsg,
                ];
            }

            $responseData = $response->json();

            LogFacturacion::hub('cfdi.hub.response', [
                'endpoint' => $endpoint,
                'http_status' => $response->status(),
                'duration_ms' => $durationMs,
                'response_payload' => is_array($responseData) ? $responseData : ['raw' => $response->body()],
            ]);

            return [
                'success' => true,
                'data' => $responseData['data'] ?? $responseData,
                'error' => null,
            ];

        } catch (Exception $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $errorMsg = $e->getMessage();

            // Extraer mensaje legible del JSON de la API si viene en la excepción
            if (preg_match('/\{.*\}/s', $errorMsg, $matches)) {
                $json = json_decode($matches[0], true);
                if ($json) {
                    $errorMsg = $json['message'] ?? $json['Mensaje'] ?? $errorMsg;
                }
            }

            LogFacturacion::hub('cfdi.hub.api_error', [
                'endpoint' => $endpoint,
                'duration_ms' => $durationMs,
                'request_payload' => $data,
                'error_code' => 'exception',
                'error_message' => $e->getMessage(),
            ], 'error');

            return [
                'success' => false,
                'data' => null,
                'error' => $errorMsg,
            ];
        }
    }

    /**
     * Variante de request() para endpoints que aceptan/envían XML (compatibility).
     * Diferencias clave vs request() JSON:
     *  - Content-Type: application/xml
     *  - Body es string raw (no array)
     *  - Logueamos un snippet del XML (truncado) además del payload completo
     */
    protected function requestXml(string $method, string $endpoint, string $xml): array
    {
        $startedAt = microtime(true);
        $url = $this->baseUrl . $endpoint;

        LogFacturacion::hub('cfdi.hub.request', [
            'method' => $method,
            'url' => $url,
            'endpoint' => $endpoint,
            'content_type' => 'application/xml',
            'request_payload' => ['xml' => $xml, 'xml_bytes' => strlen($xml)],
        ]);

        try {
            $retryConfig = config('hubcfdi.retry', ['times' => 2, 'sleep' => 1000]);

            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'X-CLIENT-ID' => $this->clientId,
                'Content-Type' => 'application/xml',
                'Accept' => 'application/json',
            ])
                ->withBody($xml, 'application/xml')
                ->timeout($this->timeout)
                ->retry($retryConfig['times'], $retryConfig['sleep']);

            $response = match (strtoupper($method)) {
                'POST' => $http->post($url),
                default => throw new Exception("Método HTTP no soportado en requestXml: {$method}"),
            };

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if (!$response->successful()) {
                $errorBody = $response->json() ?? ['raw' => $response->body()];
                $errorMsg = $errorBody['message'] ?? $errorBody['Mensaje'] ?? $errorBody['error'] ?? $response->body();

                LogFacturacion::hub('cfdi.hub.api_error', [
                    'endpoint' => $endpoint,
                    'http_status' => $response->status(),
                    'duration_ms' => $durationMs,
                    'request_payload' => ['xml_bytes' => strlen($xml)],
                    'response_payload' => is_array($errorBody) ? $errorBody : ['raw' => $response->body()],
                    'error_message' => is_string($errorMsg) ? $errorMsg : json_encode($errorMsg),
                ], 'error');

                return [
                    'success' => false,
                    'data' => null,
                    'error' => $errorMsg,
                ];
            }

            $responseData = $response->json();

            LogFacturacion::hub('cfdi.hub.response', [
                'endpoint' => $endpoint,
                'http_status' => $response->status(),
                'duration_ms' => $durationMs,
                'response_payload' => is_array($responseData) ? $responseData : ['raw' => $response->body()],
            ]);

            return [
                'success' => true,
                'data' => $responseData['data'] ?? $responseData,
                'error' => null,
            ];

        } catch (Exception $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            LogFacturacion::hub('cfdi.hub.api_error', [
                'endpoint' => $endpoint,
                'duration_ms' => $durationMs,
                'request_payload' => ['xml_bytes' => strlen($xml)],
                'error_code' => 'exception',
                'error_message' => $e->getMessage(),
            ], 'error');

            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
