<?php

namespace App\Http\Controllers;

use App\Models\CfdiInvoice;
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
}
