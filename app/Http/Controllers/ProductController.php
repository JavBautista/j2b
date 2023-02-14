<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        $category_id = isset($request->category_id)?$request->category_id:0;

        $array_where =[ ['active','=','1'] ];
        if ($buscar!='')     array_push($array_where,['name', 'like', '%'.$buscar.'%']);
        if ($category_id!=0) array_push($array_where,['category_id', '=', $category_id]);

        $products = Product::with('category')
                    ->where('shop_id',$shop->id)
                    ->where($array_where)
                    ->orderBy('id','desc')
                    ->paginate(10);

        return $products;
    }

    public function store(Request $request)
    {
            $user = $request->user();
            $shop = $user->shop;

            $product = new Product;
            $product->shop_id=$shop->id;
            $product->active        = 1;
            $product->category_id   = $request->category_id;
            $product->key           = $request->key;
            $product->name          = $request->name;
            $product->description   = $request->description;

            $product->cost          = $request->cost;
            $product->retail        = $request->retail;
            $product->wholesale        = $request->wholesale;
            $product->wholesale_premium = $request->wholesale_premium;
            $product->stock         = $request->stock;
            $product->reserve       = $request->reserve;
            /*
            $product->wholesale = $request->wholesale;
            $product->image = $request->image;
            $product->url_video = $request->url_video;
            $product->barcode = $request->barcode;
            */
            $product->save();

            $product_insert = Product::with('category')->findOrFail($product->id);

            return response()->json([
                'ok'=>true,
                'product' => $product_insert,
            ]);
    }

    public function update(Request $request)
    {
        $product = Product::findOrFail($request->id);

        $product->category_id = $request->category_id;
        $product->key         = $request->key;
        $product->name        = $request->name;
        $product->description = $request->description;
        $product->cost          = $request->cost;
        $product->retail        = $request->retail;
        $product->wholesale        = $request->wholesale;
        $product->wholesale_premium = $request->wholesale_premium;
        $product->stock         = $request->stock;
        $product->reserve       = $request->reserve;
        /*
        $product->barcode     = $request->barcode;

        $product->wholesale   = $request->wholesale;
        $product->image       = $request->image;
        $product->url_video   = $request->url_video;
        */
        $product->save();

        return response()->json([
            'ok'=>true,
            'product' => $product,
        ]);

    }

    public function updateStock(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->stock   = $request->stock;
        $product->reserve = $request->reserve;
        $product->save();

        return response()->json([
            'ok'=>true,
            'product' => $product,
        ]);

    }



    public function inactive(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->active = 0;
        $product->save();
        return response()->json([
            'ok'=>true
        ]);
    }
}
