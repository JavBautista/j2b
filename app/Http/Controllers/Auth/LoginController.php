<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Validar credenciales adicionales (shop y usuario activos)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $credentials
     * @return bool
     */
    protected function credentials($request)
    {
        return array_merge($request->only($this->username(), 'password'), ['active' => 1]);
    }

    /**
     * Validar que el shop también esté activo después de autenticación
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated($request, $user)
    {
        // Verificar que el shop esté activo
        if (!$user->shop || !$user->shop->active) {
            auth()->logout();
            return redirect()->back()
                ->withErrors(['email' => 'Tu tienda ha sido desactivada. Contacta a soporte.'])
                ->withInput($request->only('email'));
        }
    }

    /**
     * Get the post-login redirect path based on user role.
     *
     * @return string
     */
    public function redirectTo()
    {
        $user = auth()->user();
        
        if ($user && $user->hasRole('superadmin')) {
            return '/superadmin';
        }
        
        if ($user && $user->hasRole('admin')) {
            return '/admin';
        }
        
        // Para roles no autorizados en el frontend web (client, collaborator, chatbot, etc.)
        // los enviamos a la página de no autorizado donde se cerrará su sesión
        return '/unauthorized';
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
