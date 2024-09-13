<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        if($buscar==''){
            $clients = Client::with(['rents' => function ($query) {
                        $query->where('active', 1);
                    }])
                    ->where('shop_id',$shop->id)
                    ->where('active',1)
                    ->orderBy('id','desc')
                    ->paginate(10);
        }else{
            $clients = Client::with(['rents' => function ($query) {
                        $query->where('active', 1);
                    }])
                    ->where('shop_id',$shop->id)
                    ->where('active',1)
                    ->where('name', 'like', '%'.$buscar.'%')
                    ->orderBy('id','desc')
                    ->paginate(10);
        }
        return $clients;

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

            // Guardar la imagen en la ubicación 'public'
            $imagePath = $image->store('clients_locations', 'public');
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
}
