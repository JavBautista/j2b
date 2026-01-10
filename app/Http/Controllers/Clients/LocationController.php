<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    /**
     * Obtener la ubicación del cliente autenticado
     */
    public function getMyLocation(Request $request)
    {
        try {
            $user = $request->user();
            
            // Verificar que el usuario tenga un cliente asociado
            if (!$user->client) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No tienes un cliente asociado a tu cuenta.'
                ], 400);
            }
            
            $client = $user->client;
            
            return response()->json([
                'ok' => true,
                'location' => [
                    'latitude' => $client->location_latitude,
                    'longitude' => $client->location_longitude,
                    'has_location' => !empty($client->location_latitude) && !empty($client->location_longitude)
                ],
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error getting client location: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'message' => 'Error al obtener la ubicación.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Guardar la ubicación del cliente autenticado (solo si no tiene una)
     */
    public function saveMyLocation(Request $request)
    {
        try {
            $user = $request->user();
            
            // Verificar que el usuario tenga un cliente asociado
            if (!$user->client) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No tienes un cliente asociado a tu cuenta.'
                ], 400);
            }
            
            $client = $user->client;
            
            // Verificar si ya tiene ubicación guardada
            if (!empty($client->location_latitude) && !empty($client->location_longitude)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Ya tienes una ubicación guardada. No puedes modificarla.',
                    'location' => [
                        'latitude' => $client->location_latitude,
                        'longitude' => $client->location_longitude
                    ]
                ], 400);
            }
            
            // Validar datos de entrada
            $request->validate([
                'latitude' => 'required|string',
                'longitude' => 'required|string'
            ]);
            
            // Guardar ubicación
            $client->location_latitude = $request->latitude;
            $client->location_longitude = $request->longitude;
            $client->save();
            
            return response()->json([
                'ok' => true,
                'message' => 'Ubicación guardada exitosamente.',
                'location' => [
                    'latitude' => $client->location_latitude,
                    'longitude' => $client->location_longitude
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error saving client location: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'message' => 'Error al guardar la ubicación.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verificar si el cliente puede guardar ubicación
     */
    public function canSaveLocation(Request $request)
    {
        try {
            $user = $request->user();
            
            // Verificar que el usuario tenga un cliente asociado
            if (!$user->client) {
                return response()->json([
                    'ok' => false,
                    'can_save' => false,
                    'message' => 'No tienes un cliente asociado a tu cuenta.'
                ], 400);
            }
            
            $client = $user->client;
            
            // Verificar si ya tiene ubicación
            $hasLocation = !empty($client->location_latitude) && !empty($client->location_longitude);
            
            return response()->json([
                'ok' => true,
                'can_save' => !$hasLocation,
                'has_location' => $hasLocation,
                'message' => $hasLocation 
                    ? 'Ya tienes una ubicación guardada.' 
                    : 'Puedes guardar tu ubicación.'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error checking location permission: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'message' => 'Error al verificar el estado de ubicación.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}