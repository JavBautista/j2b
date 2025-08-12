<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebAccessMiddleware
{
    /**
     * Handle an incoming request.
     * Este middleware se asegura de que solo usuarios autorizados 
     * accedan a rutas protegidas del frontend web.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Si no está autenticado, Laravel Auth se encarga
        if (!Auth::check()) {
            return $next($request);
        }
        
        $user = Auth::user();
        
        // Solo superadmin y admin pueden acceder al frontend web
        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return $next($request);
        }
        
        // Cualquier otro rol (client, collaborator, chatbot, etc.) 
        // es redirigido a unauthorized donde se cerrará su sesión
        \Log::info('Usuario con rol no autorizado intentó acceder al frontend web', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_roles' => $user->roles->pluck('name'),
            'requested_url' => $request->url()
        ]);
        
        return redirect('/unauthorized');
    }
}