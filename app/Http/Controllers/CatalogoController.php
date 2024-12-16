<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;

class CatalogoController extends Controller
{
    public function categories(Request $request){
        $user = $request->user();
        $shop = $user->shop;
        $categories = Category::where('active',1)->where('shop_id',$shop->id)->orderBy('name')->limit(10)->inRandomOrder()->get();
        return response()->json([
            'ok'=>true,
            'data' => $categories,
        ]);
    }

    public function products(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $products = Product::where('active', 1)->where('shop_id',$shop->id)
            ->inRandomOrder() // Orden aleatorio
            ->limit(10)
            ->get();
        return response()->json([
            'ok'=>true,
            'data' => $products,
        ]);
    }


}
