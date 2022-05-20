<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
     public function signUp(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string'
        ]);

         if ($validator->fails()) {
            return response()->json([
                'ok'=>false,
                'message' => 'Fallo la creacion de usaurio.',
                'errors'=> $validator->errors()
            ]);
         }


        $user = new User();
        $user->name=$request->name;
        $user->email=$request->email;
        $user->password=bcrypt($request->password);
        $user->avatar= isset($request->avatar)?$request->avatar:null;
        $user->save();

        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $token->save();

        return response()->json([
            'ok'=>true,
            'access_token' => $tokenResult->accessToken,
            'message' => 'Successfully created user!'
        ]);
    }

    /**
     * Inicio de sesión y creación de token
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok'=>false,
                'message' => 'Fallo logueo de usaurio.',
                'errors'=> $validator->errors()
            ]);
         }

        $credentials = request(['email', 'password']);

        if (!Auth::attempt($credentials))
            return response()->json([
                'ok'=>false,
                'message' => 'Unauthorized'
            ]);

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');

        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();

        return response()->json([
            'ok'=>true,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString()
        ]);
    }

    /**
     * Cierre de sesión (anular el token)
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Obtener el objeto User como json
     */
    public function user(Request $request)
    {
        return response()->json([
            'ok'=>true,
            'usuario'=>$request->user(),
            ]);
    }

    public function update(Request $request)
    {
        /*$user_s = $request->user();
        $user_id= $user_s->id
        $user = User::findOrFail($user_id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();
        return response()->json([
            'ok'=>true,
            'usuario'=>$request->user(),
            ]);
            */
    }
}
