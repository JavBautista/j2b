<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\Shop;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AdministratorController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        if($buscar==''){
            $administrators = Administrator::with('user')
                    ->where('shop_id',$shop->id)
                    ->orderBy('id','desc')
                    ->paginate(10);
        }else{
            $administrators = Administrator::with('user')
                    ->where('shop_id',$shop->id)
                    ->where('name', 'like', '%'.$buscar.'%')
                    ->orderBy('id','desc')
                    ->paginate(10);
        }
        return $administrators;

    }

    public function store(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        //creamos el usaurio
        $role_admin= Role::where('name', 'admin')->first();
        $new_user = new User();
        $new_user->active = 1;
        $new_user->shop_id = $shop->id;
        $new_user->name = $request->name;
        $new_user->email = $request->email;
        $new_user->password = Hash::make($request->password);
        $new_user->save();
        $new_user->roles()->attach($role_admin);

        //Ahora creamos al Colaborador
        $administrator = new Administrator;
        $administrator->user_id=$new_user->id;
        $administrator->shop_id=$shop->id;
        $administrator->active=1;
        $administrator->name=$request->name;
        $administrator->email=$request->email;
        $administrator->phone=$request->phone;
        $administrator->movil=$request->movil;
        $administrator->zip_code=$request->zip_code;
        $administrator->address=$request->address;
        $administrator->number_out=$request->number_out;
        $administrator->number_int=$request->number_int;
        $administrator->district=$request->district;
        $administrator->city=$request->city;
        $administrator->state=$request->state;
        $administrator->reference=$request->reference;
        $administrator->detail=$request->detail;
        $administrator->observations=$request->observations;
        $administrator->save();
        $administrator->load('user');
        return response()->json([
                'ok'=>true,
                'administrator' => $administrator,
        ]);

    }

    public function verifyUserEmail(Request $request)
    {
        $email = $request->email;
        $existeUsuario = User::where('email', $email)->exists();
        return response()->json(['existeUsuario' => $existeUsuario]);

    }

    public function update(Request $request)
    {
        $administrator = Administrator::findOrFail($request->id);
        $administrator->phone=$request->phone;
        $administrator->movil=$request->movil;
        $administrator->zip_code=$request->zip_code;
        $administrator->address=$request->address;
        $administrator->number_out=$request->number_out;
        $administrator->number_int=$request->number_int;
        $administrator->district=$request->district;
        $administrator->city=$request->city;
        $administrator->state=$request->state;
        $administrator->reference=$request->reference;
        $administrator->detail=$request->detail;
        $administrator->observations=$request->observations;
        $administrator->save();
        $administrator->load('user');
        return response()->json([
                'ok'=>true,
                'administrator' => $administrator,
        ]);
    }

    public function active(Request $request)
    {
        $administrator = Administrator::findOrFail($request->id);
        $administrator->active = 1;
        // esactivar el usuario asociado
        if ($administrator->user) {
            $administrator->user->active = 1;
            $administrator->user->save();
        }
        $administrator->save();
        return response()->json([
            'ok'=>true,
            'administrator' => $administrator,
        ]);
    }
    public function inactive(Request $request)
    {
        $administrator = Administrator::findOrFail($request->id);
        $administrator->active = 0;
        // esactivar el usuario asociado
        if ($administrator->user) {
            $administrator->user->active = 0;
            $administrator->user->save();
        }

        $administrator->save();
        return response()->json([
            'ok'=>true,
            'administrator' => $administrator,
        ]);
    }

    public function updateLimited(Request $request)
    {
        $administrator = Administrator::findOrFail($request->id);
        // Verificar si el administrador tiene un usuario asociado
        if ($administrator->user) {
            $lmited_actual = $administrator->user->limited;

            // Alternar el valor de 'limited'
            $administrator->user->limited = $lmited_actual ? 0 : 1;

            // Guardar los cambios en el usuario
            $administrator->user->save();
        }

        return response()->json([
            'ok' => true,
            'administrator' => $administrator,
        ]);
    }
}
