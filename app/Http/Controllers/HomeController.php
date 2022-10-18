<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function passwordReset(Request $request){
        return view('user.reset_password');
    }

    public function updatePassword(Request $request){

        $user_id=$request->user()->id;
        $user = User::find($user_id);
        $user->password = Hash::make( $request->input('password') );
        $user->setRememberToken(Str::random(60));
        $user->save();

        $this->guard()->login($user);

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
