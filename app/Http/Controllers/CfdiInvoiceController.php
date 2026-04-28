<?php

namespace App\Http\Controllers;

use App\Models\CfdiEmisor;
use App\Models\CfdiInvoice;
use App\Models\Receipt;
use App\Services\Facturacion\CfdiTimbradoService;
use App\Exports\FacturasEmitidasExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class CfdiInvoiceController extends Controller
{
    /**
     * Obtener facturas emitidas con filtros (API JSON)
     */
    public function getFacturas(Request $request)
    {
        $shop = auth()->user()->shop;
        if (!$shop || !$shop->cfdi_enabled) {
            return response()->json(['ok' => false, 'message' => 'CFDI no habilitado'], 403);
        }

        $fechaInicio = $request->fecha_inicio
            ? Carbon::parse($request->fecha_inicio)->startOfDay()
            : Carbon::now('America/Mexico_City')->startOfMonth()->startOfDay();
        $fechaFin = $request->fecha_fin
            ? Carbon::parse($request->fecha_fin)->endOfDay()
            : Carbon::now('America/Mexico_City')->endOfDay();

        $query = CfdiInvoice::where('shop_id', $shop->id)
            ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin]);

        if ($request->status && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }

        if ($request->buscar) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('receptor_rfc', 'like', "%{$buscar}%")
                  ->orWhere('receptor_nombre', 'like', "%{$buscar}%");
            });
        }

        $facturas = $query->with('receipt:id,folio')->orderBy('fecha_emision', 'desc')->get();

        $vigentes = $facturas->where('status', 'vigente');
        $canceladas = $facturas->where('status', 'cancelada');

        return response()->json([
            'ok' => true,
            'periodo' => $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'),
            'totales' => [
                'count' => $facturas->count(),
                'vigentes' => $vigentes->count(),
                'canceladas' => $canceladas->count(),
                'subtotal' => round($vigentes->sum('subtotal'), 2),
                'impuestos' => round($vigentes->sum('total_impuestos'), 2),
                'total' => round($vigentes->sum('total'), 2),
            ],
            'facturas' => $facturas->map(function ($f) {
                return [
                    'id' => $f->id,
                    'uuid' => $f->uuid,
                    'serie' => $f->serie,
                    'folio' => $f->folio,
                    'fecha_emision' => $f->fecha_emision ? $f->fecha_emision->format('d/m/Y H:i') : null,
                    'fecha_timbrado' => $f->fecha_timbrado ? $f->fecha_timbrado->format('d/m/Y H:i') : null,
                    'receptor_rfc' => $f->receptor_rfc,
                    'receptor_nombre' => $f->receptor_nombre,
                    'receipt_folio' => $f->receipt ? $f->receipt->folio : null,
                    'subtotal' => $f->subtotal,
                    'total_impuestos' => $f->total_impuestos,
                    'total' => $f->total,
                    'status' => $f->status,
                ];
            })->values(),
        ]);
    }

    /**
     * Exportar facturas emitidas a Excel
     */
    public function exportFacturas(Request $request)
    {
        $shop = auth()->user()->shop;
        if (!$shop || !$shop->cfdi_enabled) {
            return response()->json(['ok' => false, 'message' => 'CFDI no habilitado'], 403);
        }

        $fechaInicio = $request->fecha_inicio ?: Carbon::now('America/Mexico_City')->startOfMonth()->format('Y-m-d');
        $fechaFin = $request->fecha_fin ?: Carbon::now('America/Mexico_City')->format('Y-m-d');
        $status = $request->status ?: 'todos';

        return Excel::download(
            new FacturasEmitidasExport($shop, $fechaInicio, $fechaFin, $status),
            'facturas_emitidas_' . date('Ymd') . '.xlsx'
        );
    }

    /**
     * Descargar factura en formato XML o PDF.
     * Replica la lógica del Admin\CfdiInvoiceController@descargar para la API.
     */
    public function descargar($id, $formato)
    {
        $shop = auth()->user()->shop;

        $invoice = CfdiInvoice::where('id', $id)
            ->where('shop_id', $shop->id)
            ->first();

        if (!$invoice) {
            return response()->json(['ok' => false, 'message' => 'Factura no encontrada'], 404);
        }

        if (!in_array($formato, ['xml', 'pdf'])) {
            return response()->json(['ok' => false, 'message' => 'Formato no válido'], 422);
        }

        $contentType = $formato === 'xml' ? 'application/xml' : 'application/pdf';
        $filename = "factura_{$invoice->serie}{$invoice->folio}.{$formato}";
        $pathColumn = "{$formato}_path";

        // 1. Servir desde storage local si existe
        if ($invoice->$pathColumn && Storage::disk('cfdi')->exists($invoice->$pathColumn)) {
            $content = Storage::disk('cfdi')->get($invoice->$pathColumn);

            return response($content)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        }

        // 2. Fallback según formato
        try {
            if ($formato === 'pdf') {
                // PDF: generar localmente con dompdf usando el controller Admin
                $adminController = app(\App\Http\Controllers\Admin\CfdiInvoiceController::class);
                $content = $adminController->generarPdf($invoice);

                // Guardar para futuras descargas
                $path = "{$shop->id}/{$invoice->uuid}.pdf";
                Storage::disk('cfdi')->put($path, $content);
                $invoice->pdf_path = $path;
                $invoice->save();
            } else {
                // XML: descargar de TBT API
                $hubService = new \App\Services\Facturacion\HubCfdiService();
                $result = $hubService->descargar($invoice->uuid, 'xml');

                if (!$result['success']) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Error al descargar XML: ' . ($result['error'] ?? 'Error desconocido'),
                    ]);
                }

                $base64 = $result['data']['archivo'] ?? $result['data']['base64'] ?? null;
                if (!$base64) {
                    return response()->json(['ok' => false, 'message' => 'No se recibió el XML de la API']);
                }

                $content = base64_decode($base64);

                // Backfill: guardar localmente
                $path = "{$shop->id}/{$invoice->uuid}.xml";
                Storage::disk('cfdi')->put($path, $content);
                $invoice->xml_path = $path;
                $invoice->save();
            }

            return response($content)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Exception $e) {
            Log::error('CFDI API Descarga error', [
                'invoice_id' => $id,
                'formato' => $formato,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Error al descargar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Descarga pública de factura XML o PDF (sin autenticación).
     * Ruta web: /print-cfdi/{id}/{formato}
     * Mismo patrón que /print-receipt-rent
     */
    public function descargarPublic($id, $formato)
    {
        $invoice = CfdiInvoice::find($id);

        if (!$invoice) {
            abort(404, 'Factura no encontrada');
        }

        if (!in_array($formato, ['xml', 'pdf'])) {
            abort(422, 'Formato no válido');
        }

        $contentType = $formato === 'xml' ? 'application/xml' : 'application/pdf';
        $filename = "factura_{$invoice->serie}{$invoice->folio}.{$formato}";
        $pathColumn = "{$formato}_path";

        // 1. Servir desde storage local si existe
        if ($invoice->$pathColumn && Storage::disk('cfdi')->exists($invoice->$pathColumn)) {
            $content = Storage::disk('cfdi')->get($invoice->$pathColumn);

            return response($content)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
        }

        // 2. Fallback según formato
        try {
            if ($formato === 'pdf') {
                $adminController = app(\App\Http\Controllers\Admin\CfdiInvoiceController::class);
                $content = $adminController->generarPdf($invoice);

                $path = "{$invoice->shop_id}/{$invoice->uuid}.pdf";
                Storage::disk('cfdi')->put($path, $content);
                $invoice->pdf_path = $path;
                $invoice->save();
            } else {
                $hubService = new \App\Services\Facturacion\HubCfdiService();
                $result = $hubService->descargar($invoice->uuid, 'xml');

                if (!$result['success']) {
                    abort(500, 'Error al descargar XML');
                }

                $base64 = $result['data']['archivo'] ?? $result['data']['base64'] ?? null;
                if (!$base64) {
                    abort(500, 'No se recibió el XML');
                }

                $content = base64_decode($base64);

                $path = "{$invoice->shop_id}/{$invoice->uuid}.xml";
                Storage::disk('cfdi')->put($path, $content);
                $invoice->xml_path = $path;
                $invoice->save();
            }

            return response($content)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', "inline; filename=\"{$filename}\"");

        } catch (\Exception $e) {
            Log::error('CFDI Public Descarga error', [
                'invoice_id' => $id,
                'formato' => $formato,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Error al descargar');
        }
    }

    /**
     * Datos del receipt para preparar timbrado CFDI (Ionic).
     * Mirror del endpoint web /admin/facturacion/receipt/{id}/data.
     *
     * GET /api/auth/cfdi/receipt/{id}/data
     * Retorna: receipt (con detail.product y client.fiscalData), emisor, timbres disponibles.
     */
    public function getReceiptData($id)
    {
        $shop = auth()->user()->shop;

        if (!$shop || !$shop->cfdi_enabled) {
            return response()->json(['ok' => false, 'message' => 'CFDI no habilitado'], 403);
        }

        $emisor = CfdiEmisor::where('shop_id', $shop->id)->where('is_registered', true)->first();

        if (!$emisor) {
            return response()->json(['ok' => false, 'message' => 'No hay emisor CFDI registrado'], 422);
        }

        $receipt = Receipt::with(['detail.product', 'client.fiscalData'])
            ->where('id', $id)
            ->where('shop_id', $shop->id)
            ->first();

        if (!$receipt) {
            return response()->json(['ok' => false, 'message' => 'Nota no encontrada'], 404);
        }

        if ($receipt->quotation) {
            return response()->json(['ok' => false, 'message' => 'Las cotizaciones no se pueden facturar'], 422);
        }

        if ($receipt->is_tax_invoiced) {
            return response()->json(['ok' => false, 'message' => 'Esta nota ya fue facturada'], 422);
        }

        $statusPermitidos = [Receipt::STATUS_PAGADA, Receipt::STATUS_POR_FACTURAR];
        if ($receipt->credit) {
            $statusPermitidos[] = Receipt::STATUS_POR_COBRAR;
        }
        if (!in_array($receipt->status, $statusPermitidos)) {
            return response()->json(['ok' => false, 'message' => 'Esta nota no se puede facturar en su estado actual'], 422);
        }

        // T11: bloquear timbrado si total <= 0 (cortesías totales no se facturan al SAT)
        if ($receipt->total <= 0) {
            return response()->json(['ok' => false, 'message' => 'No se puede facturar una nota con total $0 (cortesía total).'], 422);
        }

        return response()->json([
            'ok' => true,
            'receipt' => $receipt,
            'metodo_pago_calculado' => (float) $receipt->received < (float) $receipt->total ? 'PPD' : 'PUE',
            'emisor' => [
                'rfc' => $emisor->rfc,
                'razon_social' => $emisor->razon_social,
                'regimen_fiscal' => $emisor->regimen_fiscal,
                'codigo_postal' => $emisor->codigo_postal,
                'serie' => $emisor->serie,
                'timbres_disponibles' => $emisor->timbresDisponibles(),
            ],
        ]);
    }

    /**
     * Timbrar CFDI desde API (Ionic). Reusa CfdiTimbradoService (mismo algoritmo que web admin).
     *
     * POST /api/auth/cfdi/timbrar
     *   receipt_id                 int
     *   receptor_rfc               string (13)
     *   receptor_razon_social      string
     *   receptor_regimen_fiscal    string (3)
     *   receptor_uso_cfdi          string (3)
     *   receptor_codigo_postal     string (5)
     *   forma_pago                 string (2)     ej. '01'
     *   metodo_pago                string (3)     ej. 'PUE'
     *   conceptos_sat              array?         [{detail_id, clave_prod_serv, clave_unidad, descripcion}]
     *   guardar_datos_cliente      bool?
     */
    public function timbrar(Request $request)
    {
        $shop = auth()->user()->shop;

        if (!$shop || !$shop->cfdi_enabled) {
            return response()->json(['ok' => false, 'message' => 'CFDI no habilitado'], 403);
        }

        $emisor = CfdiEmisor::where('shop_id', $shop->id)->where('is_registered', true)->first();

        if (!$emisor) {
            return response()->json(['ok' => false, 'message' => 'No hay emisor CFDI registrado'], 422);
        }

        if ($emisor->timbresDisponibles() <= 0) {
            return response()->json(['ok' => false, 'message' => 'No hay timbres disponibles'], 422);
        }

        $request->validate([
            'receipt_id' => 'required|integer',
            'receptor_rfc' => 'required|string|max:13',
            'receptor_razon_social' => 'required|string|max:255',
            'receptor_regimen_fiscal' => 'required|string|max:3',
            'receptor_uso_cfdi' => 'required|string|max:3',
            'receptor_codigo_postal' => 'required|string|max:5',
            'forma_pago' => 'required|string|max:2',
            'metodo_pago' => 'required|string|max:3',
        ]);

        $receipt = Receipt::with('detail.product')
            ->where('id', $request->receipt_id)
            ->where('shop_id', $shop->id)
            ->first();

        if (!$receipt) {
            return response()->json(['ok' => false, 'message' => 'Nota no encontrada'], 404);
        }

        if ($receipt->quotation || $receipt->is_tax_invoiced) {
            return response()->json(['ok' => false, 'message' => 'Esta nota no se puede facturar'], 422);
        }

        $statusPermitidos = [Receipt::STATUS_PAGADA, Receipt::STATUS_POR_FACTURAR];
        if ($receipt->credit) {
            $statusPermitidos[] = Receipt::STATUS_POR_COBRAR;
        }
        if (!in_array($receipt->status, $statusPermitidos)) {
            return response()->json(['ok' => false, 'message' => 'Esta nota no se puede facturar en su estado actual'], 422);
        }

        // T11: bloquear timbrado si total <= 0 (cortesías totales no se facturan al SAT)
        if ($receipt->total <= 0) {
            return response()->json(['ok' => false, 'message' => 'No se puede facturar una nota con total $0 (cortesía total).'], 422);
        }

        $service = new CfdiTimbradoService();
        $result = $service->emitir($receipt, $shop, $emisor, [
            'receptor_rfc' => $request->receptor_rfc,
            'receptor_razon_social' => $request->receptor_razon_social,
            'receptor_regimen_fiscal' => $request->receptor_regimen_fiscal,
            'receptor_uso_cfdi' => $request->receptor_uso_cfdi,
            'receptor_codigo_postal' => $request->receptor_codigo_postal,
            'forma_pago' => $request->forma_pago,
            'metodo_pago' => $request->metodo_pago,
            'conceptos_sat' => $request->conceptos_sat ?? [],
            'guardar_datos_cliente' => (bool) $request->guardar_datos_cliente,
        ]);

        if (!$result['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'],
            ], $result['status'] ?? 500);
        }

        $invoice = $result['invoice'];

        // Guardar XML local (el PDF se genera on-demand en /download/{formato})
        $service->guardarArchivosLocales(
            $result['hub_service'],
            $invoice,
            $shop->id,
            null
        );

        return response()->json([
            'ok' => true,
            'message' => 'Factura timbrada exitosamente',
            'uuid' => $invoice->uuid,
            'invoice_id' => $invoice->id,
            'serie' => $invoice->serie,
            'folio' => $invoice->folio,
            'subtotal' => $invoice->subtotal,
            'total_impuestos' => $invoice->total_impuestos,
            'total' => $invoice->total,
            'conceptos_cortesia_excluidos' => $result['conceptos_cortesia_excluidos'],
        ]);
    }
}
