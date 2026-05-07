<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SnmpAgentToken;

class SnmpTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization', '');

        if (!str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'ok' => false,
                'message' => 'Token de autenticación requerido.',
            ], 401);
        }

        $token = trim(substr($header, 7));

        $tokenModel = SnmpAgentToken::where('token', $token)
            ->where('active', true)
            ->first();

        if (!$tokenModel) {
            return response()->json([
                'ok' => false,
                'message' => 'Token inválido o desactivado.',
            ], 401);
        }

        $tokenModel->forceFill([
            'last_used_at' => now(),
            'last_used_ip' => $request->ip(),
        ])->saveQuietly();

        $request->attributes->set('snmp_token', $tokenModel);

        return $next($request);
    }
}
