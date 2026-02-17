<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CfdiEmisor;
use App\Models\Shop;
use App\Services\Facturacion\HubCfdiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CfdiConfigController extends Controller
{
    /**
     * Vista de configuración CFDI para admin de tienda
     */
    public function index()
    {
        $shop = auth()->user()->shop;

        if (!$shop || !$shop->cfdi_enabled) {
            return redirect()->route('admin.index')
                ->with('error', 'La facturación CFDI no está habilitada para tu tienda.');
        }

        return view('admin.cfdi.config');
    }

    /**
     * Obtener datos del emisor CFDI de la tienda
     */
    public function get(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $shop = auth()->user()->shop;

        if (!$shop || !$shop->cfdi_enabled) {
            return response()->json(['ok' => false, 'message' => 'CFDI no habilitado'], 403);
        }

        $emisor = CfdiEmisor::where('shop_id', $shop->id)->first();

        return response()->json([
            'ok' => true,
            'emisor' => $emisor,
            'timbres_contratados' => $shop->cfdi_timbres_contratados ?? 0,
            'has_cer' => $emisor && !empty($emisor->cer_file),
            'has_key' => $emisor && !empty($emisor->key_file),
        ]);
    }

    /**
     * Guardar datos fiscales del emisor
     */
    public function save(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $shop = auth()->user()->shop;

        if (!$shop || !$shop->cfdi_enabled) {
            return response()->json(['ok' => false, 'message' => 'CFDI no habilitado'], 403);
        }

        $request->validate([
            'rfc' => 'required|string|max:13',
            'razon_social' => 'required|string|max:255',
            'regimen_fiscal' => 'required|string|max:3',
            'codigo_postal' => 'required|string|max:5',
            'serie' => 'nullable|string|max:5',
        ]);

        $emisor = CfdiEmisor::updateOrCreate(
            ['shop_id' => $shop->id],
            [
                'rfc' => strtoupper($request->rfc),
                'razon_social' => $request->razon_social,
                'regimen_fiscal' => $request->regimen_fiscal,
                'codigo_postal' => $request->codigo_postal,
                'serie' => $request->serie ?? 'A',
                'timbres_asignados' => $shop->cfdi_timbres_contratados ?? 0,
            ]
        );

        return response()->json([
            'ok' => true,
            'message' => 'Datos fiscales guardados correctamente',
            'emisor' => $emisor,
        ]);
    }

    /**
     * Subir archivos CSD (.cer y .key)
     */
    public function uploadCsd(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $shop = auth()->user()->shop;

        if (!$shop || !$shop->cfdi_enabled) {
            return response()->json(['ok' => false, 'message' => 'CFDI no habilitado'], 403);
        }

        $request->validate([
            'cer_file' => 'required|file|max:2048',
            'key_file' => 'required|file|max:2048',
            'password' => 'required|string',
        ]);

        $emisor = CfdiEmisor::where('shop_id', $shop->id)->first();

        if (!$emisor) {
            return response()->json(['ok' => false, 'message' => 'Primero guarda los datos fiscales'], 422);
        }

        $dir = "private/cfdi/{$shop->id}";

        $cerPath = $request->file('cer_file')->storeAs($dir, 'csd.cer');
        $keyPath = $request->file('key_file')->storeAs($dir, 'csd.key');

        $wasRegistered = $emisor->is_registered;

        $updateData = [
            'cer_file' => $cerPath,
            'key_file' => $keyPath,
            'password' => $request->password,
        ];

        // Si ya estaba registrado, desactivar hasta re-registro
        if ($wasRegistered) {
            $updateData['is_registered'] = false;
        }

        $emisor->update($updateData);

        return response()->json([
            'ok' => true,
            'message' => $wasRegistered
                ? 'Archivos CSD actualizados. Debe re-activar la facturacion.'
                : 'Archivos CSD subidos correctamente',
            'has_cer' => true,
            'has_key' => true,
            'needs_reregistration' => $wasRegistered,
            'emisor' => $emisor->fresh(),
        ]);
    }

    /**
     * Registrar emisor en HUB CFDI
     */
    public function registrar(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $shop = auth()->user()->shop;

        if (!$shop || !$shop->cfdi_enabled) {
            return response()->json(['ok' => false, 'message' => 'CFDI no habilitado'], 403);
        }

        $emisor = CfdiEmisor::where('shop_id', $shop->id)->first();

        if (!$emisor) {
            return response()->json(['ok' => false, 'message' => 'No hay datos del emisor'], 422);
        }

        if (empty($emisor->rfc) || empty($emisor->cer_file) || empty($emisor->key_file) || empty($emisor->password)) {
            return response()->json(['ok' => false, 'message' => 'Faltan datos fiscales o archivos CSD'], 422);
        }

        try {
            // Leer archivos CSD y codificar en base64
            $cerContent = Storage::get($emisor->cer_file);
            $keyContent = Storage::get($emisor->key_file);

            $hubService = new HubCfdiService();

            // Registrar emisor (campos exactos de la API HUB CFDI)
            $result = $hubService->registrarEmisor([
                'RfcEmisor' => $emisor->rfc,
                'Base64Cer' => base64_encode($cerContent),
                'Base64Key' => base64_encode($keyContent),
                'Contrasena' => $emisor->password,
            ]);

            if (!$result['success']) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Error al registrar emisor: ' . ($result['error'] ?? 'Error desconocido'),
                ]);
            }

            // Asignar timbres
            $timbres = $emisor->timbres_asignados ?: ($shop->cfdi_timbres_contratados ?? 0);
            $timbresResult = null;

            if ($timbres > 0) {
                $timbresResult = $hubService->asignarTimbres($emisor->rfc, $timbres);
            }

            // Actualizar emisor como registrado
            $emisor->update([
                'is_registered' => true,
                'hub_response' => [
                    'registro' => $result['data'],
                    'timbres' => $timbresResult['data'] ?? null,
                ],
            ]);

            Log::info('CFDI Emisor registrado', [
                'shop_id' => $shop->id,
                'rfc' => $emisor->rfc,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Emisor registrado exitosamente en HUB CFDI',
                'emisor' => $emisor->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error registrando emisor CFDI', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Error al conectar con HUB CFDI: ' . $e->getMessage(),
            ], 500);
        }
    }
}
