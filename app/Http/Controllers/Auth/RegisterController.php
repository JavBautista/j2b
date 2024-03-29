<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\Role;
use App\Models\User;
use App\Models\Shop;
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
            'shop_name' => ['required', 'string', 'max:255','unique:shops,name'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
                    'name' => $data['shop_name']
                ]);

                $shop_id=$shop->id;

                $user = User::create([
                    'shop_id' => $shop_id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                ]);

                $user->roles()->attach(Role::where('name', 'client')->first());

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
