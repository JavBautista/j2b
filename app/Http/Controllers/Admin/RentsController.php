<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rent;
use App\Models\RentDetail;
use App\Models\Consumables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RentsController extends Controller
{
    /**
     * Obtener rentas de un cliente con conteo de equipos
     */
    public function getClientRents(\App\Models\Client $client)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $rents = Rent::where('client_id', $client->id)
            ->withCount('rentDetail')
            ->with(['rentDetail' => function ($query) {
                $query->select('id', 'rent_id', 'trademark', 'model', 'rent_price');
            }])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'ok' => true,
            'client' => [
                'id' => $client->id,
                'name' => $client->name
            ],
            'rents' => $rents
        ]);
    }

    /**
     * Obtener detalle de una renta con todos sus equipos
     */
    public function getRentDetails($rentId)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $rent = Rent::with(['rentDetail.consumables', 'rentDetail.images', 'client'])
            ->findOrFail($rentId);

        // Verificar que la renta pertenece a un cliente de esta tienda
        if ($rent->client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Renta no encontrada'], 404);
        }

        return response()->json([
            'ok' => true,
            'rent' => $rent
        ]);
    }

    /**
     * Crear nueva renta
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'cutoff' => 'required|integer|min:1|max:31',
            'location_descripcion' => 'nullable|string|max:255',
            'location_address' => 'nullable|string|max:500',
            'location_phone' => 'nullable|string|max:50',
            'location_email' => 'nullable|email|max:255',
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        $client = \App\Models\Client::where('shop_id', $shop->id)
            ->findOrFail($request->client_id);

        $rent = new Rent();
        $rent->client_id = $request->client_id;
        $rent->active = 1;
        $rent->cutoff = $request->cutoff;
        $rent->location_descripcion = $request->location_descripcion;
        $rent->location_address = $request->location_address;
        $rent->location_phone = $request->location_phone;
        $rent->location_email = $request->location_email;
        $rent->save();

        return response()->json([
            'ok' => true,
            'rent' => $rent,
            'message' => 'Renta creada correctamente.'
        ]);
    }

    /**
     * Actualizar renta
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:rents,id',
            'cutoff' => 'required|integer|min:1|max:31',
            'location_descripcion' => 'nullable|string|max:255',
            'location_address' => 'nullable|string|max:500',
            'location_phone' => 'nullable|string|max:50',
            'location_email' => 'nullable|email|max:255',
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        $rent = Rent::with('client')->findOrFail($request->id);

        // Verificar que la renta pertenece a un cliente de esta tienda
        if ($rent->client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Renta no encontrada'], 404);
        }

        $rent->cutoff = $request->cutoff;
        $rent->location_descripcion = $request->location_descripcion;
        $rent->location_address = $request->location_address;
        $rent->location_phone = $request->location_phone;
        $rent->location_email = $request->location_email;
        $rent->save();

        return response()->json([
            'ok' => true,
            'rent' => $rent,
            'message' => 'Renta actualizada correctamente.'
        ]);
    }

    /**
     * Dar de baja una renta (soft delete)
     */
    public function inactive($id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $rent = Rent::with('client')->findOrFail($id);

        // Verificar que la renta pertenece a un cliente de esta tienda
        if ($rent->client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Renta no encontrada'], 404);
        }

        $rent->active = 0;
        $rent->save();

        return response()->json([
            'ok' => true,
            'message' => 'Renta dada de baja correctamente.'
        ]);
    }

    /**
     * Reactivar una renta
     */
    public function active($id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $rent = Rent::with('client')->findOrFail($id);

        if ($rent->client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Renta no encontrada'], 404);
        }

        $rent->active = 1;
        $rent->save();

        return response()->json([
            'ok' => true,
            'message' => 'Renta reactivada correctamente.'
        ]);
    }

    // =========================================================
    // EQUIPOS (RentDetail)
    // =========================================================

    /**
     * Crear nuevo equipo para una renta
     */
    public function storeDetail(Request $request)
    {
        $request->validate([
            'rent_id' => 'required|exists:rents,id',
            'trademark' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'rent_price' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que la renta pertenece a un cliente de esta tienda
        $rent = Rent::with('client')->findOrFail($request->rent_id);
        if ($rent->client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Renta no encontrada'], 404);
        }

        $now = now();

        $detail = new RentDetail();
        $detail->shop_id = $shop->id;
        $detail->rent_id = $request->rent_id;
        $detail->active = 1;
        $detail->trademark = $request->trademark;
        $detail->model = $request->model;
        $detail->serial_number = $request->serial_number;
        $detail->rent_price = $request->rent_price;
        $detail->description = $request->description;
        $detail->url_web_monitor = $request->url_web_monitor;

        // Blanco y Negro
        $detail->monochrome = $request->monochrome ? 1 : 0;
        $detail->pages_included_mono = $request->pages_included_mono ?? 0;
        $detail->extra_page_cost_mono = $request->extra_page_cost_mono ?? 0;
        $detail->counter_mono = $request->counter_mono ?? 0;
        $detail->update_counter_mono = $now;

        // Color
        $detail->color = $request->color ? 1 : 0;
        $detail->pages_included_color = $request->pages_included_color ?? 0;
        $detail->extra_page_cost_color = $request->extra_page_cost_color ?? 0;
        $detail->counter_color = $request->counter_color ?? 0;
        $detail->update_counter_color = $now;

        $detail->save();

        return response()->json([
            'ok' => true,
            'rent_detail' => $detail,
            'message' => 'Equipo creado correctamente.'
        ]);
    }

    /**
     * Actualizar equipo
     */
    public function updateDetail(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:rent_details,id',
            'trademark' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'rent_price' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        $detail = RentDetail::where('shop_id', $shop->id)->findOrFail($request->id);

        $now = now();

        $detail->trademark = $request->trademark;
        $detail->model = $request->model;
        $detail->serial_number = $request->serial_number;
        $detail->rent_price = $request->rent_price;
        $detail->description = $request->description;
        $detail->url_web_monitor = $request->url_web_monitor;

        // Blanco y Negro
        $detail->monochrome = $request->monochrome ? 1 : 0;
        $detail->pages_included_mono = $request->pages_included_mono ?? 0;
        $detail->extra_page_cost_mono = $request->extra_page_cost_mono ?? 0;
        $detail->counter_mono = $request->counter_mono ?? 0;
        $detail->update_counter_mono = $now;

        // Color
        $detail->color = $request->color ? 1 : 0;
        $detail->pages_included_color = $request->pages_included_color ?? 0;
        $detail->extra_page_cost_color = $request->extra_page_cost_color ?? 0;
        $detail->counter_color = $request->counter_color ?? 0;
        $detail->update_counter_color = $now;

        $detail->save();

        return response()->json([
            'ok' => true,
            'rent_detail' => $detail,
            'message' => 'Equipo actualizado correctamente.'
        ]);
    }

    /**
     * Liberar equipo (vuelve a inventario)
     */
    public function liberarDetail($id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $detail = RentDetail::where('shop_id', $shop->id)->findOrFail($id);
        $detail->rent_id = 0;
        $detail->save();

        return response()->json([
            'ok' => true,
            'message' => 'Equipo liberado correctamente.'
        ]);
    }

    /**
     * Asignar equipo existente (del inventario) a una renta
     */
    public function assignEquipment(Request $request)
    {
        $request->validate([
            'equipment_id' => 'required|exists:rent_details,id',
            'rent_id' => 'required|exists:rents,id',
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el equipo pertenece a esta tienda y está disponible
        $detail = RentDetail::where('shop_id', $shop->id)
            ->where('rent_id', 0) // Solo equipos disponibles
            ->findOrFail($request->equipment_id);

        // Verificar que la renta pertenece a un cliente de esta tienda
        $rent = Rent::with('client')->findOrFail($request->rent_id);
        if ($rent->client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Renta no encontrada'], 404);
        }

        $detail->rent_id = $request->rent_id;
        $detail->save();

        return response()->json([
            'ok' => true,
            'rent_detail' => $detail,
            'message' => 'Equipo asignado correctamente.'
        ]);
    }

    /**
     * Obtener equipos disponibles (inventario)
     */
    public function getAvailableEquipments()
    {
        $user = Auth::user();
        $shop = $user->shop;

        $equipments = RentDetail::where('shop_id', $shop->id)
            ->where('rent_id', 0)
            ->where('active', 1)
            ->orderBy('trademark')
            ->orderBy('model')
            ->get();

        return response()->json([
            'ok' => true,
            'equipments' => $equipments
        ]);
    }

    /**
     * Actualizar URL monitor de un equipo
     */
    public function updateUrlMonitor(Request $request, $id)
    {
        $request->validate([
            'url_web_monitor' => 'nullable|url|max:500',
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        $detail = RentDetail::where('shop_id', $shop->id)->findOrFail($id);
        $detail->url_web_monitor = $request->url_web_monitor;
        $detail->save();

        return response()->json([
            'ok' => true,
            'rent_detail' => $detail,
            'message' => 'URL Monitor actualizada.'
        ]);
    }

    // =========================================================
    // CONSUMIBLES
    // =========================================================

    /**
     * Obtener consumibles de un equipo
     */
    public function getConsumables($detailId)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el equipo pertenece a esta tienda
        $detail = RentDetail::where('shop_id', $shop->id)->findOrFail($detailId);

        $consumables = Consumables::where('rent_detail_id', $detailId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'ok' => true,
            'consumables' => $consumables
        ]);
    }

    /**
     * Agregar consumible a un equipo
     */
    public function storeConsumable(Request $request, $detailId)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
            'counter' => 'nullable|integer|min:0',
            'observation' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el equipo pertenece a esta tienda
        $detail = RentDetail::where('shop_id', $shop->id)->findOrFail($detailId);

        $consumable = new Consumables();
        $consumable->rent_detail_id = $detailId;
        $consumable->description = $request->description;
        $consumable->qty = $request->qty;
        $consumable->counter = $request->counter ?? 0;
        $consumable->observation = $request->observation;
        $consumable->save();

        return response()->json([
            'ok' => true,
            'consumable' => $consumable,
            'message' => 'Consumible agregado correctamente.'
        ]);
    }
}
