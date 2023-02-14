<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        if($buscar==''){
            $clients = Client::with('rents')
                    ->where('shop_id',$shop->id)
                    ->where('active',1)
                    ->orderBy('id','desc')
                    ->paginate(10);
        }else{
            $clients = Client::with('rents')
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

        $client_new = Client::with('rents')->findOrFail($client->id);
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
}
