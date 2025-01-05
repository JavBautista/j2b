<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\Role;
use App\Models\User;
use App\Models\Shop;
use App\Models\RequestsJ2b;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // Agregar esta línea



class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'shop_name' => ['required', 'string', 'max:255', 'unique:shops,name'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'token' => ['required', 'string'],
        ], [
            'shop_name.required' => 'El nombre de la tienda es obligatorio.',
            'shop_name.string' => 'El nombre de la tienda debe ser una cadena de texto.',
            'shop_name.max' => 'El nombre de la tienda no puede tener más de 255 caracteres.',
            'shop_name.unique' => 'El nombre de la tienda ya está en uso. Por favor, elige otro nombre.',

            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.string' => 'El correo electrónico debe ser una cadena de texto.',
            'email.email' => 'El correo electrónico debe ser una dirección de correo válida.',
            'email.max' => 'El correo electrónico no puede tener más de 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está registrado. Por favor, usa otro correo.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser una cadena de texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',

            'token.required' => 'El token es obligatorio.',
            'token.string' => 'El token debe ser una cadena de texto.',
        ]);

    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data){
        try {
            return DB::transaction(function () use ($data) {

                $shop = Shop::create([
                    'plan_id' => 1,
                    'active' => 1,
                    'name' => $data['shop_name'],
                    'cutoff'=>1
                ]);

                $user = User::create([
                    'shop_id' => $shop->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                ]);

                $user->roles()->attach(Role::where('name', 'admin')->first());

                // Confirmar el token solo después de crear la tienda y el usuario
                $requestJ2b = RequestsJ2b::where('token', $data['token'])->firstOrFail();
                $requestJ2b->confirmed = 1;
                $requestJ2b->save();

                return $user;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Verificar si la excepción es debido a una violación de unicidad
            if ($e->getCode() == '23000') {
                // Redirigir de nuevo al formulario de registro con un mensaje de error personalizado
                return redirect()->back()->withInput($data)->withErrors(['shop_name' => 'El nombre de la tienda ya está en uso. Por favor, elige otro nombre.']);
            } else {
                // En caso de otro tipo de excepción, simplemente lanzarla de nuevo
                throw $e;
            }
        }
    }

}
