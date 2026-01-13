<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Role;
use App\Models\Shop;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function get(Request $request)
    {
        if(!$request->ajax()) return redirect('/');

        $shops = Shop::where('active',1)->get();
        $roles = Role::orderBy('name')->get();

        $users = User::with('shop')->with('roles')
                ->when($request->buscar != '', function ($query) use ($request) {
                    return $query->where($request->criterio, 'like', '%'.$request->buscar.'%');
                })
                ->when($request->estatus != '', function ($query) use ($request) {
                    if ($request->estatus === 'active') {
                        return $query->where('active', 1);
                    } elseif ($request->estatus === 'inactive') {
                        return $query->where('active', 0);
                    }
                })
                ->when($request->rol != '', function ($query) use ($request) {
                    return $query->whereHas('roles', function ($q) use ($request) {
                        $q->where('roles.id', $request->rol);
                    });
                })
                ->orderBy('id','desc')
                ->paginate(20);

        return [
            'pagination'=>[
                'total'=> $users->total(),
                'current_page'=> $users->currentPage(),
                'per_page'=> $users->perPage(),
                'last_page'=> $users->lastPage(),
                'from'=> $users->firstItem(),
                'to'=> $users->lastItem(),
            ],
            'users'=>$users,
            'shops'=>$shops,
            'roles'=>$roles,
        ];
    }//.get()

    public function store(Request $request)
    {
        if(!$request->ajax()) return redirect('/');

        // Validar
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'shop_id' => 'required|exists:shops,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($request->role_id);

        $user = new User();
        $user->active = 1;
        $user->shop_id = $request->shop_id;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();
        $user->roles()->attach($role);

        return response()->json([
            'ok' => true,
            'message' => 'Usuario creado correctamente'
        ]);
    }//store()

    public function updateInfo(Request $request)
    {
        if(!$request->ajax()) return redirect('/');

        // Proteger usuario principal
        if($request->user_id == 1) {
            return response()->json(['error' => 'No se puede modificar el usuario principal'], 403);
        }

        $user = User::findOrFail($request->user_id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return response()->json(['ok' => true, 'message' => 'Usuario actualizado']);
    }//./updateInfo()

    public function updateToActive(Request $request){
        if(!$request->ajax()) return redirect('/');

        // Proteger usuario principal
        if($request->id == 1) {
            return response()->json(['error' => 'No se puede modificar el usuario principal'], 403);
        }

        $user = User::findOrFail($request->id);
        $user->active = 1;
        $user->save();
    }//updateToActive()

    public function updateToInactive(Request $request){
        if(!$request->ajax()) return redirect('/');

        // Proteger usuario principal
        if($request->id == 1) {
            return response()->json(['error' => 'No se puede modificar el usuario principal'], 403);
        }

        $user = User::findOrFail($request->id);
        $user->active = 0;
        $user->save();
    }//updateToInactive

    public function resetPassword(Request $request){
        if(!$request->ajax()) return redirect('/');

        // Proteger usuario principal
        if($request->id == 1) {
            return response()->json(['error' => 'No se puede modificar el usuario principal'], 403);
        }

        // Validar
        $request->validate([
            'id' => 'required',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password'
        ]);

        try {
            $user = User::findOrFail($request->id);

            // 🔍 LOG: Capturar hash antes de guardar (para debugging en producción)
            $oldPasswordHash = $user->password;
            $newPasswordHash = Hash::make($request->password);

            \Log::info('Password Reset Attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'old_hash' => $oldPasswordHash,
                'new_hash' => $newPasswordHash,
                'timestamp' => now()
            ]);

            // Actualizar contraseña con Hash (igual que en RegisterController)
            $user->password = $newPasswordHash;
            $saved = $user->save();

            // 🔍 LOG: Verificar si save() retornó true
            \Log::info('Password Save Result', [
                'user_id' => $user->id,
                'save_result' => $saved ? 'SUCCESS' : 'FAILED',
                'password_in_db' => $user->fresh()->password, // Leer directo de BD
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Contraseña actualizada correctamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Password Reset Error', [
                'user_id' => $request->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Error al actualizar contraseña: ' . $e->getMessage()
            ], 500);
        }
    }//resetPassword

}
