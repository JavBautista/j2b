<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $shop = $user->shop;

        if (!$shop) {
            return response()->json(['error' => 'No shop associated'], 403);
        }

        // Verificar si el shop está activo
        if (!$shop->active) {
            return response()->json([
                'error' => 'Shop suspended',
                'message' => 'Tu tienda ha sido suspendida por el administrador. Contacta a soporte.',
                'subscription_status' => 'suspended'
            ], 403);
        }

        // Verificar si la suscripción está activa
        if (!$shop->isActive()) {
            return response()->json([
                'error' => 'Subscription expired',
                'message' => 'Tu suscripción ha vencido. Reactiva tu plan para continuar usando la aplicación.',
                'subscription_status' => $shop->subscription_status,
                'trial_ends_at' => $shop->trial_ends_at,
                'subscription_ends_at' => $shop->subscription_ends_at,
                'grace_period_ends_at' => $shop->grace_period_ends_at,
            ], 402); // 402 Payment Required
        }

        // Todo OK, continuar
        return $next($request);
    }
}
