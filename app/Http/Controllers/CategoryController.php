<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;


class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::paginate(10);
        return $categories;
    }


    public function store(Request $request)
    {
        $category =new Category();
        $category->descripcion = $request->descripcion;
        $category->name = $request->name;
        $category->save();
    }

    public function update(Request $request)
    {
        $category = category::findOrFail($request->id);
        $category->descripcion = $request->descripcion;
        $category->name = $request->name;
        $category->save();
    }


    public function destroy($id)
    {
        $category=category::destroy($id);
    }
}
