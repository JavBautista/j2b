<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopReceiptSetting;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ReceiptSettingsController extends Controller
{
    public function index()
    {
        return view('admin.configurations.receipt-settings.index');
    }

    public function get(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $shop = $request->user()->shop;

        $settings = ShopReceiptSetting::firstOrCreate(
            ['shop_id' => $shop->id],
            ['show_qr' => true, 'qr_url_source' => 'web', 'show_logo' => true, 'show_signature' => false]
        );

        // URLs disponibles de la tienda
        $urlSources = [];
        $fields = ['web', 'facebook', 'instagram', 'twitter', 'pinterest', 'video_channel'];
        foreach ($fields as $field) {
            $urlSources[] = [
                'key' => $field,
                'label' => $this->getFieldLabel($field),
                'value' => $shop->$field ?? '',
                'has_value' => !empty(trim($shop->$field ?? '')),
            ];
        }

        // Logo URL
        $logoUrl = null;
        if (!empty(trim($shop->logo ?? ''))) {
            $logoUrl = asset('storage/' . $shop->logo);
        }

        return response()->json([
            'ok' => true,
            'settings' => $settings,
            'url_sources' => $urlSources,
            'shop_name' => $shop->name,
            'logo_url' => $logoUrl,
        ]);
    }

    public function save(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $request->validate([
            'show_qr' => 'required|boolean',
            'qr_url_source' => 'required|in:web,facebook,instagram,twitter,pinterest,video_channel',
            'show_logo' => 'required|boolean',
            'show_signature' => 'required|boolean',
        ]);

        $shop = $request->user()->shop;

        $settings = ShopReceiptSetting::updateOrCreate(
            ['shop_id' => $shop->id],
            [
                'show_qr' => $request->show_qr,
                'qr_url_source' => $request->qr_url_source,
                'show_logo' => $request->show_logo,
                'show_signature' => $request->show_signature,
            ]
        );

        return response()->json([
            'ok' => true,
            'message' => 'Configuración de recibos guardada',
            'settings' => $settings,
        ]);
    }

    public function qrPreview(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $svg = QrCode::size(150)->generate($request->url);

        return response($svg, 200)->header('Content-Type', 'image/svg+xml');
    }

    private function getFieldLabel(string $field): string
    {
        return match ($field) {
            'web' => 'Sitio Web',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'twitter' => 'Twitter / X',
            'pinterest' => 'Pinterest',
            'video_channel' => 'YouTube',
            default => $field,
        };
    }
}
