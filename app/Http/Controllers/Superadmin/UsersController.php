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

        $users = User::with('shop')->with('roles')
                ->when($request->buscar!='',function ($query) use ($request){
                    return $query->where($request->criterio,'like','%'.$request->buscar.'%');
                })
                ->when($request->estatus != '', function ($query) use ($request) {
                        // Filtrar por estatus
                        if ($request->estatus === 'active') {
                            return $query->where('active', 1);
                        } elseif ($request->estatus === 'inactive') {
                            return $query->where('active', 0);
                        }
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
        ];
    }//.get()

    public function store(Request $request)
    {
        if(!$request->ajax()) return redirect('/');

        $role_user= Role::where('name', 'admin')->first();

        $user = new User();
        $user->active = 1;
        $user->shop_id = $request->shop_id;
        $user->name     = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();
        $user->roles()->attach($role_user);
    }//store()

    public function updateInfo(Request $request)
    {
        if(!$request->ajax()) return redirect('/');
        $user = User::findOrFail($request->user_id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();
    }//./updateInfo()

    public function updateToActive(Request $request){
        if(!$request->ajax()) return redirect('/');
        $user = User::findOrFail($request->id);
        $user->active = 1;
        $user->save();
    }//updateToActive()

    public function updateToInactive(Request $request){
        if(!$request->ajax()) return redirect('/');
        $user = User::findOrFail($request->id);
        $user->active = 0;
        $user->save();
    }//updateToInactive

    public function resetPassword(Request $request){
        if(!$request->ajax()) return redirect('/');

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
