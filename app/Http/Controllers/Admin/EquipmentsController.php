<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RentDetail;
use App\Models\RentDetailImage;
use Illuminate\Support\Facades\Storage;

class EquipmentsController extends Controller
{
    /**
     * Página principal de equipos (vista Blade con componente Vue)
     */
    public function index()
    {
        $user = auth()->user();
        $shop = $user->shop;

        return view('admin.equipments.index', compact('shop'));
    }

    /**
     * Obtener equipos paginados con filtros (JSON para Vue)
     * Equipos son rent_details donde rent_id = 0 (inventario)
     */
    public function get(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        $filtroActivo = $request->filtro_activo ?? 'TODOS';

        $query = RentDetail::with('images')
            ->where('shop_id', $shop->id)
            ->where('rent_id', 0); // Solo equipos de inventario

        // Búsqueda por marca, modelo o número de serie
        if (!empty($buscar)) {
            $terms = explode(' ', $buscar);
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQuery) use ($term) {
                        $subQuery->where('trademark', 'like', "%$term%")
                                 ->orWhere('model', 'like', "%$term%")
                                 ->orWhere('serial_number', 'like', "%$term%");
                    });
                }
            });
        }

        // Filtro por activo
        if ($filtroActivo === 'ACTIVOS') {
            $query->where('active', 1);
        } elseif ($filtroActivo === 'INACTIVOS') {
            $query->where('active', 0);
        }

        $query->orderBy('id', 'desc');

        $equipments = $query->paginate(12);

        return response()->json($equipments);
    }

    /**
     * Crear nuevo equipo
     */
    public function store(Request $request)
    {
        $request->validate([
            'trademark' => 'required|string|max:255',
            'model' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $shop = $user->shop;
        $now = now();

        $equipment = new RentDetail();
        $equipment->active = 1;
        $equipment->rent_id = 0; // Inventario
        $equipment->shop_id = $shop->id;
        $equipment->trademark = $request->trademark;
        $equipment->model = $request->model;
        $equipment->serial_number = $request->serial_number;
        $equipment->rent_price = $request->rent_price ?? 0;
        $equipment->monochrome = $request->monochrome ?? 0;
        $equipment->pages_included_mono = $request->pages_included_mono;
        $equipment->extra_page_cost_mono = $request->extra_page_cost_mono;
        $equipment->counter_mono = $request->counter_mono;
        $equipment->update_counter_mono = $now;
        $equipment->color = $request->color ?? 0;
        $equipment->pages_included_color = $request->pages_included_color;
        $equipment->extra_page_cost_color = $request->extra_page_cost_color;
        $equipment->counter_color = $request->counter_color;
        $equipment->update_counter_color = $now;
        $equipment->description = $request->description;
        $equipment->cost = $request->cost ?? 0;
        $equipment->retail = $request->retail ?? 0;
        $equipment->wholesale = $request->wholesale ?? 0;
        $equipment->type_sale = $request->type_sale;
        $equipment->save();

        $equipment->load('images');

        return response()->json([
            'ok' => true,
            'equipment' => $equipment,
            'message' => 'Equipo creado correctamente.'
        ]);
    }

    /**
     * Actualizar equipo existente
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:rent_details,id',
            'trademark' => 'required|string|max:255',
            'model' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $shop = $user->shop;
        $now = now();

        $equipment = RentDetail::where('shop_id', $shop->id)
            ->where('rent_id', 0)
            ->findOrFail($request->id);

        $equipment->trademark = $request->trademark;
        $equipment->model = $request->model;
        $equipment->serial_number = $request->serial_number;
        $equipment->rent_price = $request->rent_price ?? 0;
        $equipment->monochrome = $request->monochrome ?? 0;
        $equipment->pages_included_mono = $request->pages_included_mono;
        $equipment->extra_page_cost_mono = $request->extra_page_cost_mono;
        $equipment->counter_mono = $request->counter_mono;
        $equipment->update_counter_mono = $now;
        $equipment->color = $request->color ?? 0;
        $equipment->pages_included_color = $request->pages_included_color;
        $equipment->extra_page_cost_color = $request->extra_page_cost_color;
        $equipment->counter_color = $request->counter_color;
        $equipment->update_counter_color = $now;
        $equipment->description = $request->description;
        $equipment->cost = $request->cost ?? 0;
        $equipment->retail = $request->retail ?? 0;
        $equipment->wholesale = $request->wholesale ?? 0;
        $equipment->type_sale = $request->type_sale;
        $equipment->save();

        $equipment->load('images');

        return response()->json([
            'ok' => true,
            'equipment' => $equipment,
            'message' => 'Equipo actualizado correctamente.'
        ]);
    }

    /**
     * Activar equipo
     */
    public function activate($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $equipment = RentDetail::where('shop_id', $shop->id)
            ->where('rent_id', 0)
            ->findOrFail($id);
        $equipment->active = 1;
        $equipment->save();

        $equipment->load('images');

        return response()->json([
            'ok' => true,
            'equipment' => $equipment,
            'message' => 'Equipo activado correctamente.'
        ]);
    }

    /**
     * Desactivar equipo
     */
    public function deactivate($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $equipment = RentDetail::where('shop_id', $shop->id)
            ->where('rent_id', 0)
            ->findOrFail($id);
        $equipment->active = 0;
        $equipment->save();

        $equipment->load('images');

        return response()->json([
            'ok' => true,
            'equipment' => $equipment,
            'message' => 'Equipo desactivado correctamente.'
        ]);
    }

    /**
     * Eliminar equipo
     */
    public function delete($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $equipment = RentDetail::where('shop_id', $shop->id)
            ->where('rent_id', 0)
            ->findOrFail($id);

        // Eliminar imágenes asociadas
        foreach ($equipment->images as $image) {
            Storage::disk('public')->delete($image->image);
            $image->delete();
        }

        // Eliminar consumibles asociados
        $equipment->consumables()->delete();

        $equipment->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Equipo eliminado correctamente.'
        ]);
    }

    /**
     * Subir imagen de equipo
     */
    public function uploadImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $equipment = RentDetail::where('shop_id', $shop->id)
            ->where('rent_id', 0)
            ->findOrFail($id);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('equipments', 'public');

            $rentDetailImage = new RentDetailImage();
            $rentDetailImage->rent_detail_id = $equipment->id;
            $rentDetailImage->image = $imagePath;
            $rentDetailImage->save();
        }

        $equipment->load('images');

        return response()->json([
            'ok' => true,
            'equipment' => $equipment,
            'message' => 'Imagen subida correctamente.'
        ]);
    }

    /**
     * Eliminar imagen de equipo
     */
    public function deleteImage($imageId)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $rentDetailImage = RentDetailImage::findOrFail($imageId);

        // Verificar que el equipo pertenece al shop del usuario
        $equipment = RentDetail::where('shop_id', $shop->id)
            ->where('rent_id', 0)
            ->findOrFail($rentDetailImage->rent_detail_id);

        // Eliminar archivo y registro
        Storage::disk('public')->delete($rentDetailImage->image);
        $rentDetailImage->delete();

        $equipment->load('images');

        return response()->json([
            'ok' => true,
            'equipment' => $equipment,
            'message' => 'Imagen eliminada correctamente.'
        ]);
    }
}
