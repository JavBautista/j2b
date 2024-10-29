<?php

namespace App\Http\Controllers\Chatbot;

use App\Models\Product;
use App\Models\Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function getProducts(Request $request){

        $products = Product::select('id', 'retail', 'wholesale', 'wholesale_premium', 'stock', 'reserve', 'description', 'image')
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
}
