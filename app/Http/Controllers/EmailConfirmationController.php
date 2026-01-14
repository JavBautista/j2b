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
            // Mensaje más específico dependiendo del error
            $mensaje = 'Por favor verifica los datos ingresados.';

            if ($validator->errors()->has('email')) {
                $emailErrors = $validator->errors()->get('email');
                if (str_contains($emailErrors[0], 'unique')) {
                    $mensaje = 'Este email ya está registrado. Si ya tienes cuenta, inicia sesión o recupera tu contraseña.';
                } else if (str_contains($emailErrors[0], 'email')) {
                    $mensaje = 'El formato del email no es válido.';
                }
            } else if ($validator->errors()->has('phone')) {
                $mensaje = 'El teléfono debe tener exactamente 10 dígitos numéricos.';
            } else if ($validator->errors()->has('password')) {
                $mensaje = 'La contraseña debe tener al menos 8 caracteres.';
            } else if ($validator->errors()->has('name')) {
                $mensaje = 'El nombre es obligatorio.';
            } else if ($validator->errors()->has('shop')) {
                $mensaje = 'El nombre de la tienda es obligatorio.';
            }

            return response()->json([
                'ok' => false,
                'message' => $mensaje,
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
                // 2. Obtener configuración inicial desde BD (se guarda en la tienda para personalización futura)
                $trialDays = SubscriptionSetting::get('trial_days', 30);
                $gracePeriodDays = SubscriptionSetting::get('grace_period_days', 7);
                $plan = \App\Models\Plan::find(2); // Plan BASIC

                // 3. Crear la tienda (Shop) con datos completos de suscripción
                $shop = Shop::create([
                    'plan_id' => 2, // Plan BASIC para trial
                    'monthly_price' => $plan ? $plan->price : null, // Precio inicial del plan
                    'active' => 1, // Activada por defecto
                    'name' => $registro->shop,
                    'cutoff' => now()->day, // Corte diario por defecto
                    'is_trial' => true,
                    'trial_days' => $trialDays, // Guardar días de trial asignados
                    'trial_ends_at' => now()->addDays($trialDays),
                    'grace_period_days' => $gracePeriodDays, // Guardar días de gracia asignados
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

    /**
     * Reenviar email de confirmación
     */
    public function resendConfirmation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:email_confirmations,email'
        ]);

        if ($validator->fails()) {
            // Mensaje específico dependiendo del error
            $mensaje = 'Por favor verifica el email ingresado.';

            if ($validator->errors()->has('email')) {
                $emailErrors = $validator->errors()->get('email');
                if (str_contains($emailErrors[0], 'exists') || str_contains($emailErrors[0], 'selected')) {
                    $mensaje = 'No encontramos un registro pendiente con este email. Verifica tu email o intenta registrarte nuevamente.';
                } else if (str_contains($emailErrors[0], 'email')) {
                    $mensaje = 'El formato del email no es válido.';
                }
            }

            return response()->json([
                'ok' => false,
                'message' => $mensaje
            ], 422);
        }

        // Buscar el registro pendiente
        $confirmation = EmailConfirmation::where('email', $request->email)->first();

        if (!$confirmation) {
            return response()->json([
                'ok' => false,
                'message' => 'No encontramos un registro pendiente con este email. Es posible que ya hayas confirmado tu cuenta.'
            ], 404);
        }

        // Verificar si el token ya expiró (opcional - eliminar registro expirado)
        if ($confirmation->expires_at && Carbon::now()->greaterThan($confirmation->expires_at)) {
            $confirmation->delete();
            return response()->json([
                'ok' => false,
                'message' => 'Tu solicitud de registro ha expirado. Por favor regístrate nuevamente.'
            ], 410);
        }

        // Reenviar el email de confirmación
        $url = route('email.confirmar', ['token' => $confirmation->token]);
        Mail::to($confirmation->email)->send(new EmailConfirmationMail($confirmation->name, $url));

        return response()->json([
            'ok' => true,
            'message' => 'Hemos reenviado el correo de confirmación. Revisa tu bandeja de entrada.'
        ]);
    }
}
