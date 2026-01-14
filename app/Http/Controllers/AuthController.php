<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordReset as PasswordResetMail;


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
            // Mensaje más específico dependiendo del error
            $mensaje = 'Por favor verifica los datos ingresados.';

            if ($validator->errors()->has('email')) {
                $emailErrors = $validator->errors()->get('email');
                if (str_contains($emailErrors[0], 'unique')) {
                    $mensaje = 'Este email ya está registrado. Intenta iniciar sesión o recupera tu contraseña.';
                } else if (str_contains($emailErrors[0], 'email')) {
                    $mensaje = 'El formato del email no es válido.';
                }
            }

            return response()->json([
                'ok'=>false,
                'message' => $mensaje,
                'errors'=> $validator->errors()
            ], 422);
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
            'message' => '¡Cuenta creada exitosamente! Bienvenido.'
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
                'message' => 'Por favor verifica tu email y contraseña.',
                'errors'=> $validator->errors()
            ], 422);
         }

        $credentials = request(['email', 'password']);

        // Verificar si las credenciales son correctas
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'ok'=>false,
                'message' => 'Email o contraseña incorrectos. Verifica tus datos e intenta nuevamente.'
            ], 401);
        }

        $user = $request->user();
        $shop = $user->shop;

        // Validar si la tienda está activa
        if (!$shop->active) {
            // Cerrar sesión para limpiar el intento
            Auth::logout();
            return response()->json([
                'ok'=>false,
                'message' => 'Tu tienda ha sido desactivada. Contacta al soporte para más información.'
            ], 403);
        }

        // Validar si el usuario está activo
        if (!$user->active) {
            Auth::logout();
            return response()->json([
                'ok'=>false,
                'message' => 'Tu usuario ha sido desactivado. Contacta al administrador de tu tienda.'
            ], 403);
        }

        $tokenResult = $user->createToken('Personal Access Token');



        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();

        // Verificar si el usuario ha aceptado los términos
        $accepted_terms = $user->accepted_terms;

        $user = $request->user()->load('roles')->load('client');

        return response()->json([
            'ok'=>true,
            'accepted_terms'=>$accepted_terms,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString(),
            'user'=> $user,
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
        // 🔥 MODIFICADO: Cargar shop con plan y plan_features para verificar suscripción
        $user = $request->user()
            ->load('roles')
            ->load('client')
            ->load('shop.plan');

        // Si el usuario tiene shop, cargar también las features del plan
        if ($user->shop && $user->shop->plan) {
            $user->shop->load('plan.features');
        }

        // 🔥 NUEVO: Validar que la tienda esté activa (por si fue desactivada después del login)
        if ($user->shop && !$user->shop->active) {
            return response()->json([
                'ok' => false,
                'message' => 'Tu tienda ha sido desactivada. Contacta al soporte para más información.'
            ], 403);
        }

        // 🔥 NUEVO: Validar que el usuario esté activo (por si fue desactivado después del login)
        if (!$user->active) {
            return response()->json([
                'ok' => false,
                'message' => 'Tu usuario ha sido desactivado. Contacta al administrador de tu tienda.'
            ], 403);
        }

        return response()->json([
            'ok'=>true,
            'usuario'=>$user,
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

    public function updateTerminos(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->update(['accepted_terms' => true]);
            return response()->json(['ok' => true, 'term'=>'si', 'user'=>$user]); // Puedes enviar un nuevo token si es necesario.
        }
        return response()->json(['ok' => false, 'term'=>'no', 'user'=>$user]);
    }

    /**
     * Solicitar recuperación de contraseña
     * Envía email con link para resetear
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            // Mensaje más claro dependiendo del error
            $mensaje = 'Por favor verifica el email ingresado.';

            if ($validator->errors()->has('email')) {
                $emailErrors = $validator->errors()->get('email');
                if (str_contains($emailErrors[0], 'exists') || str_contains($emailErrors[0], 'selected')) {
                    $mensaje = 'No encontramos una cuenta con este email. Verifica que sea correcto.';
                } else if (str_contains($emailErrors[0], 'email')) {
                    $mensaje = 'El formato del email no es válido.';
                }
            }

            return response()->json([
                'ok' => false,
                'message' => $mensaje,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'No encontramos una cuenta registrada con este email.'
            ], 404);
        }

        // Generar token de recuperación
        $token = \Str::random(60);

        // Guardar token en base de datos
        \DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => $token, // Sin hashear para poder validar después
                'created_at' => now()
            ]
        );

        // Generar URL de reseteo
        $url = route('password.reset.form', ['token' => $token]);

        // Enviar email con link de recuperación
        Mail::to($user->email)->send(new PasswordResetMail($user->name, $url));

        return response()->json([
            'ok' => true,
            'message' => 'Te hemos enviado un correo con un enlace para restablecer tu contraseña.'
        ]);
    }

    /**
     * Mostrar formulario de reseteo de contraseña (página web)
     */
    public function showResetForm($token)
    {
        // Validar que el token existe y no ha expirado
        $passwordReset = \DB::table('password_resets')
            ->where('token', $token)
            ->first();

        if (!$passwordReset) {
            return view('emails.error_token');
        }

        // Verificar expiración (24 horas)
        if (now()->diffInHours($passwordReset->created_at) > 24) {
            return view('emails.error_expirado');
        }

        return view('emails.password-reset-form', [
            'token' => $token,
            'email' => $passwordReset->email
        ]);
    }

    /**
     * Procesar reseteo de contraseña desde formulario web
     */
    public function processResetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|string|min:8|same:password'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Buscar token en base de datos
        $passwordReset = \DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$passwordReset) {
            return view('emails.error_token');
        }

        // Verificar que no haya expirado (24 horas)
        if (now()->diffInHours($passwordReset->created_at) > 24) {
            return view('emails.error_expirado');
        }

        // Actualizar contraseña
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return view('emails.error_token');
        }

        $user->password = bcrypt($request->password);
        $user->save();

        // Eliminar token usado
        \DB::table('password_resets')
            ->where('email', $request->email)
            ->delete();

        return view('emails.password-reset-success');
    }
}
