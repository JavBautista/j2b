<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $products = Product::orderBy('id','desc')->paginate(10);
        return $products;
    }

    public function store(Request $request)
    {
            $product = new Product;
            $product->category_id = $request->category_id;
            $product->active = 1;
            $product->key = $request->key;
            $product->barcode = $request->barcode;
            $product->name = $request->name;
            $product->description = $request->description;
            $product->cost = $request->cost;
            $product->retail = $request->retail;
            $product->wholesale = $request->wholesale;
            $product->image = $request->image;
            $product->url_video = $request->url_video;
            $product->save();

            return response()->json([
                'ok'=>true,
                'product' => $product,
            ]);


    }

    public function update(Request $request, Product $product)
    {

    }

    public function destroy(Product $product)
    {
        //
    }
}
