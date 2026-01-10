<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Administrator;
use App\Models\Collaborator;
use App\Models\Shop;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UsersController extends Controller
{
    // ========== VISTA PRINCIPAL ==========

    public function index()
    {
        $user = Auth::user();
        $shop = Shop::find($user->shop_id);
        return view('admin.users.index', compact('shop'));
    }

    // ========== DATOS GENERALES ==========

    public function getShopSlug()
    {
        $user = Auth::user();
        $shop = Shop::find($user->shop_id);
        return response()->json([
            'slug' => Str::slug($shop->name)
        ]);
    }

    public function getCounters()
    {
        $user = Auth::user();
        $shop_id = $user->shop_id;

        // Administradores
        $adminsTotal = Administrator::where('shop_id', $shop_id)->count();
        $adminsActivos = Administrator::where('shop_id', $shop_id)
            ->where('active', 1)->count();

        // Colaboradores
        $collabsTotal = Collaborator::where('shop_id', $shop_id)->count();
        $collabsActivos = Collaborator::where('shop_id', $shop_id)
            ->where('active', 1)->count();

        return response()->json([
            'admins' => [
                'total' => $adminsTotal,
                'activos' => $adminsActivos,
                'bajas' => $adminsTotal - $adminsActivos
            ],
            'collabs' => [
                'total' => $collabsTotal,
                'activos' => $collabsActivos,
                'bajas' => $collabsTotal - $collabsActivos
            ]
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $email = $request->get('email');
        $exists = User::where('email', $email)->exists();
        return response()->json(['exists' => $exists]);
    }

    // ========== ADMINISTRADORES ==========

    public function getAdministrators(Request $request)
    {
        $user = Auth::user();
        $shop_id = $user->shop_id;
        $buscar = $request->get('buscar', '');
        $estado = $request->get('estado', 'TODOS');

        $query = Administrator::with('user')
            ->where('shop_id', $shop_id);

        // Búsqueda
        if ($buscar) {
            $query->where(function($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%")
                  ->orWhere('phone', 'like', "%{$buscar}%")
                  ->orWhere('movil', 'like', "%{$buscar}%");
            });
        }

        // Filtro estado
        if ($estado === 'ACTIVO') {
            $query->where('active', 1);
        } elseif ($estado === 'BAJA') {
            $query->where('active', 0);
        }

        $administrators = $query->orderBy('name', 'asc')->paginate(12);

        return response()->json($administrators);
    }

    public function storeAdministrator(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'name' => 'required|max:255'
        ]);

        $authUser = Auth::user();
        $shop_id = $authUser->shop_id;

        // 1. Crear User
        $role_admin = Role::where('name', 'admin')->first();
        $newUser = new User();
        $newUser->active = 1;
        $newUser->shop_id = $shop_id;
        $newUser->name = $request->name;
        $newUser->email = $request->email;
        $newUser->password = Hash::make($request->password);
        $newUser->save();
        $newUser->roles()->attach($role_admin);

        // 2. Crear Administrator
        $administrator = new Administrator();
        $administrator->user_id = $newUser->id;
        $administrator->shop_id = $shop_id;
        $administrator->active = 1;
        $administrator->name = $request->name;
        $administrator->email = $request->email;
        $administrator->phone = $request->phone;
        $administrator->movil = $request->movil;
        $administrator->zip_code = $request->zip_code;
        $administrator->address = $request->address;
        $administrator->number_out = $request->number_out;
        $administrator->number_int = $request->number_int;
        $administrator->district = $request->district;
        $administrator->city = $request->city;
        $administrator->state = $request->state;
        $administrator->reference = $request->reference;
        $administrator->detail = $request->detail;
        $administrator->observations = $request->observations;
        $administrator->save();
        $administrator->load('user');

        return response()->json([
            'ok' => true,
            'administrator' => $administrator,
            'message' => 'Administrador creado correctamente'
        ]);
    }

    public function updateAdministrator(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:administrators,id'
        ]);

        $authUser = Auth::user();
        $administrator = Administrator::where('shop_id', $authUser->shop_id)
            ->findOrFail($request->id);

        $administrator->phone = $request->phone;
        $administrator->movil = $request->movil;
        $administrator->zip_code = $request->zip_code;
        $administrator->address = $request->address;
        $administrator->number_out = $request->number_out;
        $administrator->number_int = $request->number_int;
        $administrator->district = $request->district;
        $administrator->city = $request->city;
        $administrator->state = $request->state;
        $administrator->reference = $request->reference;
        $administrator->detail = $request->detail;
        $administrator->observations = $request->observations;
        $administrator->save();
        $administrator->load('user');

        return response()->json([
            'ok' => true,
            'administrator' => $administrator,
            'message' => 'Administrador actualizado correctamente'
        ]);
    }

    public function activateAdministrator($id)
    {
        $authUser = Auth::user();
        $administrator = Administrator::where('shop_id', $authUser->shop_id)
            ->findOrFail($id);

        $administrator->active = 1;
        $administrator->save();

        // Activar user asociado
        if ($administrator->user) {
            $administrator->user->active = 1;
            $administrator->user->save();
        }

        $administrator->load('user');

        return response()->json([
            'ok' => true,
            'administrator' => $administrator,
            'message' => 'Administrador activado correctamente'
        ]);
    }

    public function deactivateAdministrator($id)
    {
        $authUser = Auth::user();
        $administrator = Administrator::where('shop_id', $authUser->shop_id)
            ->findOrFail($id);

        $administrator->active = 0;
        $administrator->save();

        // Desactivar user asociado
        if ($administrator->user) {
            $administrator->user->active = 0;
            $administrator->user->save();
        }

        $administrator->load('user');

        return response()->json([
            'ok' => true,
            'administrator' => $administrator,
            'message' => 'Administrador dado de baja correctamente'
        ]);
    }

    public function toggleLimitedAdministrator($id)
    {
        $authUser = Auth::user();
        $administrator = Administrator::where('shop_id', $authUser->shop_id)
            ->findOrFail($id);

        if ($administrator->user) {
            $administrator->user->limited = !$administrator->user->limited;
            $administrator->user->save();
        }

        $administrator->load('user');
        $estado = $administrator->user->limited ? 'limitado' : 'con privilegios completos';

        return response()->json([
            'ok' => true,
            'administrator' => $administrator,
            'message' => "Administrador ahora está {$estado}"
        ]);
    }

    // ========== COLABORADORES ==========

    public function getCollaborators(Request $request)
    {
        $user = Auth::user();
        $shop_id = $user->shop_id;
        $buscar = $request->get('buscar', '');
        $estado = $request->get('estado', 'TODOS');

        $query = Collaborator::with('user')->where('shop_id', $shop_id);

        if ($buscar) {
            $query->where(function($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%")
                  ->orWhere('phone', 'like', "%{$buscar}%")
                  ->orWhere('movil', 'like', "%{$buscar}%");
            });
        }

        if ($estado === 'ACTIVO') {
            $query->where('active', 1);
        } elseif ($estado === 'BAJA') {
            $query->where('active', 0);
        }

        $collaborators = $query->orderBy('name', 'asc')->paginate(12);

        return response()->json($collaborators);
    }

    public function storeCollaborator(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'name' => 'required|max:255'
        ]);

        $authUser = Auth::user();
        $shop_id = $authUser->shop_id;

        // 1. Crear User
        $role_collaborator = Role::where('name', 'collaborator')->first();
        $newUser = new User();
        $newUser->active = 1;
        $newUser->shop_id = $shop_id;
        $newUser->name = $request->name;
        $newUser->email = $request->email;
        $newUser->password = Hash::make($request->password);
        $newUser->save();
        $newUser->roles()->attach($role_collaborator);

        // 2. Crear Collaborator
        $collaborator = new Collaborator();
        $collaborator->user_id = $newUser->id;
        $collaborator->shop_id = $shop_id;
        $collaborator->active = 1;
        $collaborator->name = $request->name;
        $collaborator->email = $request->email;
        $collaborator->phone = $request->phone;
        $collaborator->movil = $request->movil;
        $collaborator->zip_code = $request->zip_code;
        $collaborator->address = $request->address;
        $collaborator->number_out = $request->number_out;
        $collaborator->number_int = $request->number_int;
        $collaborator->district = $request->district;
        $collaborator->city = $request->city;
        $collaborator->state = $request->state;
        $collaborator->reference = $request->reference;
        $collaborator->detail = $request->detail;
        $collaborator->observations = $request->observations;
        $collaborator->save();

        return response()->json([
            'ok' => true,
            'collaborator' => $collaborator,
            'message' => 'Colaborador creado correctamente'
        ]);
    }

    public function updateCollaborator(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:collaborators,id'
        ]);

        $authUser = Auth::user();
        $collaborator = Collaborator::where('shop_id', $authUser->shop_id)
            ->findOrFail($request->id);

        $collaborator->phone = $request->phone;
        $collaborator->movil = $request->movil;
        $collaborator->zip_code = $request->zip_code;
        $collaborator->address = $request->address;
        $collaborator->number_out = $request->number_out;
        $collaborator->number_int = $request->number_int;
        $collaborator->district = $request->district;
        $collaborator->city = $request->city;
        $collaborator->state = $request->state;
        $collaborator->reference = $request->reference;
        $collaborator->detail = $request->detail;
        $collaborator->observations = $request->observations;
        $collaborator->save();

        return response()->json([
            'ok' => true,
            'collaborator' => $collaborator,
            'message' => 'Colaborador actualizado correctamente'
        ]);
    }

    public function activateCollaborator($id)
    {
        $authUser = Auth::user();
        $collaborator = Collaborator::where('shop_id', $authUser->shop_id)
            ->findOrFail($id);

        $collaborator->active = 1;
        $collaborator->save();

        if ($collaborator->user) {
            $collaborator->user->active = 1;
            $collaborator->user->save();
        }

        return response()->json([
            'ok' => true,
            'collaborator' => $collaborator,
            'message' => 'Colaborador activado correctamente'
        ]);
    }

    public function deactivateCollaborator($id)
    {
        $authUser = Auth::user();
        $collaborator = Collaborator::where('shop_id', $authUser->shop_id)
            ->findOrFail($id);

        $collaborator->active = 0;
        $collaborator->save();

        if ($collaborator->user) {
            $collaborator->user->active = 0;
            $collaborator->user->save();
        }

        return response()->json([
            'ok' => true,
            'collaborator' => $collaborator,
            'message' => 'Colaborador dado de baja correctamente'
        ]);
    }
}
