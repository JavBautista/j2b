<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Infraestructura compartida de observabilidad (no específica de CFDI).
 *
 * Marca cada request con:
 *   - request_source: 'plataforma' | 'ionic' | 'system' | 'unknown'
 *   - request_id: correlacional para agrupar logs de la misma operación
 *
 * Cualquier módulo del sistema puede leer estos atributos vía
 *   $request->attributes->get('request_source')
 *   $request->attributes->get('request_id')
 * para enriquecer sus propios logs sin tocar Kernel.
 *
 * El header X-Request-Id se devuelve al cliente para facilitar reportes.
 */
class TagRequestSource
{
    private const VALID_SOURCES = ['plataforma', 'ionic', 'system'];

    public function handle(Request $request, Closure $next)
    {
        $source = $this->resolveSource($request);
        $requestId = Str::random(16);

        $request->attributes->set('request_source', $source);
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('X-Request-Id', $requestId);
        }

        return $response;
    }

    private function resolveSource(Request $request): string
    {
        if ($request->hasHeader('X-Client-Source')) {
            $declared = strtolower(trim($request->header('X-Client-Source')));
            if (in_array($declared, self::VALID_SOURCES, true)) {
                return $declared;
            }
        }

        if (str_starts_with($request->path(), 'api/')) {
            return 'ionic';
        }

        if ($request->hasSession() && $request->user() !== null) {
            return 'plataforma';
        }

        return 'unknown';
    }
}
