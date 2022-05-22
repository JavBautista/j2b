<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{

    public function index()
    {
        $clients = Client::paginate(10);
        return $clients;

    }

    public function store(Request $request)
    {
        $client = new Client;
        $client->active=1;
        $client->name=$request->name;
        $client->company=$request->company;
        $client->email=$request->email;
        $client->movil=$request->movil;
        $client->address=$request->address;
        $client->save();
        return response()->json([
                'ok'=>true,
                'client' => $client,
        ]);
    }

    public function update(Request $request/*, Client $client*/)
    {
        $client = Client::findOrFail($request->id);
        $client->name=$request->name;
        $client->company=$request->company;
        $client->email=$request->email;
        $client->movil=$request->movil;
        $client->address=$request->address;
        $client->save();
        return response()->json([
                'ok'=>true,
                'client' => $client,
        ]);
    }

    public function destroy(Client $client)
    {
        //
    }
}
