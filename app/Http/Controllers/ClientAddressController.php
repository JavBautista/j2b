<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientAddressController extends Controller
{
    /**
     * Obtener todas las direcciones de un cliente
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $client_id = $request->client_id;
        
        // Verificar que el cliente pertenece al shop del usuario
        $client = Client::where('id', $client_id)
                       ->where('shop_id', $shop->id)
                       ->where('active', 1)
                       ->firstOrFail();

        $addresses = ClientAddress::where('client_id', $client_id)
                                 ->where('active', 1)
                                 ->orderBy('is_primary', 'desc')
                                 ->orderBy('id', 'desc')
                                 ->paginate(10);

        return $addresses;
    }

    /**
     * Crear nueva dirección
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece al shop del usuario
        $client = Client::where('id', $request->client_id)
                       ->where('shop_id', $shop->id)
                       ->where('active', 1)
                       ->firstOrFail();

        // Si es dirección principal, desmarcar las demás
        if ($request->is_primary) {
            ClientAddress::where('client_id', $request->client_id)
                        ->update(['is_primary' => false]);
        }

        $address = new ClientAddress();
        $address->client_id = $request->client_id;
        $address->name = $request->name;
        $address->address = $request->address;
        $address->num_ext = $request->num_ext;
        $address->num_int = $request->num_int;
        $address->colony = $request->colony;
        $address->city = $request->city;
        $address->state = $request->state;
        $address->country = $request->country ?? 'México';
        $address->postal_code = $request->postal_code;
        $address->latitude = $request->latitude;
        $address->longitude = $request->longitude;
        $address->description = $request->description;
        $address->phone = $request->phone;
        $address->email = $request->email;
        $address->is_primary = $request->is_primary ?? false;
        $address->active = 1;
        $address->save();

        return response()->json([
            'ok' => true,
            'address' => $address,
            'client' => $client
        ]);
    }

    /**
     * Actualizar dirección
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        // Verificar que la dirección pertenece a un cliente del shop
        $address = ClientAddress::with('client')
                               ->whereHas('client', function($query) use ($shop) {
                                   $query->where('shop_id', $shop->id);
                               })
                               ->findOrFail($request->id);

        // Si es dirección principal, desmarcar las demás
        if ($request->is_primary && !$address->is_primary) {
            ClientAddress::where('client_id', $address->client_id)
                        ->where('id', '!=', $address->id)
                        ->update(['is_primary' => false]);
        }

        $address->name = $request->name;
        $address->address = $request->address;
        $address->num_ext = $request->num_ext;
        $address->num_int = $request->num_int;
        $address->colony = $request->colony;
        $address->city = $request->city;
        $address->state = $request->state;
        $address->country = $request->country ?? 'México';
        $address->postal_code = $request->postal_code;
        $address->latitude = $request->latitude;
        $address->longitude = $request->longitude;
        $address->description = $request->description;
        $address->phone = $request->phone;
        $address->email = $request->email;
        $address->is_primary = $request->is_primary ?? false;
        $address->save();

        return response()->json([
            'ok' => true,
            'address' => $address
        ]);
    }

    /**
     * Desactivar dirección (soft delete)
     */
    public function inactive(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $address = ClientAddress::with('client')
                               ->whereHas('client', function($query) use ($shop) {
                                   $query->where('shop_id', $shop->id);
                               })
                               ->findOrFail($request->id);

        $address->active = 0;
        $address->save();

        return response()->json([
            'ok' => true
        ]);
    }

    /**
     * Subir imagen de ubicación
     */
    public function uploadLocationImage(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $address = ClientAddress::with('client')
                               ->whereHas('client', function($query) use ($shop) {
                                   $query->where('shop_id', $shop->id);
                               })
                               ->findOrFail($request->address_id);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('client_addresses', 'public');
            $address->location_image = $imagePath;
            $address->save();
        }

        return response()->json([
            'ok' => true,
            'address' => $address
        ]);
    }

    /**
     * Eliminar imagen de ubicación
     */
    public function deleteLocationImage(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $address = ClientAddress::with('client')
                               ->whereHas('client', function($query) use ($shop) {
                                   $query->where('shop_id', $shop->id);
                               })
                               ->findOrFail($request->id);

        $imagePath = $address->location_image;
        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
            $address->location_image = null;
            $address->save();
        }

        return response()->json([
            'ok' => true,
            'address' => $address
        ]);
    }

    /**
     * Actualizar coordenadas GPS
     */
    public function updateLocation(Request $request)
    {
        try {
            $user = $request->user();
            $shop = $user->shop;
            
            $request->validate([
                'address_id' => 'required|integer|exists:client_addresses,id',
                'latitude' => 'required|string',
                'longitude' => 'required|string'
            ]);

            $address = ClientAddress::with('client')
                                   ->whereHas('client', function($query) use ($shop) {
                                       $query->where('shop_id', $shop->id);
                                   })
                                   ->findOrFail($request->address_id);

            $address->latitude = $request->latitude;
            $address->longitude = $request->longitude;
            $address->save();

            return response()->json([
                'ok' => true,
                'message' => 'Ubicación actualizada correctamente',
                'address' => $address
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al actualizar ubicación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover coordenadas GPS
     */
    public function removeLocation(Request $request)
    {
        try {
            $user = $request->user();
            $shop = $user->shop;
            
            $request->validate([
                'address_id' => 'required|integer|exists:client_addresses,id'
            ]);

            $address = ClientAddress::with('client')
                                   ->whereHas('client', function($query) use ($shop) {
                                       $query->where('shop_id', $shop->id);
                                   })
                                   ->findOrFail($request->address_id);

            $address->latitude = null;
            $address->longitude = null;
            $address->save();

            return response()->json([
                'ok' => true,
                'message' => 'Ubicación eliminada correctamente',
                'address' => $address
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al eliminar ubicación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
