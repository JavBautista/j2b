<?php

namespace App\Services\Facturacion\Logging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Str;

/**
 * Helper centralizado para logs estructurados del módulo de facturación.
 *
 * Plan maestro: xdev/facturacion/PLAN_LOGS_FACTURACION.md
 *
 * Sesión 1 (actual): solo escritura a archivo (channels dedicados).
 * Sesión 2: persistencia en cfdi_timbrado_logs y sanitización avanzada.
 *
 * Uso típico:
 *   LogFacturacion::timbrado('cfdi.timbrado.success', [
 *       'shop_id' => $shop->id,
 *       'cfdi_invoice_id' => $cfdi->id,
 *       'uuid' => $uuid,
 *       'duration_ms' => $ms,
 *   ]);
 */
class LogFacturacion
{
    private const SENSITIVE_KEYS = [
        'password', 'pwd', 'pass',
        'csd_password', 'csd_pass', 'key_password',
        'api_token', 'token', 'bearer',
        'authorization', 'x-api-key',
        'sello', 'certificado', 'private_key',
    ];

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
     * Sanitiza un array recursivamente, redactando claves sensibles.
     */
    public static function sanitize(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && self::isSensitive($key)) {
                $out[$key] = '[REDACTED]';
                continue;
            }
            if (is_array($value)) {
                $out[$key] = self::sanitize($value);
                continue;
            }
            $out[$key] = $value;
        }
        return $out;
    }

    private static function isSensitive(string $key): bool
    {
        $normalized = strtolower($key);
        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains($normalized, $sensitive)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Estructura común que se agrega a todo log: request_id, source, user_id.
     */
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
        } catch (\Throwable $e) {
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

    private static function write(string $channel, string $event, array $context, string $level): ?int
    {
        $payload = array_merge(
            ['event' => $event],
            self::ambientContext(),
            self::sanitize($context),
        );

        Log::channel($channel)->{$level}($event, $payload);

        // Sesión 2 implementará persistencia en BD y retornará el ID del row.
        return null;
    }
}
