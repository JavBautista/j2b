<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CfdiEmisor;
use App\Models\CfdiInvoice;
use App\Models\ClientFiscalData;
use App\Models\Receipt;
use App\Services\Facturacion\HubCfdiService;
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
     * Vista index de facturas emitidas (Blade + Vue)
     */
    public function indexFacturas()
    {
        $shop = auth()->user()->shop;
        if (!$shop || !$shop->cfdi_enabled) {
            return redirect('/admin')->with('error', 'CFDI no habilitado');
        }
        return view('admin.cfdi.facturas');
    }

    /**
     * Obtener facturas emitidas con filtros (JSON)
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
            return redirect('/admin')->with('error', 'CFDI no habilitado');
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
     * Obtener datos de un receipt para facturación (AJAX)
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

        $receipt = Receipt::with(['detail', 'client.fiscalData'])
            ->where('id', $id)
            ->where('shop_id', $shop->id)
            ->first();

        if (!$receipt) {
            return response()->json(['ok' => false, 'message' => 'Nota no encontrada'], 404);
        }

        // Verificar que se puede facturar
        if ($receipt->quotation) {
            return response()->json(['ok' => false, 'message' => 'Las cotizaciones no se pueden facturar'], 422);
        }

        if ($receipt->is_tax_invoiced) {
            return response()->json(['ok' => false, 'message' => 'Esta nota ya fue facturada'], 422);
        }

        if (!in_array($receipt->status, ['PAGADA', 'POR FACTURAR'])) {
            return response()->json(['ok' => false, 'message' => 'Solo se pueden facturar notas con status PAGADA o POR FACTURAR'], 422);
        }

        return response()->json([
            'ok' => true,
            'receipt' => $receipt,
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
     * Timbrar factura CFDI desde un receipt (AJAX)
     */
    public function timbrar(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

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

        $receipt = Receipt::with('detail')
            ->where('id', $request->receipt_id)
            ->where('shop_id', $shop->id)
            ->first();

        if (!$receipt) {
            return response()->json(['ok' => false, 'message' => 'Nota no encontrada'], 404);
        }

        if ($receipt->quotation || $receipt->is_tax_invoiced) {
            return response()->json(['ok' => false, 'message' => 'Esta nota no se puede facturar'], 422);
        }

        if (!in_array($receipt->status, ['PAGADA', 'POR FACTURAR'])) {
            return response()->json(['ok' => false, 'message' => 'Solo se pueden facturar notas PAGADA o POR FACTURAR'], 422);
        }

        try {
            // Armar JSON CFDI (folio se revierte si el timbrado falla)
            $folio = $emisor->siguienteFolio();
            $fechaEmision = Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s');

            $receptorRfc = strtoupper($request->receptor_rfc);
            $esPublicoGeneral = ($receptorRfc === 'XAXX010101000');

            // Calcular conceptos e impuestos
            $conceptos = [];
            $subtotalTotal = 0;
            $ivaTotal = 0;
            $tieneIva = $receipt->iva > 0;

            foreach ($receipt->detail as $item) {
                // Saltar items con precio/subtotal = 0 (SAT no acepta valor_unitario ni base = 0)
                if (round($item->subtotal, 2) <= 0) {
                    continue;
                }

                if ($tieneIva) {
                    // IVA ya separado en la nota
                    $valorUnitario = round($item->price, 2);
                    $subtotalItem = round($item->subtotal, 2);
                    // SAT exige: importe traslado = round(base × tasa_cuota, 2)
                    $ivaItem = round($subtotalItem * 0.16, 2);
                } else {
                    // Extraer IVA de los precios (el precio ya incluye IVA)
                    $valorUnitario = round($item->price / 1.16, 2);
                    $subtotalItem = round($item->subtotal / 1.16, 2);
                    $ivaItem = round($subtotalItem * 0.16, 2);
                }

                $concepto = [
                    'clave_prod_serv' => '01010101',
                    'descripcion' => $item->descripcion,
                    'cantidad' => $item->qty,
                    'clave_unidad' => 'E48',
                    'valor_unitario' => $valorUnitario,
                    'subtotal' => $subtotalItem,
                    'importe' => $subtotalItem,
                    'objeto_impuesto' => '02',
                    'impuestos' => [
                        'traslados' => [
                            [
                                'base' => $subtotalItem,
                                'impuesto' => '002',
                                'tipo_factor' => 'Tasa',
                                'tasa_cuota' => '0.160000',
                                'importe' => $ivaItem,
                            ]
                        ]
                    ],
                ];

                $conceptos[] = $concepto;
                $subtotalTotal += $subtotalItem;
                $ivaTotal += $ivaItem;
            }

            // Total CFDI = subtotal + IVA (calculado por item, SAT-compliant)
            // Puede diferir por centavos del receipt.total debido a redondeo — esto es normal
            $total = round($subtotalTotal + $ivaTotal, 2);

            $cfdiPayload = [
                'serie' => $emisor->serie ?? 'A',
                'folio' => (string) $folio,
                'fecha_emision' => $fechaEmision,
                'forma_pago' => $request->forma_pago,
                'metodo_pago' => $request->metodo_pago,
                'tipo_comprobante' => 'I',
                'exportacion' => '01',
                'moneda' => 'MXN',
                'lugar_expedicion' => $emisor->codigo_postal,
                'subtotal' => $subtotalTotal,
                'total' => $total,
                'emisor' => [
                    'rfc' => $emisor->rfc,
                    'razon_social' => $emisor->razon_social,
                    'regimen_fiscal' => $emisor->regimen_fiscal,
                ],
                'receptor' => [
                    'rfc' => $receptorRfc,
                    'razon_social' => strtoupper($request->receptor_razon_social),
                    'uso_cfdi' => $request->receptor_uso_cfdi,
                    'regimen_fiscal' => $request->receptor_regimen_fiscal,
                    'codigo_postal' => $request->receptor_codigo_postal,
                ],
                'conceptos' => $conceptos,
            ];

            // Nodo informacion_global para Público en General
            if ($esPublicoGeneral) {
                $now = Carbon::now('America/Mexico_City');
                $cfdiPayload['informacion_global'] = [
                    'periodicidad' => '04',
                    'meses' => str_pad($now->month, 2, '0', STR_PAD_LEFT),
                    'anio' => (string) $now->year,
                ];
            }

            // Nodo impuestos global (siempre, IVA se desglosa en todos los casos)
            $cfdiPayload['impuestos'] = [
                'total_impuestos_trasladados' => $ivaTotal,
                'traslados' => [
                    [
                        'base' => $subtotalTotal,
                        'impuesto' => '002',
                        'tipo_factor' => 'Tasa',
                        'tasa_cuota' => '0.160000',
                        'importe' => $ivaTotal,
                    ]
                ]
            ];

            // Llamar API
            $hubService = new HubCfdiService();
            $result = $hubService->timbrar($cfdiPayload);

            if (!$result['success']) {
                // Revertir folio para no dejar huecos
                $emisor->revertirFolio();

                Log::error('CFDI Timbrado fallido', [
                    'shop_id' => $shop->id,
                    'receipt_id' => $receipt->id,
                    'folio_revertido' => $folio,
                    'error' => $result['error'],
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'Error al timbrar: ' . ($result['error'] ?? 'Error desconocido'),
                ]);
            }

            $responseData = $result['data'];
            $uuid = $responseData['uuid'] ?? null;

            // Crear registro de factura
            $invoice = CfdiInvoice::create([
                'shop_id' => $shop->id,
                'cfdi_emisor_id' => $emisor->id,
                'receipt_id' => $receipt->id,
                'receptor_rfc' => $receptorRfc,
                'receptor_nombre' => strtoupper($request->receptor_razon_social),
                'receptor_regimen' => $request->receptor_regimen_fiscal,
                'receptor_cp' => $request->receptor_codigo_postal,
                'receptor_uso_cfdi' => $request->receptor_uso_cfdi,
                'uuid' => $uuid,
                'serie' => $emisor->serie ?? 'A',
                'folio' => (string) $folio,
                'fecha_emision' => $fechaEmision,
                'fecha_timbrado' => $responseData['fecha_timbrado'] ?? null,
                'tipo_comprobante' => 'I',
                'forma_pago' => $request->forma_pago,
                'metodo_pago' => $request->metodo_pago,
                'subtotal' => $subtotalTotal,
                'total_impuestos' => $ivaTotal,
                'total' => $total,
                'status' => 'vigente',
                'request_json' => $cfdiPayload,
                'response_json' => $responseData,
            ]);

            // Marcar receipt como facturado y cambiar status
            $receipt->is_tax_invoiced = true;
            $receipt->status = 'PAGADA';
            $receipt->save();

            // Incrementar timbres usados
            $emisor->increment('timbres_usados');

            // Guardar datos fiscales del cliente si se solicitó
            if ($request->guardar_datos_cliente && $receipt->client_id && !$esPublicoGeneral) {
                $existing = ClientFiscalData::where('client_id', $receipt->client_id)
                    ->where('rfc', $receptorRfc)
                    ->first();

                if (!$existing) {
                    // Si es el primer perfil fiscal, marcarlo como default
                    $hasAny = ClientFiscalData::where('client_id', $receipt->client_id)->exists();

                    ClientFiscalData::create([
                        'client_id' => $receipt->client_id,
                        'rfc' => $receptorRfc,
                        'razon_social' => strtoupper($request->receptor_razon_social),
                        'regimen_fiscal' => $request->receptor_regimen_fiscal,
                        'uso_cfdi' => $request->receptor_uso_cfdi,
                        'codigo_postal' => $request->receptor_codigo_postal,
                        'is_default' => !$hasAny,
                    ]);
                }
            }

            // Guardar XML y PDF en storage local
            $this->guardarArchivosLocales($hubService, $invoice, $shop->id);

            Log::info('CFDI Timbrado exitoso', [
                'shop_id' => $shop->id,
                'receipt_id' => $receipt->id,
                'uuid' => $uuid,
                'invoice_id' => $invoice->id,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Factura timbrada exitosamente',
                'uuid' => $uuid,
                'invoice_id' => $invoice->id,
                'serie' => $invoice->serie,
                'folio' => $invoice->folio,
            ]);

        } catch (\Exception $e) {
            // Revertir folio si ya se había incrementado
            if (isset($folio)) {
                $emisor->revertirFolio();
            }

            Log::error('CFDI Timbrado exception', [
                'shop_id' => $shop->id,
                'receipt_id' => $receipt->id,
                'folio_revertido' => $folio ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Error al timbrar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancelar una factura CFDI (AJAX)
     */
    public function cancelar(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $shop = auth()->user()->shop;

        if (!$shop || !$shop->cfdi_enabled) {
            return response()->json(['ok' => false, 'message' => 'CFDI no habilitado'], 403);
        }

        $request->validate([
            'invoice_id' => 'required|integer',
            'motivo' => 'required|string|in:01,02,03,04',
            'folio_sustitucion' => 'nullable|string|max:255',
        ]);

        // Motivo 01 requiere folio de sustitución
        if ($request->motivo === '01' && empty($request->folio_sustitucion)) {
            return response()->json([
                'ok' => false,
                'message' => 'El motivo 01 requiere el UUID de la factura que sustituye',
            ], 422);
        }

        $invoice = CfdiInvoice::where('id', $request->invoice_id)
            ->where('shop_id', $shop->id)
            ->first();

        if (!$invoice) {
            return response()->json(['ok' => false, 'message' => 'Factura no encontrada'], 404);
        }

        if ($invoice->status !== 'vigente') {
            return response()->json(['ok' => false, 'message' => 'Solo se pueden cancelar facturas vigentes'], 422);
        }

        try {
            $hubService = new HubCfdiService();
            $result = $hubService->cancelar(
                $invoice->uuid,
                $request->motivo,
                $request->folio_sustitucion
            );

            if (!$result['success']) {
                Log::error('CFDI Cancelación fallida', [
                    'shop_id' => $shop->id,
                    'invoice_id' => $invoice->id,
                    'uuid' => $invoice->uuid,
                    'error' => $result['error'],
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'Error al cancelar: ' . ($result['error'] ?? 'Error desconocido'),
                ]);
            }

            // Actualizar factura
            $invoice->update([
                'status' => 'cancelada',
                'motivo_cancelacion' => $request->motivo,
                'fecha_cancelacion' => Carbon::now('America/Mexico_City'),
            ]);

            // Revertir receipt
            if ($invoice->receipt_id) {
                $receipt = Receipt::find($invoice->receipt_id);
                if ($receipt) {
                    $receipt->is_tax_invoiced = false;
                    $receipt->save();
                }
            }

            Log::info('CFDI Cancelación exitosa', [
                'shop_id' => $shop->id,
                'invoice_id' => $invoice->id,
                'uuid' => $invoice->uuid,
                'motivo' => $request->motivo,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Factura cancelada exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('CFDI Cancelación exception', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Error al cancelar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Descargar factura en formato XML o PDF.
     * Sirve desde storage local si existe, fallback a TBT API con backfill.
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
                // PDF: generar localmente con dompdf
                $content = $this->generarPdf($invoice);

                // Guardar para futuras descargas
                $path = "{$shop->id}/{$invoice->uuid}.pdf";
                Storage::disk('cfdi')->put($path, $content);
                $invoice->pdf_path = $path;
                $invoice->save();
            } else {
                // XML: descargar de TBT API
                $hubService = new HubCfdiService();
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
            Log::error('CFDI Descarga error', [
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
     * Guardar XML y PDF de una factura en storage local.
     * No lanza excepción si falla (el timbrado ya fue exitoso).
     */
    private function guardarArchivosLocales(HubCfdiService $hubService, CfdiInvoice $invoice, int $shopId): void
    {
        // 1. XML: seguir descargando de TBT (es el documento fiscal oficial)
        try {
            $result = $hubService->descargar($invoice->uuid, 'xml');

            if ($result['success']) {
                $base64 = $result['data']['archivo'] ?? $result['data']['base64'] ?? null;
                if ($base64) {
                    $path = "{$shopId}/{$invoice->uuid}.xml";
                    Storage::disk('cfdi')->put($path, base64_decode($base64));
                    $invoice->xml_path = $path;
                }
            } else {
                Log::warning("CFDI: No se pudo descargar XML post-timbrado", [
                    'invoice_id' => $invoice->id,
                    'error' => $result['error'],
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("CFDI: Excepción al guardar XML localmente", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 2. PDF: generar localmente con dompdf (incluye logo de la tienda)
        try {
            $pdfContent = $this->generarPdf($invoice);
            $path = "{$shopId}/{$invoice->uuid}.pdf";
            Storage::disk('cfdi')->put($path, $pdfContent);
            $invoice->pdf_path = $path;
        } catch (\Exception $e) {
            Log::warning("CFDI: Excepción al generar PDF localmente", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Guardar paths en DB si se obtuvieron
        if ($invoice->isDirty(['xml_path', 'pdf_path'])) {
            $invoice->save();
        }
    }

    /**
     * Genera el PDF de una factura CFDI usando dompdf.
     * Retorna el contenido binario del PDF.
     */
    public function generarPdf(CfdiInvoice $invoice): string
    {
        $invoice->loadMissing('emisor');
        $emisor = $invoice->emisor;

        // Logo de la tienda (desde shops.logo en storage/public)
        $logoBase64 = null;
        $shop = \App\Models\Shop::find($invoice->shop_id);
        if ($shop && $shop->logo) {
            $logoPath = storage_path('app/public/' . $shop->logo);
            if (file_exists($logoPath)) {
                $logoBase64 = base64_encode(file_get_contents($logoPath));
            }
        }

        // Datos del request_json (conceptos, impuestos)
        $requestData = $invoice->request_json ?? [];
        $conceptos = $requestData['conceptos'] ?? [];

        // Datos del response_json (timbre fiscal)
        $responseData = $invoice->response_json ?? [];
        $timbreFiscal = $responseData['timbre_fiscal'] ?? null;

        // No. Certificado del Emisor: solo disponible en el XML
        $noCertificadoEmisor = null;
        if ($invoice->xml_path && Storage::disk('cfdi')->exists($invoice->xml_path)) {
            try {
                $xml = Storage::disk('cfdi')->get($invoice->xml_path);
                if (preg_match('/NoCertificado="([^"]+)"/', $xml, $matches)) {
                    $noCertificadoEmisor = $matches[1];
                }
            } catch (\Exception $e) {
                // No es crítico, continuar sin este dato
            }
        }

        // Catálogos SAT para descripciones legibles
        $catalogos = [
            'forma_pago' => [
                '01' => 'Efectivo', '02' => 'Cheque nominativo', '03' => 'Transferencia electrónica',
                '04' => 'Tarjeta de crédito', '28' => 'Tarjeta de débito', '99' => 'Por definir',
            ],
            'metodo_pago' => [
                'PUE' => 'Pago en Una sola Exhibición', 'PPD' => 'Pago en Parcialidades o Diferido',
            ],
            'tipo_comprobante' => [
                'I' => 'Ingreso', 'E' => 'Egreso', 'T' => 'Traslado', 'P' => 'Pago',
            ],
            'regimen' => [
                '601' => 'General de Ley PM', '603' => 'PM con Fines no Lucrativos',
                '612' => 'Personas Físicas con Act. Empresariales y Profesionales',
                '616' => 'Sin obligaciones fiscales', '621' => 'Incorporación Fiscal',
                '626' => 'Régimen Simplificado de Confianza',
            ],
            'uso_cfdi' => [
                'G01' => 'Adquisición de mercancías', 'G03' => 'Gastos en general',
                'S01' => 'Sin efectos fiscales',
            ],
        ];

        // Generar QR de verificación SAT (PNG con GD, sin imagick)
        $qrBase64 = null;
        if ($invoice->uuid && $emisor) {
            $sello = $timbreFiscal['sello'] ?? '';
            $fe = substr($sello, -8);
            $totalFormatted = str_pad(number_format($invoice->total, 6, '.', ''), 24, '0', STR_PAD_LEFT);

            $qrUrl = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx"
                . "?id={$invoice->uuid}"
                . "&re={$emisor->rfc}"
                . "&rr={$invoice->receptor_rfc}"
                . "&tt={$totalFormatted}"
                . "&fe={$fe}";

            try {
                $qrPngData = base64_decode($this->generarQrPng($qrUrl));
                $qrTmpPath = sys_get_temp_dir() . '/cfdi_qr_' . $invoice->uuid . '.png';
                file_put_contents($qrTmpPath, $qrPngData);
                $qrBase64 = base64_encode($qrPngData);
            } catch (\Exception $e) {
                Log::warning('CFDI: Error generando QR', ['error' => $e->getMessage()]);
            }
        }

        // Importe con letra
        $importeLetra = $this->numeroALetras($invoice->total) . ' M.N.';

        // Datos adicionales del response_json
        $responseData = $invoice->response_json ?? [];

        $pdf = Pdf::loadView('cfdi.pdf-factura', [
            'invoice' => $invoice,
            'emisor' => $emisor,
            'logoBase64' => $logoBase64,
            'conceptos' => $conceptos,
            'requestData' => $requestData,
            'responseData' => $responseData,
            'timbreFiscal' => $timbreFiscal,
            'catalogos' => $catalogos,
            'qrBase64' => $qrBase64,
            'qrPath' => $qrTmpPath ?? null,
            'noCertificadoEmisor' => $noCertificadoEmisor,
            'importeLetra' => $importeLetra,
        ])->setPaper('letter')
          ->setOption('isRemoteEnabled', true);

        return $pdf->output();
    }

    /**
     * Genera un QR como PNG base64 usando bacon-qr-code + GD (sin imagick).
     */
    private function generarQrPng(string $text): ?string
    {
        $qrCode = \BaconQrCode\Encoder\Encoder::encode(
            $text,
            \BaconQrCode\Common\ErrorCorrectionLevel::L()
        );
        $matrix = $qrCode->getMatrix();
        $size = $matrix->getWidth();
        $scale = 6;
        $margin = 2;
        $imgSize = ($size + $margin * 2) * $scale;

        $img = imagecreatetruecolor($imgSize, $imgSize);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $px = ($x + $margin) * $scale;
                    $py = ($y + $margin) * $scale;
                    imagefilledrectangle($img, $px, $py, $px + $scale - 1, $py + $scale - 1, $black);
                }
            }
        }

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return base64_encode($png);
    }

    /**
     * Convierte un número a su representación en letras (español MX).
     * Ej: 4749.10 → "CUATRO MIL SETECIENTOS CUARENTA Y NUEVE PESOS 10/100"
     */
    private function numeroALetras(float $numero): string
    {
        $entero = (int) floor($numero);
        $centavos = (int) round(($numero - $entero) * 100);

        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $especiales = [11 => 'ONCE', 12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE', 15 => 'QUINCE',
                       16 => 'DIECISEIS', 17 => 'DIECISIETE', 18 => 'DIECIOCHO', 19 => 'DIECINUEVE'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
                     'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $convertirGrupo = function (int $n) use ($unidades, $decenas, $especiales, $centenas): string {
            if ($n === 0) return '';
            if ($n === 100) return 'CIEN';

            $resultado = '';
            if ($n >= 100) {
                $resultado .= $centenas[(int) floor($n / 100)] . ' ';
                $n %= 100;
            }
            if ($n >= 11 && $n <= 19) {
                $resultado .= $especiales[$n];
                return trim($resultado);
            }
            if ($n >= 21 && $n <= 29) {
                $resultado .= 'VEINTI' . $unidades[$n - 20];
                return trim($resultado);
            }
            if ($n >= 10) {
                $resultado .= $decenas[(int) floor($n / 10)];
                $n %= 10;
                if ($n > 0) $resultado .= ' Y ';
            }
            if ($n > 0) {
                $resultado .= $unidades[$n];
            }
            return trim($resultado);
        };

        if ($entero === 0) {
            $texto = 'CERO';
        } elseif ($entero === 1) {
            $texto = 'UN';
        } else {
            $texto = '';
            if ($entero >= 1000000) {
                $millones = (int) floor($entero / 1000000);
                $texto .= ($millones === 1 ? 'UN MILLON' : $convertirGrupo($millones) . ' MILLONES') . ' ';
                $entero %= 1000000;
            }
            if ($entero >= 1000) {
                $miles = (int) floor($entero / 1000);
                $texto .= ($miles === 1 ? 'MIL' : $convertirGrupo($miles) . ' MIL') . ' ';
                $entero %= 1000;
            }
            if ($entero > 0) {
                $texto .= $convertirGrupo($entero);
            }
            $texto = trim($texto);
        }

        return $texto . ' PESOS ' . str_pad($centavos, 2, '0', STR_PAD_LEFT) . '/100';
    }
}
