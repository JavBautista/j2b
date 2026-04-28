<?php

namespace App\Services\Facturacion;

use App\Models\CfdiEmisor;
use App\Models\CfdiInvoice;
use App\Models\ClientFiscalData;
use App\Models\Receipt;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Encapsula el flujo completo de emisión CFDI (prorrateo + payload + HUB + BD).
 * Reutilizable desde el controller Admin (web) y el controller API (Ionic).
 *
 * Retorno estándar:
 *   ['ok' => true,  'invoice' => CfdiInvoice, 'conceptos_cortesia_excluidos' => string[]]
 *   ['ok' => false, 'message' => string, 'status' => int]
 */
class CfdiTimbradoService
{
    /**
     * @param Receipt     $receipt  Con detail.product precargado
     * @param Shop        $shop
     * @param CfdiEmisor  $emisor
     * @param array       $data     receptor_rfc, receptor_razon_social, receptor_regimen_fiscal,
     *                              receptor_uso_cfdi, receptor_codigo_postal, forma_pago,
     *                              metodo_pago, conceptos_sat (opt), guardar_datos_cliente (opt)
     */
    public function emitir(Receipt $receipt, Shop $shop, CfdiEmisor $emisor, array $data): array
    {
        // El PAC valida que todos los campos monetarios tengan los mismos decimales.
        // round() devuelve float y PHP trunca ceros al serializar (469.90 -> 469.9).
        // fmt() fuerza string "0.00" para todos los valores que van al payload del PAC.
        $fmt = fn($n) => number_format((float) $n, 2, '.', '');

        $folio = null;
        try {
            $folio = $emisor->siguienteFolio();
            $fechaEmision = Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s');

            $receptorRfc = strtoupper($data['receptor_rfc']);
            $esPublicoGeneral = ($receptorRfc === 'XAXX010101000');

            // Detección automática PUE/PPD (regla SAT: PPD si queda saldo pendiente)
            $esPPD = (float) $receipt->received < (float) $receipt->total;
            $metodoPago = $esPPD ? 'PPD' : 'PUE';
            $formaPago = $esPPD ? '99' : $data['forma_pago'];

            // Indexar overrides SAT por detail_id
            $conceptosSatMap = [];
            foreach (($data['conceptos_sat'] ?? []) as $cs) {
                $conceptosSatMap[$cs['detail_id']] = $cs;
            }

            // === PASO 1: pre-calcular items facturables con valores brutos sin IVA ===
            $tieneIva = $receipt->iva > 0;
            $taxDecimal = $shop->getTaxDecimal();
            $taxDivisor = $shop->getTaxDivisor();
            $conceptosCortesia = [];
            $facturables = [];

            foreach ($receipt->detail as $item) {
                if ($item->is_complimentary) {
                    $conceptosCortesia[] = $item->descripcion;
                    continue;
                }

                $valorUnitario = $tieneIva
                    ? round($item->price, 2)
                    : round($item->price / $taxDivisor, 2);
                $importeBruto = round($valorUnitario * $item->qty, 2);

                if ($importeBruto <= 0) continue;

                $facturables[] = [
                    'item' => $item,
                    'valor_unitario' => $valorUnitario,
                    'importe_bruto' => $importeBruto,
                ];
            }

            // === PASO 2: calcular descuento global en misma unidad (sin IVA) ===
            $subtotalBruto = array_sum(array_column($facturables, 'importe_bruto'));
            $descuentoGlobalMonto = 0.0;

            if ($receipt->discount > 0 && $subtotalBruto > 0) {
                $descuentoRaw = ($receipt->discount_concept === '%')
                    ? $receipt->subtotal * $receipt->discount / 100
                    : $receipt->discount;
                $descuentoGlobalMonto = $tieneIva
                    ? round($descuentoRaw, 2)
                    : round($descuentoRaw / $taxDivisor, 2);
            }

            // === PASO 3: prorratear ===
            $factor = $descuentoGlobalMonto > 0 ? $descuentoGlobalMonto / $subtotalBruto : 0;
            foreach ($facturables as $idx => &$ef) {
                $ef['descuento'] = $factor > 0 ? round($ef['importe_bruto'] * $factor, 2) : 0;
            }
            unset($ef);

            // === PASO 4: ajuste de redondeo al último concepto ===
            if ($factor > 0 && count($facturables) > 0) {
                $sumaDescuentos = array_sum(array_column($facturables, 'descuento'));
                $diferencia = round($descuentoGlobalMonto - $sumaDescuentos, 2);
                if (abs($diferencia) >= 0.01) {
                    $lastIdx = count($facturables) - 1;
                    $facturables[$lastIdx]['descuento'] = round($facturables[$lastIdx]['descuento'] + $diferencia, 2);
                }
            }

            // === PASO 5: construir conceptos ===
            $conceptos = [];
            $subtotalTotal = 0;
            $descuentoTotal = 0;
            $ivaTotal = 0;

            foreach ($facturables as $ef) {
                $item = $ef['item'];
                $valorUnitario = $ef['valor_unitario'];
                $importe = $ef['importe_bruto'];
                $descuentoConcepto = $ef['descuento'];
                $base = round($importe - $descuentoConcepto, 2);
                $ivaItem = round($base * $taxDecimal, 2);

                $satOverride = $conceptosSatMap[$item->id] ?? null;
                $claveProdServ = $satOverride['clave_prod_serv'] ?? $item->product?->sat_product_code ?? '01010101';
                $claveUnidad = $satOverride['clave_unidad'] ?? $item->product?->sat_unit_code ?? 'E48';

                $descripcionRaw = $satOverride['descripcion'] ?? $item->descripcion;
                $descripcionSat = str_replace(["\n", "\r", "\t", "|"], [' ', '', ' ', '-'], $descripcionRaw);
                $descripcionSat = preg_replace('/\s+/', ' ', trim($descripcionSat));

                $concepto = [
                    'clave_prod_serv' => $claveProdServ,
                    'descripcion' => $descripcionSat,
                    'cantidad' => $item->qty,
                    'clave_unidad' => $claveUnidad,
                    'valor_unitario' => $fmt($valorUnitario),
                    'subtotal' => $fmt($importe),
                    'importe' => $fmt($importe),
                    'objeto_impuesto' => '02',
                    'impuestos' => [
                        'traslados' => [[
                            'base' => $fmt($base),
                            'impuesto' => '002',
                            'tipo_factor' => 'Tasa',
                            'tasa_cuota' => $shop->getTaxSatRate(),
                            'importe' => $fmt($ivaItem),
                        ]],
                    ],
                ];

                if ($descuentoConcepto > 0) {
                    $concepto['descuento'] = $fmt($descuentoConcepto);
                }

                $conceptos[] = $concepto;
                $subtotalTotal += $importe;
                $descuentoTotal += $descuentoConcepto;
                $ivaTotal += $ivaItem;
            }

            $subtotalTotal = round($subtotalTotal, 2);
            $descuentoTotal = round($descuentoTotal, 2);
            $ivaTotal = round($ivaTotal, 2);
            $baseIvaGlobal = round($subtotalTotal - $descuentoTotal, 2);
            $total = round($baseIvaGlobal + $ivaTotal, 2);

            // === Construir payload CFDI ===
            $cfdiPayload = [
                'serie' => $emisor->serie ?? 'A',
                'folio' => (string) $folio,
                'fecha_emision' => $fechaEmision,
                'forma_pago' => $formaPago,
                'metodo_pago' => $metodoPago,
                'tipo_comprobante' => 'I',
                'exportacion' => '01',
                'moneda' => $shop->getCurrencyCode(),
                'lugar_expedicion' => $emisor->codigo_postal,
                'subtotal' => $fmt($subtotalTotal),
                'total' => $fmt($total),
                'emisor' => [
                    'rfc' => $emisor->rfc,
                    'razon_social' => $emisor->razon_social,
                    'regimen_fiscal' => $emisor->regimen_fiscal,
                ],
                'receptor' => [
                    'rfc' => $receptorRfc,
                    'razon_social' => strtoupper($data['receptor_razon_social']),
                    'uso_cfdi' => $data['receptor_uso_cfdi'],
                    'regimen_fiscal' => $data['receptor_regimen_fiscal'],
                    'codigo_postal' => $data['receptor_codigo_postal'],
                ],
                'conceptos' => $conceptos,
            ];

            if ($descuentoTotal > 0) {
                $cfdiPayload['descuento'] = $fmt($descuentoTotal);
            }

            if ($esPublicoGeneral) {
                $now = Carbon::now('America/Mexico_City');
                $cfdiPayload['informacion_global'] = [
                    'periodicidad' => '04',
                    'meses' => str_pad($now->month, 2, '0', STR_PAD_LEFT),
                    'anio' => (string) $now->year,
                ];
            }

            $cfdiPayload['impuestos'] = [
                'total_impuestos_trasladados' => $fmt($ivaTotal),
                'traslados' => [[
                    'base' => $fmt($baseIvaGlobal),
                    'impuesto' => '002',
                    'tipo_factor' => 'Tasa',
                    'tasa_cuota' => $shop->getTaxSatRate(),
                    'importe' => $fmt($ivaTotal),
                ]],
            ];

            // === Llamar API PAC ===
            $hubService = new HubCfdiService();
            $result = $hubService->timbrar($cfdiPayload);

            if (!$result['success']) {
                $emisor->revertirFolio();
                Log::error('CFDI Timbrado fallido', [
                    'shop_id' => $shop->id,
                    'receipt_id' => $receipt->id,
                    'folio_revertido' => $folio,
                    'error' => $result['error'],
                ]);
                return [
                    'ok' => false,
                    'message' => 'Error al timbrar: ' . ($result['error'] ?? 'Error desconocido'),
                    'status' => 422,
                ];
            }

            $responseData = $result['data'];
            $uuid = $responseData['uuid'] ?? null;

            // === Crear registro de factura ===
            $invoice = CfdiInvoice::create([
                'shop_id' => $shop->id,
                'cfdi_emisor_id' => $emisor->id,
                'receipt_id' => $receipt->id,
                'receptor_rfc' => $receptorRfc,
                'receptor_nombre' => strtoupper($data['receptor_razon_social']),
                'receptor_regimen' => $data['receptor_regimen_fiscal'],
                'receptor_cp' => $data['receptor_codigo_postal'],
                'receptor_uso_cfdi' => $data['receptor_uso_cfdi'],
                'uuid' => $uuid,
                'serie' => $emisor->serie ?? 'A',
                'folio' => (string) $folio,
                'fecha_emision' => $fechaEmision,
                'fecha_timbrado' => $responseData['fecha_timbrado'] ?? null,
                'tipo_comprobante' => 'I',
                'forma_pago' => $formaPago,
                'metodo_pago' => $metodoPago,
                'subtotal' => $subtotalTotal,
                'total_impuestos' => $ivaTotal,
                'total' => $total,
                'status' => 'vigente',
                'request_json' => $cfdiPayload,
                'response_json' => $responseData,
            ]);

            // === Actualizar receipt y emisor ===
            // PUE marca PAGADA. PPD mantiene el status actual (POR COBRAR / POR FACTURAR)
            // porque queda saldo insoluto que se irá liquidando con complementos de pago.
            $receipt->is_tax_invoiced = true;
            if (!$esPPD) {
                $receipt->status = Receipt::STATUS_PAGADA;
            }
            $receipt->save();
            $emisor->increment('timbres_usados');

            // === PPD: emitir complementos iniciales por abonos previos ===
            // Si la nota tenía partial_payments antes de timbrar, hay que emitir un
            // complemento por cada uno. Si alguno falla queda como 'failed' y se
            // puede re-emitir desde la UI; NO se revierte la factura tipo I.
            $complementosIniciales = [];
            if ($esPPD && (float) $receipt->received > 0) {
                $complementoService = new CfdiComplementoPagoService();
                $abonos = $receipt->partialPayments()->where('amount', '>', 0)->orderBy('id')->get();
                foreach ($abonos as $idx => $abono) {
                    $resp = $complementoService->emitir($receipt, $abono, $idx + 1);
                    $complementosIniciales[] = $resp;
                    if (!$resp['ok']) {
                        Log::warning('Complemento inicial PPD falló', [
                            'shop_id' => $shop->id,
                            'receipt_id' => $receipt->id,
                            'partial_payment_id' => $abono->id,
                            'error' => $resp['message'],
                        ]);
                    }
                }
            }

            // === Guardar datos fiscales del cliente si se solicitó ===
            if (!empty($data['guardar_datos_cliente']) && $receipt->client_id && !$esPublicoGeneral) {
                $existing = ClientFiscalData::where('client_id', $receipt->client_id)
                    ->where('rfc', $receptorRfc)
                    ->first();
                if (!$existing) {
                    $hasAny = ClientFiscalData::where('client_id', $receipt->client_id)->exists();
                    ClientFiscalData::create([
                        'client_id' => $receipt->client_id,
                        'rfc' => $receptorRfc,
                        'razon_social' => strtoupper($data['receptor_razon_social']),
                        'regimen_fiscal' => $data['receptor_regimen_fiscal'],
                        'uso_cfdi' => $data['receptor_uso_cfdi'],
                        'codigo_postal' => $data['receptor_codigo_postal'],
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

            return [
                'ok' => true,
                'invoice' => $invoice,
                'hub_service' => $hubService,
                'conceptos_cortesia_excluidos' => $conceptosCortesia,
                'metodo_pago' => $metodoPago,
                'complementos_iniciales' => $complementosIniciales,
            ];
        } catch (\Exception $e) {
            if ($folio !== null) {
                $emisor->revertirFolio();
            }
            Log::error('CFDI Timbrado exception', [
                'shop_id' => $shop->id,
                'receipt_id' => $receipt->id,
                'folio_revertido' => $folio,
                'error' => $e->getMessage(),
            ]);
            return [
                'ok' => false,
                'message' => 'Error al timbrar: ' . $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Guarda XML (descargado del PAC) y PDF (generado localmente) en storage/cfdi.
     * Se invoca post-timbrado. El PDF es opcional: se genera solo si $pdfGenerator != null.
     *
     * @param callable|null $pdfGenerator  fn(CfdiInvoice): string  — retorna binario PDF
     */
    public function guardarArchivosLocales(
        HubCfdiService $hubService,
        CfdiInvoice $invoice,
        int $shopId,
        ?callable $pdfGenerator = null
    ): void {
        // 1) XML (oficial, descargado del PAC)
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
                Log::warning('CFDI: No se pudo descargar XML post-timbrado', [
                    'invoice_id' => $invoice->id,
                    'error' => $result['error'],
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('CFDI: Excepción al guardar XML localmente', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 2) PDF (opcional, requiere generador inyectado)
        if ($pdfGenerator !== null) {
            try {
                $pdfContent = $pdfGenerator($invoice);
                $path = "{$shopId}/{$invoice->uuid}.pdf";
                Storage::disk('cfdi')->put($path, $pdfContent);
                $invoice->pdf_path = $path;
            } catch (\Exception $e) {
                Log::warning('CFDI: Excepción al generar PDF localmente', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($invoice->isDirty(['xml_path', 'pdf_path'])) {
            $invoice->save();
        }
    }
}
