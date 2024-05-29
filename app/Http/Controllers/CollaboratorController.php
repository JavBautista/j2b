<?php

namespace App\Http\Controllers;

use App\Models\Collaborator;
use App\Models\Shop;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class CollaboratorController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        if($buscar==''){
            $collaborators = Collaborator::where('shop_id',$shop->id)
                    ->orderBy('id','desc')
                    ->paginate(10);
        }else{
            $collaborators = Collaborator::where('shop_id',$shop->id)
                    ->where('name', 'like', '%'.$buscar.'%')
                    ->orderBy('id','desc')
                    ->paginate(10);
        }
        return $collaborators;

    }

    public function store(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        //creamos el usaurio
        $role_collaborator= Role::where('name', 'collaborator')->first();
        $new_user = new User();
        $new_user->name = $request->name;
        $new_user->email = $request->email;
        $new_user->password = Hash::make($request->password);
        $new_user->save();
        $new_user->roles()->attach($role_collaborator);

        //Ahora creamos al Colaborador
        $collaborator = new Collaborator;
        $collaborator->user_id=$new_user->id;
        $collaborator->shop_id=$shop->id;
        $collaborator->active=1;
        $collaborator->name=$request->name;
        $collaborator->email=$request->email;
        $collaborator->phone=$request->phone;
        $collaborator->movil=$request->movil;
        $collaborator->zip_code=$request->zip_code;
        $collaborator->address=$request->address;
        $collaborator->number_out=$request->number_out;
        $collaborator->number_int=$request->number_int;
        $collaborator->district=$request->district;
        $collaborator->city=$request->city;
        $collaborator->state=$request->state;
        $collaborator->reference=$request->reference;
        $collaborator->detail=$request->detail;
        $collaborator->observations=$request->observations;
        $collaborator->save();

        return response()->json([
                'ok'=>true,
                'collaborator' => $collaborator,
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
        $collaborator = Collaborator::findOrFail($request->id);
        $collaborator->phone=$request->phone;
        $collaborator->movil=$request->movil;
        $collaborator->zip_code=$request->zip_code;
        $collaborator->address=$request->address;
        $collaborator->number_out=$request->number_out;
        $collaborator->number_int=$request->number_int;
        $collaborator->district=$request->district;
        $collaborator->city=$request->city;
        $collaborator->state=$request->state;
        $collaborator->reference=$request->reference;
        $collaborator->detail=$request->detail;
        $collaborator->observations=$request->observations;
        $collaborator->save();
        return response()->json([
                'ok'=>true,
                'collaborator' => $collaborator,
        ]);
    }

    public function active(Request $request)
    {
        $collaborator = Collaborator::findOrFail($request->id);
        $collaborator->active = 1;
        // esactivar el usuario asociado
        if ($collaborator->user) {
            $collaborator->user->active = 1;
            $collaborator->user->save();
        }
        $collaborator->save();
        return response()->json([
            'ok'=>true,
            'collaborator' => $collaborator,
        ]);
    }
    public function inactive(Request $request)
    {
        $collaborator = Collaborator::findOrFail($request->id);
        $collaborator->active = 0;
        // esactivar el usuario asociado
        if ($collaborator->user) {
            $collaborator->user->active = 0;
            $collaborator->user->save();
        }

        $collaborator->save();
        return response()->json([
            'ok'=>true,
            'collaborator' => $collaborator,
        ]);
    }
}
