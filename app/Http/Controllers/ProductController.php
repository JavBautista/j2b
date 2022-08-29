<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $buscar = $request->buscar;
        if($buscar==''){
            $products = Product::with('category')
                    ->where('active',1)
                    ->orderBy('id','desc')
                    ->paginate(10);
        }else{
            $products = Product::with('category')
                    ->where('active',1)
                    ->where('name', 'like', '%'.$buscar.'%')
                    ->orderBy('id','desc')
                    ->paginate(10);
        }

        return $products;
    }

    public function store(Request $request)
    {
            $product = new Product;
            $product->active        = 1;
            $product->category_id   = $request->category_id;
            $product->key           = $request->key;
            $product->name          = $request->name;
            $product->description   = $request->description;

            $product->cost          = $request->cost;
            $product->retail        = $request->retail;
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
