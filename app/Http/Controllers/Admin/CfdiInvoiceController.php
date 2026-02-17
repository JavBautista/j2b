<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CfdiEmisor;
use App\Models\CfdiInvoice;
use App\Models\ClientFiscalData;
use App\Models\Receipt;
use App\Services\Facturacion\HubCfdiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CfdiInvoiceController extends Controller
{
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
            // Armar JSON CFDI
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
                $subtotalItem = round($item->subtotal, 2);

                // Si la nota tiene IVA, calcularlo proporcionalmente por concepto
                $ivaItem = 0;
                if ($tieneIva && $receipt->subtotal > 0) {
                    $proporcion = $subtotalItem / $receipt->subtotal;
                    $ivaItem = round($receipt->iva * $proporcion, 2);
                }

                $concepto = [
                    'clave_prod_serv' => '01010101',
                    'descripcion' => $item->descripcion,
                    'cantidad' => $item->qty,
                    'clave_unidad' => 'E48',
                    'valor_unitario' => round($item->price, 2),
                    'subtotal' => $subtotalItem,
                    'importe' => $subtotalItem,
                ];

                if ($tieneIva) {
                    $concepto['objeto_impuesto'] = '02';
                    $concepto['impuestos'] = [
                        'traslados' => [
                            [
                                'base' => $subtotalItem,
                                'impuesto' => '002',
                                'tipo_factor' => 'Tasa',
                                'tasa_cuota' => '0.160000',
                                'importe' => $ivaItem,
                            ]
                        ]
                    ];
                } else {
                    $concepto['objeto_impuesto'] = '01';
                }

                $conceptos[] = $concepto;
                $subtotalTotal += $subtotalItem;
                $ivaTotal += $ivaItem;
            }

            // Ajustar redondeo: asegurar que iva total coincide con receipt.iva
            if ($tieneIva) {
                $diff = round($receipt->iva - $ivaTotal, 2);
                if ($diff != 0 && count($conceptos) > 0) {
                    $lastIdx = count($conceptos) - 1;
                    $conceptos[$lastIdx]['impuestos']['traslados'][0]['importe'] += $diff;
                    $ivaTotal = round($receipt->iva, 2);
                }
            }

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

            // Nodo impuestos global
            if ($tieneIva) {
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
            }

            // Llamar API
            $hubService = new HubCfdiService();
            $result = $hubService->timbrar($cfdiPayload);

            if (!$result['success']) {
                Log::error('CFDI Timbrado fallido', [
                    'shop_id' => $shop->id,
                    'receipt_id' => $receipt->id,
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
            Log::error('CFDI Timbrado exception', [
                'shop_id' => $shop->id,
                'receipt_id' => $receipt->id,
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
     * Descargar factura en formato XML o PDF
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

        try {
            $hubService = new HubCfdiService();
            $result = $hubService->descargar($invoice->uuid, $formato);

            if (!$result['success']) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Error al descargar: ' . ($result['error'] ?? 'Error desconocido'),
                ]);
            }

            $data = $result['data'];

            // La API devuelve el archivo en base64 (campo "archivo")
            $base64 = $data['archivo'] ?? $data['base64'] ?? null;

            if ($base64) {
                $content = base64_decode($base64);
                $contentType = $formato === 'xml' ? 'application/xml' : 'application/pdf';
                $filename = "factura_{$invoice->serie}{$invoice->folio}.{$formato}";

                return response($content)
                    ->header('Content-Type', $contentType)
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
            }

            if (isset($data['url'])) {
                return response()->json(['ok' => true, 'url' => $data['url']]);
            }

            return response()->json(['ok' => false, 'message' => 'No se recibió el archivo de la API']);

        } catch (\Exception $e) {
            Log::error('CFDI Descarga error', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Error al descargar: ' . $e->getMessage(),
            ], 500);
        }
    }
}
