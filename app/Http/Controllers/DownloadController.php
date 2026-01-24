<?php

namespace App\Http\Controllers;

use App\Models\DownloadLead;
use App\Models\SubscriptionSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    /**
     * Muestra el formulario de descarga
     */
    public function showForm()
    {
        $trialDays = SubscriptionSetting::get('trial_days', 30);
        return view('web.download', compact('trialDays'));
    }

    /**
     * Procesa el email y fuerza la descarga del APK
     */
    public function processDownload(Request $request)
    {
        // Validar email
        $request->validate([
            'email' => 'required|email|max:255',
        ], [
            'email.required' => 'Por favor ingresa tu correo electrónico.',
            'email.email' => 'Por favor ingresa un correo electrónico válido.',
        ]);

        // Guardar lead
        DownloadLead::create([
            'email' => $request->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Verificar que el archivo existe
        // Primero buscar en /apk (ubicación actual), luego en /downloads
        $filePath = storage_path('app/public/apk/j2b.apk');
        if (!file_exists($filePath)) {
            $filePath = storage_path('app/public/downloads/j2biznes.apk');
        }

        if (!file_exists($filePath)) {
            return back()->with('error', 'El archivo de descarga no está disponible en este momento. Por favor intenta más tarde.');
        }

        // Forzar descarga con nombre amigable
        return response()->download($filePath, 'J2Biznes.apk', [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
}
