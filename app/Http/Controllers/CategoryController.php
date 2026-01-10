<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;


class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        if($buscar==''){
            $categories = Category::where('active',1)
                    ->where('shop_id',$shop->id)
                    ->orderBy('id','desc')
                    ->paginate(10);
        }else{
            $categories = Category::where('active',1)
                    ->where('shop_id',$shop->id)
                    ->where('name', 'like', '%'.$buscar.'%')
                    ->orderBy('id','desc')
                    ->paginate(10);
        }
        return $categories;
    }

    public function all(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $categories = Category::where('active',1)->where('shop_id',$shop->id)->get();
        return response()->json([
            'ok'=>true,
            'data' => $categories,
        ]);
    }


    public function store(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $category =new Category();
        $category->shop_id=$shop->id;
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
