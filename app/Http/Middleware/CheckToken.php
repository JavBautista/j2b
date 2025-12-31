<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\RequestsJ2b;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        $token = $request->query('token');

        if (!$token || !RequestsJ2b::where('token', $token)->exists()) {
            return redirect()->route('solicitud.error')->with('error', 'Debe completar el pre-registro antes de registrarse.');
        }

        return $next($request);
    }
}
