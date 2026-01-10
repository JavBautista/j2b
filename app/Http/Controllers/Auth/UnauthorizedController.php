<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnauthorizedController extends Controller
{
    /**
     * Show the unauthorized access page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('auth.unauthorized');
    }

    /**
     * Logout user and redirect to home with message.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $userName = $user ? $user->name : 'Usuario';
        
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/')->with('warning', "Hola {$userName}, tu cuenta no tiene permisos para acceder al panel de administración.");
    }
}