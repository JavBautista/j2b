<?php

namespace App\Services\Facturacion\Logging;

use App\Models\CfdiTimbradoLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Str;
use Throwable;

/**
 * Helper centralizado para logs estructurados del módulo de facturación.
 *
 * Plan maestro: xdev/facturacion/PLAN_LOGS_FACTURACION.md
 *
 * Escritura dual:
 *   1. Archivo (siempre): channel daily dedicado por área en storage/logs/cfdi/
 *   2. Tabla cfdi_timbrado_logs (solo eventos en config('cfdi-logging.persisted_events'))
 *
 * Uso típico:
 *   $logId = LogFacturacion::timbrado('cfdi.timbrado.success', [
 *       'shop_id' => $shop->id,
 *       'cfdi_invoice_id' => $cfdi->id,
 *       'uuid' => $uuid,
 *       'duration_ms' => $ms,
 *       'pipeline' => 'json',
 *       'request_payload' => $payloadEnviado,
 *       'response_payload' => $respTbt,
 *       'metadata' => ['emisor_rfc' => ..., 'total' => ...],
 *   ]);
 *
 * Si el insert falla por cualquier razón, el archivo queda como respaldo y
 * el log no rompe el flujo de negocio (try/catch silencioso, error a Log::warning).
 */
class LogFacturacion
{
    public static function timbrado(string $event, array $context = [], string $level = 'info'): ?int
    {
        return self::write('cfdi_timbrado', $event, $context, $level);
    }

    public static function cancelacion(string $event, array $context = [], string $level = 'info'): ?int
    {
        return self::write('cfdi_cancelacion', $event, $context, $level);
    }

    public static function complementoPago(string $event, array $context = [], string $level = 'info'): ?int
    {
        return self::write('cfdi_complemento_pago', $event, $context, $level);
    }

    public static function retenciones(string $event, array $context = [], string $level = 'info'): ?int
    {
        return self::write('cfdi_retenciones', $event, $context, $level);
    }

    public static function implocal(string $event, array $context = [], string $level = 'info'): ?int
    {
        return self::write('cfdi_implocal', $event, $context, $level);
    }

    public static function hub(string $event, array $context = [], string $level = 'info'): ?int
    {
        return self::write('cfdi_hub', $event, $context, $level);
    }

    /**
     * Sanitiza un array recursivamente, redactando claves cuyo nombre contenga
     * cualquier substring de la lista config('cfdi-logging.sensitive_keys').
     */
    public static function sanitize(array $data): array
    {
        $sensitiveKeys = (array) config('cfdi-logging.sensitive_keys', []);

        $walk = function ($value) use (&$walk, $sensitiveKeys) {
            if (!is_array($value)) {
                return $value;
            }
            $out = [];
            foreach ($value as $k => $v) {
                if (is_string($k) && self::isSensitive($k, $sensitiveKeys)) {
                    $out[$k] = '[REDACTED]';
                    continue;
                }
                $out[$k] = is_array($v) ? $walk($v) : $v;
            }
            return $out;
        };

        return $walk($data);
    }

    /**
     * Codifica payload a string. Si supera el umbral, comprime con gzip + base64
     * y prefija con "gzip:" para que el modelo sepa descomprimirlo al leer.
     */
    public static function encodePayload($payload): ?string
    {
        if ($payload === null) {
            return null;
        }
        $json = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return null;
        }

        $threshold = (int) config('cfdi-logging.compression_threshold_bytes', 32768);
        if (strlen($json) <= $threshold) {
            return $json;
        }

        $compressed = gzencode($json, 6);
        if ($compressed === false) {
            return $json;
        }

        return 'gzip:' . base64_encode($compressed);
    }

    private static function isSensitive(string $key, array $sensitiveKeys): bool
    {
        $normalized = strtolower($key);
        foreach ($sensitiveKeys as $sensitive) {
            if (str_contains($normalized, strtolower($sensitive))) {
                return true;
            }
        }
        return false;
    }

    private static function ambientContext(): array
    {
        if (app()->runningInConsole()) {
            return [
                'request_id' => Str::random(16),
                'source' => 'system',
            ];
        }

        try {
            $request = RequestFacade::instance();
        } catch (Throwable $e) {
            $request = null;
        }

        if (!$request) {
            return [
                'request_id' => Str::random(16),
                'source' => 'unknown',
            ];
        }

        $ambient = [
            'request_id' => $request->attributes->get('request_id') ?: Str::random(16),
            'source' => $request->attributes->get('request_source') ?: 'unknown',
        ];

        if ($request->user()) {
            $ambient['user_id'] = $request->user()->id ?? null;
        }

        return array_filter($ambient, fn($v) => $v !== null);
    }

    private static function shouldPersist(string $event): bool
    {
        $persisted = (array) config('cfdi-logging.persisted_events', []);
        return in_array($event, $persisted, true);
    }

    private static function inferStatus(string $event, array $context): string
    {
        if (isset($context['status'])) {
            return (string) $context['status'];
        }
        if (str_ends_with($event, '.success'))                       return 'success';
        if (str_ends_with($event, '.attempt'))                       return 'pending';
        if (str_ends_with($event, '.warning') || str_contains($event, '.warn')) return 'warning';
        if (str_contains($event, 'error'))                           return 'error';
        return 'pending';
    }

    private static function persist(string $event, array $context, array $ambient): ?int
    {
        try {
            $row = [
                'shop_id'         => $context['shop_id']         ?? null,
                'user_id'         => $context['user_id']         ?? ($ambient['user_id'] ?? null),
                'cfdi_invoice_id' => $context['cfdi_invoice_id'] ?? null,
                'receipt_id'      => $context['receipt_id']      ?? null,
                'request_id'      => $ambient['request_id'],
                'source'          => $ambient['source'] ?? 'unknown',
                'event_type'      => $event,
                'pipeline'        => $context['pipeline'] ?? 'json',
                'status'          => self::inferStatus($event, $context),
                'http_status'     => $context['http_status']     ?? null,
                'uuid'            => $context['uuid']            ?? null,
                'duration_ms'     => $context['duration_ms']     ?? null,
                'request_payload' => self::encodePayload(
                    isset($context['request_payload']) ? self::sanitize((array) $context['request_payload']) : null
                ),
                'response_payload' => self::encodePayload(
                    isset($context['response_payload']) ? self::sanitize((array) $context['response_payload']) : null
                ),
                'error_code'    => $context['error_code']    ?? null,
                'error_message' => $context['error_message'] ?? null,
                'attempts'      => $context['attempts']      ?? null,
                'metadata'      => isset($context['metadata']) ? self::sanitize((array) $context['metadata']) : null,
            ];

            $log = CfdiTimbradoLog::create($row);
            return $log->id;
        } catch (Throwable $e) {
            // No romper flujo de negocio si BD falla. Archivo queda como respaldo.
            Log::channel('cfdi_hub')->warning('LogFacturacion: persistencia BD fallida', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private static function write(string $channel, string $event, array $context, string $level): ?int
    {
        $ambient = self::ambientContext();
        $sanitized = self::sanitize($context);

        // Escritura a archivo (siempre).
        Log::channel($channel)->{$level}($event, array_merge(['event' => $event], $ambient, $sanitized));

        // Persistencia BD (solo eventos curados).
        if (self::shouldPersist($event)) {
            return self::persist($event, $context, $ambient);
        }

        return null;
    }
}
