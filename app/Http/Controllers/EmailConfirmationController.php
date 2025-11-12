<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Shop;
use App\Models\EmailConfirmation;
use App\Models\SubscriptionSetting;
use App\Mail\EmailConfirmation as EmailConfirmationMail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class EmailConfirmationController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'shop' => 'required|string|max:255',
            'phone' => 'required|string|size:10|regex:/^[0-9]+$/',
            'email' => 'required|email|unique:users,email|unique:email_confirmations,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors(),
            ], 422);
        }

        $confirmation = EmailConfirmation::create([
            'name' => $request->name,
            'shop' => $request->shop,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'avatar' => $request->avatar ?? null,
        ]);

        $url = route('email.confirmar', ['token' => $confirmation->token]);

        Mail::to($confirmation->email)->send(new EmailConfirmationMail($confirmation->name, $url));



        return response()->json([
            'ok' => true,
            'message' => 'Correo de confirmación enviado. Revisa tu bandeja de entrada.',
        ]);
    }//.store()

    public function confirmar($token)
    {
        return DB::transaction(function () use ($token) {
            // 1. Validar token
            $registro = EmailConfirmation::where('token', $token)->first();

            if (!$registro) {
                return view('emails.error_token');
            }

            if ($registro->expires_at && Carbon::now()->greaterThan($registro->expires_at)) {
                return view('emails.error_expirado');
            }

            try {
                // 2. Obtener configuración de trial desde BD
                $trialDays = SubscriptionSetting::get('trial_days', 30);

                // 3. Crear la tienda (Shop) con datos completos de suscripción
                $shop = Shop::create([
                    'plan_id' => 2, // Plan BASIC para trial
                    'active' => 1, // Activada por defecto
                    'name' => $registro->shop,
                    'cutoff' => now()->day, // Corte diario por defecto
                    'is_trial' => true,
                    'trial_ends_at' => now()->addDays($trialDays),
                    'subscription_status' => 'trial'
                ]);

                // 4. Crear usuario asociado a la tienda
                $user = User::create([
                    'shop_id' => $shop->id,
                    'name' => $registro->name,
                    'email' => $registro->email,
                    'password' => $registro->password,
                    'avatar' => $registro->avatar,
                    'phone' => $registro->phone
                ]);

                // 5. Asignar como owner de la tienda
                $shop->owner_user_id = $user->id;
                $shop->save();

                // 6. Asignar rol (client/admin según necesidad)
                $role = $this->determinarRolParaUsuario($registro);
                $user->roles()->attach($role);

                // 7. Eliminar registro temporal
                $registro->delete();

                // 8. Opcional: Enviar email de bienvenida
                //Mail::to($user->email)->send(new WelcomeEmail($user, $shop));

                return view('emails.confirmado');

            } catch (\Illuminate\Database\QueryException $e) {
                // Manejo específico de errores de DB
                if ($e->getCode() == '23000') {
                    // Verificar qué restricción de unicidad falló
                    if (str_contains($e->getMessage(), 'shops.name')) {
                        return view('emails.error_registro', [
                            'message' => 'El nombre de la tienda ya está en uso. Por favor contacta al soporte.'
                        ]);
                    } elseif (str_contains($e->getMessage(), 'users.email')) {
                        return view('emails.error_registro', [
                            'message' => 'El email ya está registrado. Por favor inicia sesión.'
                        ]);
                    }
                }
                
                // Error genérico
                return view('emails.error_registro', [
                    'message' => 'Ocurrió un error inesperado. Por favor intenta nuevamente.'
                ]);
            }
        });
    }

    // Método auxiliar para determinar el rol
    protected function determinarRolParaUsuario($registro)
    {
        // Lógica para asignar rol (puedes personalizar esto)
        return Role::where('name', 'admin')->first();
        
        // O si necesitas lógica más compleja:
        // return Role::where('name', $registro->esAdmin ? 'admin' : 'client')->first();
    }
}
