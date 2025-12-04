<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoriesController extends Controller
{
    /**
     * Página principal de categorías (vista Blade con componente Vue)
     */
    public function index()
    {
        $user = auth()->user();
        $shop = $user->shop;

        return view('admin.categories.index', compact('shop'));
    }

    /**
     * Obtener categorías paginadas con filtros (JSON para Vue)
     */
    public function get(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        $filtroActivo = $request->filtro_activo ?? 'TODOS';

        $query = Category::where('shop_id', $shop->id);

        // Búsqueda por nombre
        if (!empty($buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', '%' . $buscar . '%')
                  ->orWhere('id', $buscar);
            });
        }

        // Filtro por activo
        if ($filtroActivo === 'ACTIVOS') {
            $query->where('active', 1);
        } elseif ($filtroActivo === 'INACTIVOS') {
            $query->where('active', 0);
        }

        $query->orderBy('name', 'asc');

        $categories = $query->paginate(12);

        return response()->json($categories);
    }

    /**
     * Crear nueva categoría
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $category = new Category();
        $category->shop_id = $shop->id;
        $category->active = 1;
        $category->name = $request->name;
        $category->description = $request->description;
        $category->save();

        return response()->json([
            'ok' => true,
            'category' => $category,
            'message' => 'Categoría creada correctamente.'
        ]);
    }

    /**
     * Actualizar categoría existente
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $category = Category::where('shop_id', $shop->id)->findOrFail($request->id);

        $category->name = $request->name;
        $category->description = $request->description;
        $category->save();

        return response()->json([
            'ok' => true,
            'category' => $category,
            'message' => 'Categoría actualizada correctamente.'
        ]);
    }

    /**
     * Activar categoría
     */
    public function activate($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $category = Category::where('shop_id', $shop->id)->findOrFail($id);
        $category->active = 1;
        $category->save();

        return response()->json([
            'ok' => true,
            'category' => $category,
            'message' => 'Categoría activada correctamente.'
        ]);
    }

    /**
     * Desactivar categoría
     */
    public function deactivate($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $category = Category::where('shop_id', $shop->id)->findOrFail($id);
        $category->active = 0;
        $category->save();

        return response()->json([
            'ok' => true,
            'category' => $category,
            'message' => 'Categoría desactivada correctamente.'
        ]);
    }

    /**
     * Eliminar categoría
     */
    public function delete($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $category = Category::where('shop_id', $shop->id)->findOrFail($id);
        $category->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Categoría eliminada correctamente.'
        ]);
    }
}
