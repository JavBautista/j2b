<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageService;
use App\Services\StockAlertService;

class ProductsController extends Controller
{
    /**
     * Página principal de productos (vista Blade con componente Vue)
     */
    public function index()
    {
        $user = auth()->user();
        $shop = $user->shop;

        return view('admin.products.index', compact('shop'));
    }

    /**
     * Obtener productos paginados con filtros (JSON para Vue)
     */
    public function get(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        $filtroCategoria = $request->filtro_categoria ?? 'TODOS';
        $filtroActivo = $request->filtro_activo ?? 'TODOS';

        $query = Product::with(['category', 'images'])
                    ->where('shop_id', $shop->id);

        // Búsqueda por nombre, código o código de barras
        if (!empty($buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', '%' . $buscar . '%')
                  ->orWhere('key', 'like', '%' . $buscar . '%')
                  ->orWhere('barcode', 'like', '%' . $buscar . '%')
                  ->orWhere('id', $buscar);
            });
        }

        // Filtro por categoría
        if (!empty($filtroCategoria) && $filtroCategoria !== 'TODOS') {
            $query->where('category_id', $filtroCategoria);
        }

        // Filtro por activo
        if ($filtroActivo === 'ACTIVOS') {
            $query->where('active', 1);
        } elseif ($filtroActivo === 'INACTIVOS') {
            $query->where('active', 0);
        }

        $query->orderBy('name', 'asc');

        $products = $query->paginate(12);

