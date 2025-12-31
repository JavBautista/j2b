<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\StockAlertService;
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
        $query = Product::with('category', 'images')
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

            $product->load('category');
            $product->load('images');

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
        $product->load('images');

        return response()->json([
            'ok'=>true,
            'product' => $product,
        ]);

    }

    public function updateStock(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $previousStock = $product->stock;

        $product->stock   = $request->stock;
        $product->reserve = $request->reserve;
        $product->save();

        // Trigger: notificar clientes si stock pasó de 0 a >0
        if ($previousStock == 0 && $product->stock > 0) {
            $stockAlertService = app(StockAlertService::class);
            $stockAlertService->processStockIncrease($product, $previousStock, $product->stock);
        }

        $product->load('category');
        $product->load('images');

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

            // Si ya existe una imagen principal, guardar en la relación ProductImage
            if ($product->image) {
                $productImage = new ProductImage();
                $productImage->product_id = $product_id;
                $productImage->image = $imagePath;
                $productImage->save();
            } else {
                // Si no existe una imagen principal, guardarla en el registro del product
                $product->image = $imagePath;
                $product->save();

            }
        }


        $product->load('category');
        $product->load('images');
        return response()->json([
            'ok'=>true,
            'product' => $product,
        ]);
    }

    public function deleteMainImage(Request $request){
        $user = $request->user();
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

            $log_desc = 'Eliminación de imágen principal.';
        }

        $product->load('category');
        $product->load('images');
        return response()->json([
            'ok' => true,
            'product' => $product,
        ]);
    }//.deleteMainImage()

    public function deleteAltImage(Request $request){
        $user = $request->user();
        $product_id = $request->input('product.id'); // Obtener el 'id' del product del request
        $imgAltId = $request->input('img_alt_id'); // Obtener el 'img_alt_id' del request

        try {
            // Buscar la imagen alternativa por su ID
            $productImage = ProductImage::findOrFail($imgAltId);

            // Verificar si la imagen alternativa pertenece al product indicado
            if ($productImage->product_id != $product_id) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La imagen alternativa no pertenece al product indicado.',
                ], 400);
            }

            // Eliminar la imagen alternativa del almacenamiento
            Storage::disk('public')->delete($productImage->image);

            // Eliminar el registro de la imagen alternativa de la base de datos
            $productImage->delete();


            // Cargar el product con las relaciones actualizadas
            $product = Product::with('category', 'images')->findOrFail($product_id);

            return response()->json([
                'ok' => true,
                'product' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al eliminar la imagen alternativa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//.deleteAltImage()
}
