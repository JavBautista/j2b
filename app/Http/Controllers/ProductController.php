<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $products = Product::where('active',1)->orderBy('id','desc')->paginate(10);
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
            $product->retail        = $request->retail;
            /*
            $product->cost = $request->cost;
            $product->wholesale = $request->wholesale;
            $product->image = $request->image;
            $product->url_video = $request->url_video;
            $product->barcode = $request->barcode;
            */
            $product->save();
            return response()->json([
                'ok'=>true,
                'product' => $product,
            ]);
    }

    public function update(Request $request)
    {
        $product = Product::findOrFail($request->id);

        $product->category_id = $request->category_id;
        $product->key         = $request->key;
        $product->name        = $request->name;
        $product->description = $request->description;
        $product->retail      = $request->retail;
        /*
        $product->barcode     = $request->barcode;
        $product->cost        = $request->cost;
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
