<?php

namespace App\Http\Controllers\Chatbot;

use App\Models\Product;
use App\Models\Client;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function getProducts(Request $request){

        $products = Product::select('id', 'name','retail', 'wholesale', 'wholesale_premium', 'stock', 'reserve', 'description', 'image')
                    ->with('category')
                    ->where('shop_id',1)
                    ->where('active',1)
                    ->orderBy('id','desc')
                    ->get();
        return $products;
    }

    public function getClients(Request $request){

        $products = Client::where('shop_id',1)
                    ->where('active',1)
                    ->orderBy('id','desc')
                    ->get();
        return $products;
    }

    public function clientStore(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $client = new Client;
        $client->shop_id =$shop->id;
        $client->active  =1;
        $client->name    =$request->name;
        $client->company =$request->company;
        $client->email   =$request->email;
        $client->movil   =$request->movil;
        $client->address =$request->address;
        $client->level   =1;
        $client->origin_chatbot =1;
        $client->save();

        return response()->json([
                'ok'=>true,
                'client' => $client,
        ]);
    }
}
