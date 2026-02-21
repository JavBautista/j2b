<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;

class ServicesController extends Controller
{
    /**
     * Página principal de servicios (vista Blade con componente Vue)
     */
    public function index()
    {
        $user = auth()->user();
        $shop = $user->shop;

        return view('admin.services.index', compact('shop'));
    }

    /**
     * Obtener servicios paginados con filtros (JSON para Vue)
     */
    public function get(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        $filtroActivo = $request->filtro_activo ?? 'TODOS';

        $query = Service::where('shop_id', $shop->id);

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

        $services = $query->paginate(12);

        return response()->json($services);
    }

    /**
     * Crear nuevo servicio
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $service = new Service();
        $service->shop_id = $shop->id;
        $service->active = 1;
        $service->name = $request->name;
        $service->description = $request->description;
        $service->price = $request->price;
        $service->sat_product_code = $request->sat_product_code;
        $service->sat_unit_code = $request->sat_unit_code ?? 'E48';
        $service->save();

        return response()->json([
            'ok' => true,
            'service' => $service,
            'message' => 'Servicio creado correctamente.'
        ]);
    }

    /**
     * Actualizar servicio existente
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:services,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $service = Service::where('shop_id', $shop->id)->findOrFail($request->id);

        $service->name = $request->name;
        $service->description = $request->description;
        $service->price = $request->price;
        $service->sat_product_code = $request->sat_product_code;
        $service->sat_unit_code = $request->sat_unit_code ?? $service->sat_unit_code;
        $service->save();

        return response()->json([
            'ok' => true,
            'service' => $service,
            'message' => 'Servicio actualizado correctamente.'
        ]);
    }

    /**
     * Activar servicio
     */
    public function activate($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $service = Service::where('shop_id', $shop->id)->findOrFail($id);
        $service->active = 1;
        $service->save();

        return response()->json([
            'ok' => true,
            'service' => $service,
            'message' => 'Servicio activado correctamente.'
        ]);
    }

    /**
     * Desactivar servicio
     */
    public function deactivate($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $service = Service::where('shop_id', $shop->id)->findOrFail($id);
        $service->active = 0;
        $service->save();

        return response()->json([
            'ok' => true,
            'service' => $service,
            'message' => 'Servicio desactivado correctamente.'
        ]);
    }

    /**
     * Eliminar servicio
     */
    public function delete($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $service = Service::where('shop_id', $shop->id)->findOrFail($id);
        $service->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Servicio eliminado correctamente.'
        ]);
    }
}
