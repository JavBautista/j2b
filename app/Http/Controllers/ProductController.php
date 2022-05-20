<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $products = Product::paginate(10);
        return $products;
    }

    public function store(Request $request)
    {
        //
    }

    public function update(Request $request, Product $product)
    {

    }

    public function destroy(Product $product)
    {
        //
    }
}
