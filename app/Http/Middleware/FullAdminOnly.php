<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FullAdminOnly
{
    /**
     * Solo permite acceso a admins full (limited = 0 o null)
     * Bloquea a admins limitados (limited = 1)
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->isLimitedAdmin()) {
            abort(403, 'Acceso restringido. Esta sección requiere permisos de administrador completo.');
        }

        return $next($request);
    }
}
