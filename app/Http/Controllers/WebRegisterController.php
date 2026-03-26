<?php

namespace App\Http\Controllers;

use App\Models\EmailConfirmation;
use App\Mail\EmailConfirmation as EmailConfirmationMail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class WebRegisterController extends Controller
{
    public function showForm()
    {
        return view('auth.register-web');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'shop' => 'required|string|max:255',
            'phone' => 'required|string|size:10|regex:/^[0-9]+$/',
            'email' => 'required|email|unique:users,email|unique:email_confirmations,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'shop.required' => 'El nombre de tu negocio es obligatorio.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.size' => 'El teléfono debe tener exactamente 10 dígitos.',
            'phone.regex' => 'El teléfono solo debe contener números.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo no es válido.',
            'email.unique' => 'Este correo ya está registrado. Si ya tienes cuenta, inicia sesión.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $confirmation = EmailConfirmation::create([
            'name' => $request->name,
            'shop' => $request->shop,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $url = route('email.confirmar', ['token' => $confirmation->token, 'source' => 'web']);
        Mail::to($confirmation->email)->send(new EmailConfirmationMail($confirmation->name, $url));

        return redirect()->route('web.register')
            ->with('success', '¡Registro exitoso! Revisa tu correo electrónico para confirmar tu cuenta. El enlace expira en 24 horas.');
    }
}