        return response()->json($products);
    }

    /**
     * Obtener categorías de la tienda
     */
    public function getCategories()
    {
        $user = auth()->user();
        $shop = $user->shop;

        $categories = Category::where('shop_id', $shop->id)
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'ok' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Crear nuevo producto
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'nullable|string|max:100',
            'cost' => 'required|numeric|min:0',
            'retail' => 'required|numeric|min:0',
        ]);

        try {
            $user = auth()->user();
            $shop = $user->shop;

            // Si no viene category_id, usar la primera categoría de la tienda
            $categoryId = $request->category_id;
            if (empty($categoryId)) {
                $defaultCategory = Category::where('shop_id', $shop->id)->first();
                if (!$defaultCategory) {
                    // Crear categoría "General" si no existe ninguna
                    $defaultCategory = Category::create([
                        'shop_id' => $shop->id,
                        'name' => 'General',
                        'active' => 1
                    ]);
                }
                $categoryId = $defaultCategory->id;
            }

            $product = new Product();
            $product->shop_id = $shop->id;
            $product->category_id = $categoryId;
            $product->active = 1;
            $product->key = $request->key;
            $product->barcode = $request->barcode;
            $product->name = $request->name;
            $product->description = $request->description;
            $product->cost = $request->cost;
            $product->retail = $request->retail;
            $product->wholesale = $request->wholesale ?? 0;
            $product->wholesale_premium = $request->wholesale_premium ?? 0;
            $product->stock = $request->stock ?? 0;
            $product->reserve = $request->reserve ?? 0;
            $product->save();

            $product->load('category', 'images');

            return response()->json([
                'ok' => true,
                'product' => $product,
                'message' => 'Producto creado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al crear el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar producto existente
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'retail' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $product = Product::where('shop_id', $shop->id)->findOrFail($request->id);

        // Guardar stock anterior para verificar si pasó de 0 a >0
        $previousStock = $product->stock;

        $product->category_id = $request->category_id;
        $product->key = $request->key;
        $product->barcode = $request->barcode;
        $product->name = $request->name;
        $product->description = $request->description;
        $product->cost = $request->cost;
        $product->retail = $request->retail;
        $product->wholesale = $request->wholesale ?? 0;
        $product->wholesale_premium = $request->wholesale_premium ?? 0;
        $product->stock = $request->stock ?? $product->stock;
        $product->reserve = $request->reserve ?? $product->reserve;
        $product->save();

        // Notificar clientes en espera si stock pasó de 0 a >0
        if ($previousStock == 0 && $product->stock > 0) {
            $stockAlertService = app(StockAlertService::class);
            $stockAlertService->processStockIncrease($product, $previousStock, $product->stock);
        }

        $product->load('category', 'images');

        return response()->json([
            'ok' => true,
            'product' => $product,
            'message' => 'Producto actualizado correctamente.'
        ]);
    }

    /**
     * Activar producto
     */
    public function activate($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $product = Product::where('shop_id', $shop->id)->findOrFail($id);
        $product->active = 1;
        $product->save();

        $product->load('category');

        return response()->json([
            'ok' => true,
            'product' => $product,
            'message' => 'Producto activado correctamente.'
        ]);
    }

    /**
     * Desactivar producto
     */
    public function deactivate($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $product = Product::where('shop_id', $shop->id)->findOrFail($id);
        $product->active = 0;
        $product->save();

        $product->load('category');

        return response()->json([
            'ok' => true,
            'product' => $product,
            'message' => 'Producto desactivado correctamente.'
        ]);
    }

    /**
     * Eliminar producto
     */
    public function delete($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $product = Product::where('shop_id', $shop->id)->findOrFail($id);

        // Eliminar imagen si existe
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Producto eliminado correctamente.'
        ]);
    }

    /**
     * Actualizar stock
     */
    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'stock' => 'required|integer|min:0',
            'reserve' => 'nullable|integer|min:0',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $product = Product::where('shop_id', $shop->id)->findOrFail($id);

        // Guardar stock anterior para verificar si pasó de 0 a >0
        $previousStock = $product->stock;

        $product->stock = $request->stock;
        $product->reserve = $request->reserve ?? $product->reserve;
        $product->save();

        // Notificar clientes en espera si stock pasó de 0 a >0
        if ($previousStock == 0 && $product->stock > 0) {
            $stockAlertService = app(StockAlertService::class);
            $stockAlertService->processStockIncrease($product, $previousStock, $product->stock);
        }

        $product->load('category', 'images');

        return response()->json([
            'ok' => true,
            'product' => $product,
            'message' => 'Stock actualizado correctamente.'
        ]);
    }

    /**
     * Subir imagen de producto
     */
    public function uploadImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $product = Product::where('shop_id', $shop->id)->findOrFail($id);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            // Procesar y optimizar la imagen
            $imageService = new ImageService();
            $imagePath = $imageService->processAndStore($image, 'products');

            // Si ya existe imagen principal, guardar como alternativa
            if ($product->image) {
                $productImage = new ProductImage();
                $productImage->product_id = $product->id;
                $productImage->image = $imagePath;
                $productImage->save();
            } else {
                // Si no hay imagen principal, guardarla como principal
                $product->image = $imagePath;
                $product->save();
            }
        }

        $product->load('category', 'images');

        return response()->json([
            'ok' => true,
            'product' => $product,
            'message' => 'Imagen subida correctamente.'
        ]);
    }

    /**
     * Eliminar imagen principal
     */
    public function deleteMainImage($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $product = Product::where('shop_id', $shop->id)->findOrFail($id);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
            $product->image = null;
            $product->save();
        }

        $product->load('category', 'images');

        return response()->json([
            'ok' => true,
            'product' => $product,
            'message' => 'Imagen eliminada correctamente.'
        ]);
    }

    /**
     * Eliminar imagen alternativa
     */
    public function deleteAltImage($imageId)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $productImage = ProductImage::findOrFail($imageId);

        // Verificar que el producto pertenece al shop del usuario
        $product = Product::where('shop_id', $shop->id)->findOrFail($productImage->product_id);

        // Eliminar archivo y registro
        Storage::disk('public')->delete($productImage->image);
        $productImage->delete();

        $product->load('category', 'images');

        return response()->json([
            'ok' => true,
            'product' => $product,
            'message' => 'Imagen alternativa eliminada correctamente.'
        ]);
    }
}
