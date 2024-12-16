<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        $category_id = isset($request->category_id)?$request->category_id:0;

        /*$array_where =[ ['active','=','1'] ];
        if ($buscar!='')     array_push($array_where,['name', 'like', '%'.$buscar.'%']);
        if ($category_id!=0) array_push($array_where,['category_id', '=', $category_id]);

        $products = Product::with('category')
                    ->where('shop_id',$shop->id)
                    ->where($array_where)
                    ->orderBy('id','desc')
                    ->paginate(10);*/

        // Inicializamos la consulta base
        $query = Product::with('category')
            ->where('shop_id', $shop->id)
            ->where('active', '1');

        // Búsqueda por palabras separadas en el campo 'name'
        if (!empty($buscar)) {
            $terms = explode(' ', $buscar); // Dividir la búsqueda en palabras
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where('name', 'like', "%$term%");
                }
            });
        }

        // Filtrar por categoría si se selecciona una
        if ($category_id != 0) {
            $query->where('category_id', $category_id);
        }

        // Ordenar y paginar resultados
        $products = $query->orderBy('id', 'desc')->paginate(10);


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
        $product->load('category');

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

    public function uploadImageProduct(Request $request){
        $product_id = $request->product_id;
        $product = Product::findOrFail($product_id);
        // Validar la existencia del archivo de imagen
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            // Guardar la imagen en la ubicación 'public'
            $imagePath = $image->store('products', 'public');
            // Si no existe una imagen principal, guardarla en el registro del product
            $product->image = $imagePath;
            $product->save();
        }

        $product->load('category');
        return response()->json([
            'ok'=>true,
            'product' => $product,
        ]);
    }

    public function deleteImageProduct(Request $request){
        $product_id = $request->id;
        $product = Product::findOrFail($product_id);
        // Obtener la ruta de la imagen actual
        $imagePath = $product->image;
        // Verificar si hay una imagen almacenada y eliminarla
        if ($imagePath) {
            // Eliminar la imagen del almacenamiento
            Storage::disk('public')->delete($imagePath);
            // Limpiar el atributo de la imagen en el modelo
            $product->image = null;
            $product->save();
        }

        $product->load('category');
        return response()->json([
            'ok' => true,
            'product' => $product,
        ]);
    }
}
