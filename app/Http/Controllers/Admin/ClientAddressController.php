<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageService;

class ClientAddressController extends Controller
{
    /**
     * Obtener direcciones de un cliente
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $clientId = $request->get('client_id');
        
        // Verificar que el cliente pertenece al shop del usuario
        $client = Client::where('id', $clientId)
                       ->where('shop_id', $shop->id)
                       ->firstOrFail();

        $addresses = ClientAddress::where('client_id', $clientId)
                                 ->where('active', 1)
                                 ->orderBy('is_primary', 'desc')
                                 ->orderBy('id', 'desc')
                                 ->get();

        return response()->json([
            'success' => true,
            'addresses' => $addresses,
            'client' => $client
        ]);
    }

    /**
     * Crear nueva dirección
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $request->validate([
            'client_id' => 'required|integer',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
        ]);

        // Verificar que el cliente pertenece al shop del usuario
        $client = Client::where('id', $request->client_id)
                       ->where('shop_id', $shop->id)
                       ->firstOrFail();

        // Si es dirección principal, desmarcar las demás
        if ($request->is_primary) {
            ClientAddress::where('client_id', $request->client_id)
                        ->update(['is_primary' => false]);
        }

        $address = ClientAddress::create([
            'client_id' => $request->client_id,
            'name' => $request->name,
            'address' => $request->address,
            'num_ext' => $request->num_ext,
            'num_int' => $request->num_int,
            'colony' => $request->colony,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country ?? 'México',
            'postal_code' => $request->postal_code,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'description' => $request->description,
            'phone' => $request->phone,
            'email' => $request->email,
            'is_primary' => $request->is_primary ?? false,
            'active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dirección creada correctamente',
            'address' => $address
        ]);
    }

    /**
     * Actualizar dirección
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $request->validate([
            'id' => 'required|integer',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
        ]);

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

        $address->update([
            'name' => $request->name,
            'address' => $request->address,
            'num_ext' => $request->num_ext,
            'num_int' => $request->num_int,
            'colony' => $request->colony,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country ?? 'México',
            'postal_code' => $request->postal_code,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'description' => $request->description,
            'phone' => $request->phone,
            'email' => $request->email,
            'is_primary' => $request->is_primary ?? false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dirección actualizada correctamente',
            'address' => $address
        ]);
    }

    /**
     * Desactivar dirección
     */
    public function inactive(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $address = ClientAddress::with('client')
                               ->whereHas('client', function($query) use ($shop) {
                                   $query->where('shop_id', $shop->id);
                               })
                               ->findOrFail($request->id);

        $address->update(['active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Dirección desactivada correctamente'
        ]);
    }

    /**
     * Activar dirección
     */
    public function active(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $address = ClientAddress::with('client')
                               ->whereHas('client', function($query) use ($shop) {
                                   $query->where('shop_id', $shop->id);
                               })
                               ->findOrFail($request->id);

        $address->update(['active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Dirección activada correctamente'
        ]);
    }

    /**
     * Subir imagen de ubicación
     */
    public function uploadLocationImage(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $request->validate([
            'address_id' => 'required|integer',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $address = ClientAddress::with('client')
                               ->whereHas('client', function($query) use ($shop) {
                                   $query->where('shop_id', $shop->id);
                               })
                               ->findOrFail($request->address_id);

        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($address->location_image) {
                Storage::disk('public')->delete($address->location_image);
            }

            $image = $request->file('image');
            // Procesar y optimizar la imagen
            $imageService = new ImageService();
            $imagePath = $imageService->processAndStore($image, 'client_addresses');
            $address->update(['location_image' => $imagePath]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Imagen subida correctamente',
            'address' => $address
        ]);
    }

    /**
     * Eliminar imagen de ubicación
     */
    public function deleteLocationImage(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $address = ClientAddress::with('client')
                               ->whereHas('client', function($query) use ($shop) {
                                   $query->where('shop_id', $shop->id);
                               })
                               ->findOrFail($request->id);

        if ($address->location_image) {
            Storage::disk('public')->delete($address->location_image);
            $address->update(['location_image' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada correctamente',
            'address' => $address
        ]);
    }
}
