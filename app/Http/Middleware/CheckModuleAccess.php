<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Bloquea el acceso a rutas de un módulo vendible si la tienda no lo tiene contratado.
 * Uso: ->middleware('module:cfdi')  (o 'module:tasks', 'module:gps').
 * Los módulos core pasan siempre (Shop::hasModule devuelve true para ellos).
 */
class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, string $moduleKey)
    {
        $shop = $request->user()?->shop;

        // Caso defensivo: sin tienda (ej. superadmin) no se aplica el gating.
        if ($shop && !$shop->hasModule($moduleKey)) {
            $mensaje = 'Tu plan no incluye este módulo. Contacta a soporte para activarlo.';

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $mensaje], 403);
            }

            return redirect()->route('admin.index')->with('error', $mensaje);
        }

        return $next($request);
    }
}
