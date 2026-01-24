<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\SubscriptionSetting;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // No aplicar middleware aquí porque HomeController maneja tanto 
        // usuarios autenticados como no autenticados
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        // Si el usuario no está autenticado, mostrar landing público
        if (!auth()->check()) {
            $trialDays = SubscriptionSetting::get('trial_days', 30);
            return view('web.index', compact('trialDays'));
        }
        
        // Redirecciones por rol para usuarios autenticados
        $request->user()->authorizeRoles(['superadmin', 'admin', 'client']);

        if($request->user()->hasRole('superadmin'))
            return redirect('/superadmin');

        if($request->user()->hasRole('admin'))
            return redirect('/admin');

        // Para roles no autorizados (client, collaborator, etc.) 
        // los enviamos a /unauthorized donde se cerrará su sesión
        return redirect('/unauthorized');
    }

    public function passwordReset(Request $request){
        $user = auth()->user();
        
        if($user->hasRole('superadmin')) {
            return view('superadmin.reset_password');
        } elseif($user->hasRole('admin')) {
            return view('admin.reset_password');  
        } else {
            return view('user.reset_password');
        }
    }

    public function updatePassword(Request $request){

        $user_id=$request->user()->id;
        $user = User::find($user_id);
        $user->password = Hash::make( $request->input('password') );
        $user->setRememberToken(Str::random(60));
        $user->save();

        Auth::login($user);

        /*
            $request->user()->authorizeRoles(['seller', 'admin', 'buyer']);

            if($request->user()->hasRole('seller'))
                return redirect('/seller');

            if($request->user()->hasRole('buyer'))
                return redirect('/buyer');

            if($request->user()->hasRole('admin'))
                return redirect('/admin');

            return redirect('/');
            //return view('user.reset_password');
        */
        return redirect('/');

    }

    protected function guard()
    {
        return Auth::guard();
    }
}
