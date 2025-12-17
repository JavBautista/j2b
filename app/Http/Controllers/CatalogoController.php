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

        $query = Product::where('active', 1)->where('shop_id', $shop->id);

        // Filtro por stock: 'all' = todos, 'in_stock' = con stock, 'out_of_stock' = sin stock
        $stockFilter = $request->input('stock_filter', 'all');
        if ($stockFilter === 'in_stock') {
            $query->where('stock', '>', 0);
        } elseif ($stockFilter === 'out_of_stock') {
            $query->where(function($q) {
                $q->where('stock', '<=', 0)->orWhereNull('stock');
            });
        }

        // Búsqueda por nombre
        $buscar = $request->input('buscar', '');
        if (!empty($buscar)) {
            $terms = explode(' ', $buscar);
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where('name', 'like', "%$term%");
                }
            });
        }

        $products = $query->inRandomOrder()->limit(20)->get();

        return response()->json([
            'ok'=>true,
            'data' => $products,
        ]);
    }


}
