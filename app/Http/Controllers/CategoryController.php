<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;


class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('active',1)->orderBy('id','desc')->paginate(10);
        return $categories;
    }

    public function all()
    {
        $categories = Category::where('active',1)->get();
        return response()->json([
            'ok'=>true,
            'data' => $categories,
        ]);
    }


    public function store(Request $request)
    {
        $category =new Category();
        $category->active = 1;
        $category->name = $request->name;
        $category->description = $request->description;
        $category->save();
        return response()->json([
            'ok'=>true,
            'category' => $category,
        ]);
    }

    public function update(Request $request)
    {
        $category = Category::findOrFail($request->id);
        $category->description = $request->description;
        $category->name = $request->name;
        $category->save();
        return response()->json([
            'ok'=>true,
            'category' => $category,
        ]);
    }


    public function inactive(Request $request)
    {
        $category = Category::findOrFail($request->id);
        $category->active = 0;
        $category->save();
        return response()->json([
            'ok'=>true
        ]);
    }
}
