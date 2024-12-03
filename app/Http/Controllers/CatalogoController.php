<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;

class CatalogoController extends Controller
{
    public function categories(Request $request){
        $categories = Category::where('active',1)->orderBy('name')->limit(8)->inRandomOrder()->get();
        return response()->json([
            'ok'=>true,
            'data' => $categories,
        ]);
    }

    public function products(Request $request){
        $products = Product::where('active', 1)
            ->inRandomOrder() // Orden aleatorio
            ->limit(10)
            ->get();
        return response()->json([
            'ok'=>true,
            'data' => $products,
        ]);
    }


}
