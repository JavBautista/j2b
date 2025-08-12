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
