<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperadminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        /*
        if (! $request->user()->hasRole($role)) {
                return redirect('home');
            }
        return $next($request);

        Hemos agregado una redirección estándar a la ruta “home”, pero en esta línea podrás agregar lo que desees. Por ejemplo:
        // opción 1
        abort(403, “No tienes autorización para ingresar.”);
        // Opción 2
        return redirect(‘home’);
        */
        if( auth()->check() && auth()->user()->authorizeRoles(['superadmin']) )
            return $next($request);
        return redirect('/');
    }
}
