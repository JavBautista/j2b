<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Articulo;

class ArticuloController extends Controller
{

    public function index()
    {
        $articulos = Articulo::all();
        return $articulos;
    }


    public function store(Request $request)
    {
        $articulo =new Articulo();
        $articulo->descripcion = $request->descripcion;
        $articulo->precio = $request->precio;
        $articulo->stock = $request->stock;
        $articulo->save();
    }

    public function update(Request $request)
    {
        $articulo = Articulo::findOrFail($request->id);
        $articulo->descripcion = $request->descripcion;
        $articulo->precio = $request->precio;
        $articulo->stock = $request->stock;
        $articulo->save();
        return $articulo;
    }


    public function destroy($id)
    {
        $articulo=Articulo::destroy($request->id);
        return $articulo;
    }
}
