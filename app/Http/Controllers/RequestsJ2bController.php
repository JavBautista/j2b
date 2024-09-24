<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use App\Models\RequestsJ2b;
use App\Mail\SolicitudRecibida;

class RequestsJ2bController extends Controller
{
     public function j2bSolicitar(){
        return view('pre_register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:requests_j2bs,email|unique:users,email',
            'phone' => 'required|string|max:15',
        ], [
            // Mensajes de error personalizados
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección de correo válida.',
            'email.unique' => 'Este correo electrónico ya ha sido registrado anteriormente, si requiere una nueva solicitud por favor contactenos directamente en contacto@levcore.app',
            'phone.required' => 'El teléfono es obligatorio.',
        ]);

        /*$request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('requests_j2bs', 'email'), // Validar en la tabla RequestsJ2b
                Rule::unique('users', 'email'),        // Validar en la tabla Users
            ],
            'phone' => 'required|string|max:15',
        ], [
            // Mensajes de error personalizados
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección de correo válida.',
            'email.unique' => 'Este correo electrónico ya ha sido registrado anteriormente, si requiere una nueva solicitud por favor contáctenos directamente en contacto@levcore.app',
            'phone.required' => 'El teléfono es obligatorio.',
        ]);*/


        $token = Str::random(60);

        // Crear una nueva instancia del modelo RequestsJ2b
        $requestJ2b = new RequestsJ2b();
        $requestJ2b->name = $request->name;
        $requestJ2b->email = $request->email;
        $requestJ2b->phone = $request->phone;
        $requestJ2b->token = $token;

        // Guardar en la base de datos
        $requestJ2b->save();

        // Enviar correo electrónico
        Mail::to($requestJ2b->email)->send(new SolicitudRecibida($requestJ2b));

        return redirect()->back()->with('success', '¡Gracias por registrarse! Revise su email para confirmar el registro.');

    }

    /*public function confirm(Request $request){
        $xtoken= $request->xtoken;
        try {
            // Buscar el registro con el token proporcionado y que no esté confirmado
            $requestJ2b = RequestsJ2b::where('token', $xtoken)
                                    ->where('confirmed', 0)
                                    ->firstOrFail();
            // Marcar el registro como confirmado
            $requestJ2b->confirmed = 1;
            $requestJ2b->save();
            // Redirigir a la vista para completar el registro con los datos
            return view('register_completar',['data'=>$requestJ2b])->with('success', 'Exito, su información ha sido validada.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si no se encuentra el registro o ya está confirmado, redirigir con un mensaje de error
            return view('pre_register_error')->with('error', 'El token proporcionado es inválido.');
        }
    }*/

    public function confirm(Request $request){
        $xtoken = $request->xtoken;
        try {
            // Buscar el registro con el token proporcionado
            $requestJ2b = RequestsJ2b::where('token', $xtoken)->firstOrFail();
            if ($requestJ2b->confirmed == 1) {
                return redirect()->route('pre_register_error')->with('error', 'El token ya está confirmado.');
            }
            // Redirigir a la vista para completar el registro con los datos
            return view('register_completar',['data'=>$requestJ2b])->with('success', 'Por favor complete el formulario.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return view('pre_register_error')->with('error', 'El token proporcionado es inválido.');
        } catch (\Exception $e) {
            return view('pre_register_error')->with('error', $e->getMessage());
        }
    }
}
