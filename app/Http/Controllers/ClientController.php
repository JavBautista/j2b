<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Services\ImageService;
use Illuminate\Support\Facades\Session;

class ClientController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        if($buscar==''){
            $clients = Client::with('addresses')->with(['rents' => function ($query) {
                        $query->where('active', 1);
                    }])
                    ->where('shop_id',$shop->id)
                    ->where('active',1)
                    ->orderBy('id','desc')
                    ->paginate(10);
        }else{
            $clients = Client::with('addresses')->with(['rents' => function ($query) {
                        $query->where('active', 1);
                    }])
                    ->where('shop_id',$shop->id)
                    ->where('active',1)
                    ->where('name', 'like', '%'.$buscar.'%')
                    ->orderBy('id','desc')
                    ->paginate(10);
        }
        
        //return $clients;

        $response = $clients->toArray();
        
        $response['total_bd']  = Client::where('shop_id',$shop->id)->where('active',1)->count();

        return response()->json($response);

    }

    public function verifyUserEmail(Request $request)
    {
        $email = $request->email;
        $existeUsuario = User::where('email', $email)->exists();
        return response()->json(['existeUsuario' => $existeUsuario]);

    }

    public function storeUserApp(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $client = Client::findOrFail($request->client_id);
        $name=$client->name;
        $role_collaborator= Role::where('name', 'client')->first();
        $new_user = new User();
        $new_user->active   = 1;
        $new_user->shop_id  = $shop->id;
        $new_user->name     = $name;
        $new_user->email    = $request->email;
        $new_user->password = Hash::make($request->password);
        $new_user->save();
        $new_user->roles()->attach($role_collaborator);

        $client->user_id=$new_user->id;
        $client->save();

        // Recargar cliente con relaciones para devolver completo
        $client->load(['rents' => function ($query) {
            $query->where('active', 1);
        }, 'addresses']);

        return response()->json([
                'ok'=>true,
                'client' => $client,
                'user' => $new_user,
        ]);

    }

    public function updateUserApp(Request $request)
    {
        $authUser = $request->user();
        $shop = $authUser->shop;

        // Validar que el cliente existe y pertenece a la tienda
        $client = Client::where('id', $request->client_id)
                       ->where('shop_id', $shop->id)
                       ->firstOrFail();

        // Verificar que el cliente tiene un user_id asociado
        if (!$client->user_id) {
            return response()->json([
                'ok' => false,
                'message' => 'Este cliente no tiene usuario APP asociado'
            ], 400);
        }

        $userApp = User::findOrFail($client->user_id);

        // Actualizar email si se proporciona y es diferente
        if ($request->has('email') && $request->email !== $userApp->email) {
            // Verificar que el nuevo email no exista
            $emailExists = User::where('email', $request->email)
                              ->where('id', '!=', $userApp->id)
                              ->exists();
            if ($emailExists) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El email ya está en uso por otro usuario'
                ], 400);
            }
            $userApp->email = $request->email;
        }

        // Actualizar contraseña si se proporciona
        if ($request->has('password') && !empty($request->password)) {
            $userApp->password = Hash::make($request->password);
        }

        // Actualizar nombre con el nombre actual del cliente
        $userApp->name = $client->name;
        $userApp->save();

        // Recargar cliente con relaciones para devolver completo
        $client->load(['rents' => function ($query) {
            $query->where('active', 1);
        }, 'addresses']);

        return response()->json([
            'ok' => true,
            'client' => $client,
            'user' => $userApp,
            'message' => 'Usuario APP actualizado correctamente'
        ]);
    }

    public function getUserApp(Request $request)
    {
        $authUser = $request->user();
        $shop = $authUser->shop;

        $client_id = $request->client_id;

        // Validar que el cliente existe y pertenece a la tienda
        $client = Client::where('id', $client_id)
                       ->where('shop_id', $shop->id)
                       ->firstOrFail();

        // Verificar que el cliente tiene un user_id asociado
        if (!$client->user_id) {
            return response()->json([
                'ok' => false,
                'message' => 'Este cliente no tiene usuario APP asociado'
            ], 400);
        }

        $userApp = User::find($client->user_id);

        if (!$userApp) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró el usuario APP'
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'user' => [
                'id' => $userApp->id,
                'email' => $userApp->email,
                'name' => $userApp->name,
                'active' => $userApp->active
            ]
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $client = new Client;
        $client->shop_id=$shop->id;
        $client->active=1;
        $client->name=$request->name;
        $client->company=$request->company;
        $client->email=$request->email;
        $client->movil=$request->movil;
        $client->address=$request->address;
        $client->level=$request->level;
        $client->save();

        $client_new = Client::with(['rents' => function ($query) {
                    $query->where('active', 1);
                }])->findOrFail($client->id);
        return response()->json([
                'ok'=>true,
                'client' => $client_new,
        ]);
    }

    public function update(Request $request)
    {
        $client = Client::findOrFail($request->id);
        $client->name=$request->name;
        $client->company=$request->company;
        $client->email=$request->email;
        $client->movil=$request->movil;
        $client->address=$request->address;
        $client->level=$request->level;
        $client->save();
        return response()->json([
                'ok'=>true,
                'client' => $client,
        ]);
    }

    public function inactive(Request $request)
    {
        $client = Client::findOrFail($request->id);
        $client->active = 0;
        $client->save();
        return response()->json([
            'ok'=>true
        ]);
    }

    public function uploadLocationImageClient(Request $request){
        //return 'OK';
        $user = $request->user();

        $clientId = $request->client_id;
        $client = Client::findOrFail($clientId);

        // Validar la existencia del archivo de imagen
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            // Procesar y optimizar la imagen
            $imageService = new ImageService();
            $imagePath = $imageService->processAndStore($image, 'clients_locations');
            // guadamos  el registro del client
            $client->location_image = $imagePath;
            $client->save();

        }

        //$client->load('...');
        return response()->json([
            'ok'=>true,
            'client' => $client
        ]);
    }//.uploadLocationImageClient()

    public function deleteLocationImage(Request $request){
        $user = $request->user();
        $client_id = $request->id;
        $client = Client::findOrFail($client_id);
        // Obtener la ruta de la imagen actual
        $imagePath = $client->location_image;
        // Verificar si hay una imagen almacenada y eliminarla
        if ($imagePath) {
            // Eliminar la imagen del almacenamiento
            Storage::disk('public')->delete($imagePath);
            // Limpiar el atributo de la imagen en el modelo
            $client->location_image = null;
            $client->save();
        }

        //$client->load('...');
        return response()->json([
            'ok' => true,
            'client' => $client
        ]);
    }//.deleteLocationImage()

    // MÉTODOS PARA GEOLOCALIZACIÓN GPS

    public function updateLocation(Request $request)
    {
        try {
            $user = $request->user();
            $shop = $user->shop;
            
            $request->validate([
                'client_id' => 'required|integer|exists:clients,id',
                'location_latitude' => 'required|string',
                'location_longitude' => 'required|string'
            ]);

            $client = Client::where('id', $request->client_id)
                           ->where('shop_id', $shop->id)
                           ->firstOrFail();

            $client->location_latitude = $request->location_latitude;
            $client->location_longitude = $request->location_longitude;
            $client->save();

            return response()->json([
                'ok' => true,
                'message' => 'Ubicación actualizada correctamente',
                'client' => $client
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al actualizar ubicación',
                'error' => $e->getMessage()
            ], 500);
        }
    } // updateLocation()

    public function removeLocation(Request $request)
    {
        try {
            $user = $request->user();
            $shop = $user->shop;
            
            $request->validate([
                'client_id' => 'required|integer|exists:clients,id'
            ]);

            $client = Client::where('id', $request->client_id)
                           ->where('shop_id', $shop->id)
                           ->firstOrFail();

            $client->location_latitude = null;
            $client->location_longitude = null;
            $client->save();

            return response()->json([
                'ok' => true,
                'message' => 'Ubicación eliminada correctamente',
                'client' => $client
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al eliminar ubicación',
                'error' => $e->getMessage()
            ], 500);
        }
    } // removeLocation()
}
