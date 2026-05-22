<?php

namespace App\Services\Facturacion;

use App\Models\CfdiEmisor;
use App\Models\CfdiInvoice;
use App\Models\CfdiInvoiceImpuestoLocal;
use App\Models\ClientFiscalData;
use App\Models\Receipt;
use App\Models\Shop;
use App\Services\Facturacion\Logging\LogFacturacion;
use App\Services\Facturacion\Xml\CfdiXmlBuilder;
use App\Services\Facturacion\Xml\CfdiXmlValidator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Pipeline alterno de timbrado para CFDIs que llevan complemento implocal v1.0.
 *
 * Se invoca SOLO cuando la factura lleva `impuestos_locales` en el payload.
 * El pipeline JSON (CfdiTimbradoService) queda intacto para el 99% de casos
 * sin implocal. Ver invariante en xdev/facturacion/PLAN_IMPUESTOS_LOCALES.md §0.5.1.
 *
 * Paridad funcional con CfdiTimbradoService::emitir() (pipeline JSON):
 *  - Detección PUE/PPD automática
 *  - Filtrado de cortesías (is_complimentary)
 *  - Prorrateo de descuento global con ajuste de centavo
 *  - Retenciones federales (ISR/IVA) por concepto + globales
 *  - Persistencia en cfdi_invoice_taxes (federales) + cfdi_invoice_impuestos_locales
 *  - Hook PPD: emisión de complementos iniciales por abonos previos
 *
 * Único bloqueo de scope que se mantiene: Público en General (XAXX010101000)
 * no admite implocal porque exige informacion_global y cambia el shape del XML.
 */
class CfdiTimbradoXmlService
{
    /**
     * Formato SAT obligatorio para tasas: string con 6 decimales (ej. "0.106667").
     * Mismo helper que el pipeline JSON (CfdiTimbradoService).
     */
    private function fmtTasa(float $tasa): string
    {
        return number_format($tasa, 6, '.', '');
    }

    public function emitir(Receipt $receipt, Shop $shop, CfdiEmisor $emisor, array $data): array
    {
        $startedAt = microtime(true);

        LogFacturacion::implocal('cfdi.implocal.attempt', [
            'shop_id' => $shop->id,
            'receipt_id' => $receipt->id,
            'pipeline' => 'xml_compat',
            'metadata' => [
                'emisor_rfc' => $emisor->rfc,
                'receptor_rfc' => strtoupper($data['receptor_rfc'] ?? ''),
                'impuestos_locales_count' => count($data['impuestos_locales'] ?? []),
            ],
        ]);

        // ===== Validar limitaciones de scope =====
        $errorScope = $this->validarScope($receipt, $data);
        if ($errorScope) {
            LogFacturacion::implocal('cfdi.implocal.error', [
                'shop_id' => $shop->id,
                'receipt_id' => $receipt->id,
                'error_code' => 'scope_limitation',
                'error_message' => $errorScope,
            ], 'error');
            return ['ok' => false, 'message' => $errorScope, 'status' => 422];
        }

        $folio = null;
        try {
            $folio = $emisor->siguienteFolio();
            // Formato ISO con T mayúscula confirmado en sandbox para el pipeline compat.
            $fechaEmision = Carbon::now('America/Mexico_City')->format('Y-m-d\TH:i:s');
            $receptorRfc = strtoupper($data['receptor_rfc']);

            // Detección automática PUE/PPD (regla SAT: PPD si queda saldo pendiente)
            $esPPD = (float) $receipt->received < (float) $receipt->total;
            $metodoPago = $esPPD ? 'PPD' : 'PUE';
            $formaPago = $esPPD ? '99' : $data['forma_pago'];

            // Indexar overrides SAT por detail_id
            $conceptosSatMap = [];
            foreach (($data['conceptos_sat'] ?? []) as $cs) {
                $conceptosSatMap[$cs['detail_id']] = $cs;
            }

            // Config retenciones federales: tasas globales, aplicación por concepto.
            $retIsrAplica = (bool) ($data['ret_isr_aplica'] ?? false);
            $retIsrTasa = (float) ($data['ret_isr_tasa'] ?? 0);
            $retIvaAplica = (bool) ($data['ret_iva_aplica'] ?? false);
            $retIvaTasa = (float) ($data['ret_iva_tasa'] ?? 0);

            // === PASO 1: pre-calcular items facturables con valores brutos sin IVA ===
            $tieneIva = $receipt->iva > 0;
            $taxDecimal = $shop->getTaxDecimal();
            $taxDivisor = $shop->getTaxDivisor();
            $taxSatRate = $shop->getTaxSatRate();
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
            $retIsrTotal = 0;
            $retIvaTotal = 0;
            $conceptosTaxData = [];

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
                    'cantidad' => $item->qty,
                    'clave_unidad' => $claveUnidad,
                    'descripcion' => $descripcionSat,
                    'valor_unitario' => $valorUnitario,
                    'importe' => $importe,
                    'objeto_imp' => '02',
                    'traslados' => [[
                        'base' => $base,
                        'impuesto' => '002',
                        'tipo_factor' => 'Tasa',
                        'tasa_cuota' => $taxSatRate,
                        'importe' => $ivaItem,
                    ]],
                ];

                if ($descuentoConcepto > 0) {
                    $concepto['descuento'] = $descuentoConcepto;
                }

                // Retenciones por concepto: solo cuando el usuario marcó este concepto.
                $aplicaRetConcepto = (bool) ($satOverride['aplica_retencion'] ?? false);
                $retConceptoIsr = 0;
                $retConceptoIva = 0;
                $retencionesNodes = [];

                if ($aplicaRetConcepto) {
                    if ($retIsrAplica && $retIsrTasa > 0) {
                        $r = round($base * $retIsrTasa, 2);
                        // SAT rechaza retenciones con importe 0 — omitir si redondeo cae bajo 1¢.
                        if ($r >= 0.01) {
                            $retConceptoIsr = $r;
                            $retencionesNodes[] = [
                                'base' => $base,
                                'impuesto' => '001',
                                'tipo_factor' => 'Tasa',
                                'tasa_cuota' => $this->fmtTasa($retIsrTasa),
                                'importe' => $r,
                            ];
                        }
                    }
                    // IVA retenido solo cuando hay IVA trasladado (si IVA traslado=0 → SAT rechaza).
                    if ($retIvaAplica && $retIvaTasa > 0 && $ivaItem > 0) {
                        $r = round($base * $retIvaTasa, 2);
                        if ($r >= 0.01) {
                            $retConceptoIva = $r;
                            $retencionesNodes[] = [
                                'base' => $base,
                                'impuesto' => '002',
                                'tipo_factor' => 'Tasa',
                                'tasa_cuota' => $this->fmtTasa($retIvaTasa),
                                'importe' => $r,
                            ];
                        }
                    }
                }

                if (!empty($retencionesNodes)) {
                    $concepto['retenciones'] = $retencionesNodes;
                }

                $conceptosTaxData[] = [
                    'index' => count($conceptos),
                    'base' => $base,
                    'iva' => $ivaItem,
                    'ret_isr' => $retConceptoIsr,
                    'ret_iva' => $retConceptoIva,
                ];

                $conceptos[] = $concepto;
                $subtotalTotal += $importe;
                $descuentoTotal += $descuentoConcepto;
                $ivaTotal += $ivaItem;
                $retIsrTotal += $retConceptoIsr;
                $retIvaTotal += $retConceptoIva;
            }

            $subtotalTotal = round($subtotalTotal, 2);
            $descuentoTotal = round($descuentoTotal, 2);
            $ivaTotal = round($ivaTotal, 2);
            $retIsrTotal = round($retIsrTotal, 2);
            $retIvaTotal = round($retIvaTotal, 2);
            $retTotal = round($retIsrTotal + $retIvaTotal, 2);
            $baseIvaGlobal = round($subtotalTotal - $descuentoTotal, 2);

            // ===== Totales implocal =====
            $totalImplocalRet = 0.0;
            $totalImplocalTras = 0.0;
            foreach ($data['impuestos_locales'] as $imp) {
                if (($imp['tipo'] ?? '') === 'retencion') {
                    $totalImplocalRet += (float) $imp['importe'];
                } else {
                    $totalImplocalTras += (float) $imp['importe'];
                }
            }
            $totalImplocalRet = round($totalImplocalRet, 2);
            $totalImplocalTras = round($totalImplocalTras, 2);

            $total = round($baseIvaGlobal + $ivaTotal - $retTotal - $totalImplocalRet + $totalImplocalTras, 2);

            // ===== Armar input del CfdiXmlBuilder =====
            $comprobante = [
                'serie' => $emisor->serie ?? 'A',
                'folio' => (string) $folio,
                'fecha' => $fechaEmision,
                'forma_pago' => $formaPago,
                'metodo_pago' => $metodoPago,
                'subtotal' => $subtotalTotal,
                'moneda' => $shop->getCurrencyCode(),
                'total' => $total,
                'tipo_comprobante' => 'I',
                'lugar_expedicion' => $emisor->codigo_postal,
                'exportacion' => '01',
            ];
            if ($descuentoTotal > 0) {
                $comprobante['descuento'] = $descuentoTotal;
            }

            $impuestosGlobal = [
                'total_impuestos_trasladados' => $ivaTotal,
                'traslados' => [[
                    'base' => $baseIvaGlobal,
                    'impuesto' => '002',
                    'tipo_factor' => 'Tasa',
                    'tasa_cuota' => $taxSatRate,
                    'importe' => $ivaTotal,
                ]],
            ];
            if ($retTotal > 0) {
                $impuestosGlobal['total_impuestos_retenidos'] = $retTotal;
                $globalRet = [];
                if ($retIsrTotal >= 0.01) {
                    $globalRet[] = ['impuesto' => '001', 'importe' => $retIsrTotal];
                }
                if ($retIvaTotal >= 0.01) {
                    $globalRet[] = ['impuesto' => '002', 'importe' => $retIvaTotal];
                }
                $impuestosGlobal['retenciones'] = $globalRet;
            }

            $xmlInput = [
                'comprobante' => $comprobante,
                'emisor' => [
                    'rfc' => $emisor->rfc,
                    'nombre' => $emisor->razon_social,
                    'regimen_fiscal' => $emisor->regimen_fiscal,
                ],
                'receptor' => [
                    'rfc' => $receptorRfc,
                    'nombre' => strtoupper($data['receptor_razon_social']),
                    'domicilio_fiscal' => $data['receptor_codigo_postal'],
                    'regimen_fiscal' => $data['receptor_regimen_fiscal'],
                    'uso_cfdi' => $data['receptor_uso_cfdi'],
                ],
                'conceptos' => $conceptos,
                'impuestos' => $impuestosGlobal,
                'impuestos_locales' => $data['impuestos_locales'],
            ];

            // ===== Construir XML =====
            $builder = new CfdiXmlBuilder();
            $doc = $builder->build($xmlInput);

            // ===== Validar antes de quemar timbre =====
            $validator = new CfdiXmlValidator();
            $vResult = $validator->validate($doc);
            if (!$vResult['valid']) {
                $emisor->revertirFolio();
                $errMsg = 'XML inválido pre-envío: ' . implode('; ', $vResult['errors']);
                LogFacturacion::implocal('cfdi.implocal.error', [
                    'shop_id' => $shop->id,
                    'receipt_id' => $receipt->id,
                    'error_code' => 'xml_validation',
                    'error_message' => $errMsg,
                ], 'error');
                return ['ok' => false, 'message' => $errMsg, 'status' => 422];
            }

            $xml = $doc->saveXML();

            // ===== Enviar a TBT vía endpoint compat =====
            $hub = app(HubCfdiService::class);
            $result = $hub->timbrarCompat($xml);

            if (!$result['success']) {
                $emisor->revertirFolio();
                LogFacturacion::implocal('cfdi.implocal.error', [
                    'shop_id' => $shop->id,
                    'receipt_id' => $receipt->id,
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    'error_message' => is_string($result['error']) ? $result['error'] : json_encode($result['error']),
                    'metadata' => ['folio_revertido' => $folio],
                ], 'error');
                return [
                    'ok' => false,
                    'message' => 'Error al timbrar (XML compat): ' . ($result['error'] ?? 'Error desconocido'),
                    'status' => 422,
                ];
            }

            $responseData = $result['data'];
            // TBT compat devuelve UUID en data.Valores.UUID (validado sandbox 2026-05-18)
            $uuid = $responseData['Valores']['UUID']
                ?? $responseData['uuid']
                ?? $responseData['Uuid']
                ?? null;
            // FechaTimbrado viene embebida en el XML sellado.
            $fechaTimbrado = null;
            if (!empty($responseData['Xml'])) {
                if (preg_match('/FechaTimbrado="([^"]+)"/', $responseData['Xml'], $m)) {
                    $fechaTimbrado = str_replace('T', ' ', $m[1]);
                }
            }

            // ===== Persistir CfdiInvoice =====
            $clientFiscalDataId = $this->resolverClientFiscalData($receipt, $receptorRfc, $data);

            $invoice = CfdiInvoice::create([
                'shop_id' => $shop->id,
                'cfdi_emisor_id' => $emisor->id,
                'receipt_id' => $receipt->id,
                'client_fiscal_data_id' => $clientFiscalDataId,
                'receptor_rfc' => $receptorRfc,
                'receptor_nombre' => strtoupper($data['receptor_razon_social']),
                'receptor_regimen' => $data['receptor_regimen_fiscal'],
                'receptor_cp' => $data['receptor_codigo_postal'],
                'receptor_uso_cfdi' => $data['receptor_uso_cfdi'],
                'uuid' => $uuid,
                'serie' => $emisor->serie ?? 'A',
                'folio' => (string) $folio,
                'fecha_emision' => $fechaEmision,
                'fecha_timbrado' => $fechaTimbrado ?? ($responseData['fecha_timbrado'] ?? null),
                'tipo_comprobante' => 'I',
                'forma_pago' => $formaPago,
                'metodo_pago' => $metodoPago,
                'subtotal' => $subtotalTotal,
                'total_impuestos' => $ivaTotal,
                'total_retenciones' => $retTotal,
                'total_impuestos_locales_retenidos' => $totalImplocalRet,
                'total_impuestos_locales_trasladados' => $totalImplocalTras,
                'total' => $total,
                'status' => 'vigente',
                'pipeline_timbrado' => 'xml_compat',
                'request_json' => $xmlInput,
                'response_json' => $responseData,
            ]);

            // ===== Persistir cfdi_invoice_taxes (federales) =====
            // Source of truth para complementos PPD y reportes.
            // Falla aquí NO revierte el timbre: el CFDI ya está vigente en TBT.
            try {
                foreach ($conceptosTaxData as $cm) {
                    if ($cm['iva'] > 0) {
                        $invoice->taxes()->create([
                            'concepto_index' => $cm['index'],
                            'tipo' => 'traslado',
                            'impuesto' => '002',
                            'tipo_factor' => 'Tasa',
                            'tasa' => $taxDecimal,
                            'base' => $cm['base'],
                            'importe' => $cm['iva'],
                        ]);
                    }
                    if ($cm['ret_isr'] > 0) {
                        $invoice->taxes()->create([
                            'concepto_index' => $cm['index'],
                            'tipo' => 'retencion',
                            'impuesto' => '001',
                            'tipo_factor' => 'Tasa',
                            'tasa' => $retIsrTasa,
                            'base' => $cm['base'],
                            'importe' => $cm['ret_isr'],
                        ]);
                    }
                    if ($cm['ret_iva'] > 0) {
                        $invoice->taxes()->create([
                            'concepto_index' => $cm['index'],
                            'tipo' => 'retencion',
                            'impuesto' => '002',
                            'tipo_factor' => 'Tasa',
                            'tasa' => $retIvaTasa,
                            'base' => $cm['base'],
                            'importe' => $cm['ret_iva'],
                        ]);
                    }
                }

                if ($ivaTotal > 0) {
                    $invoice->taxes()->create([
                        'concepto_index' => null,
                        'tipo' => 'traslado',
                        'impuesto' => '002',
                        'tipo_factor' => 'Tasa',
                        'tasa' => $taxDecimal,
                        'base' => $baseIvaGlobal,
                        'importe' => $ivaTotal,
                    ]);
                }
                if ($retIsrTotal >= 0.01) {
                    $invoice->taxes()->create([
                        'concepto_index' => null,
                        'tipo' => 'retencion',
                        'impuesto' => '001',
                        'importe' => $retIsrTotal,
                    ]);
                }
                if ($retIvaTotal >= 0.01) {
                    $invoice->taxes()->create([
                        'concepto_index' => null,
                        'tipo' => 'retencion',
                        'impuesto' => '002',
                        'importe' => $retIvaTotal,
                    ]);
                }
            } catch (\Exception $e) {
                LogFacturacion::implocal('cfdi.implocal.taxes_warning', [
                    'shop_id' => $shop->id,
                    'cfdi_invoice_id' => $invoice->id,
                    'uuid' => $uuid,
                    'error_message' => $e->getMessage(),
                ], 'warning');
            }

            // ===== Persistir impuestos locales =====
            try {
                foreach ($data['impuestos_locales'] as $imp) {
                    CfdiInvoiceImpuestoLocal::create([
                        'cfdi_invoice_id' => $invoice->id,
                        'tipo' => $imp['tipo'],
                        'nombre' => $imp['nombre'],
                        'tasa_porcentaje' => $imp['tasa_porcentaje'],
                        'base' => $imp['base'],
                        'importe' => $imp['importe'],
                    ]);
                }
            } catch (\Exception $e) {
                LogFacturacion::implocal('cfdi.implocal.persistencia_warning', [
                    'shop_id' => $shop->id,
                    'cfdi_invoice_id' => $invoice->id,
                    'uuid' => $uuid,
                    'error_message' => $e->getMessage(),
                ], 'warning');
            }

            // ===== Actualizar receipt y emisor =====
            // PUE marca PAGADA. PPD mantiene status actual (POR COBRAR / POR FACTURAR)
            // porque queda saldo insoluto que se liquida con complementos.
            $receipt->is_tax_invoiced = true;
            if (!$esPPD) {
                $receipt->status = Receipt::STATUS_PAGADA;
            }
            $receipt->save();
            $emisor->increment('timbres_usados');

            // ===== Hook PPD: emitir complementos iniciales por abonos previos =====
            // Mismo patrón que el pipeline JSON. Los complementos NO llevan implocal
            // (regla SAT confirmada por TBT 2026-05-18 punto 7), solo retenciones
            // federales proporcionales — CfdiComplementoPagoService ya lo soporta.
            $complementosIniciales = [];
            if ($esPPD && (float) $receipt->received > 0) {
                $complementoService = new CfdiComplementoPagoService();
                $abonos = $receipt->partialPayments()->where('amount', '>', 0)->orderBy('id')->get();
                $abonosPrevios = $data['abonos_previos'] ?? null;
                $estrategia = $abonosPrevios['estrategia'] ?? 'separar';

                if ($estrategia === 'consolidar' && !empty($abonosPrevios['consolidado'])) {
                    $resp = $complementoService->emitirConsolidado(
                        $receipt,
                        $abonos,
                        $abonosPrevios['consolidado'],
                        1
                    );
                    $complementosIniciales[] = $resp;
                    if (!$resp['ok']) {
                        LogFacturacion::complementoPago('cfdi.complemento_pago.initial_consolidado_warning', [
                            'shop_id' => $shop->id,
                            'receipt_id' => $receipt->id,
                            'cfdi_invoice_id' => $invoice->id,
                            'error_message' => $resp['message'],
                            'metadata' => [
                                'consolidated_ids' => $abonos->pluck('id')->all(),
                            ],
                        ], 'warning');
                    }
                } else {
                    $asignaciones = collect($abonosPrevios['asignaciones'] ?? [])
                        ->keyBy('partial_payment_id');

                    foreach ($abonos as $idx => $abono) {
                        $asig = $asignaciones->get($abono->id);
                        if ($asig) {
                            $abono->payment_method = $asig['payment_method'] ?? $abono->payment_method;
                            $abono->shop_bank_account_id = $asig['shop_bank_account_id'] ?? null;
                            $abono->bank_ord_code = $asig['bank_ord_code'] ?? null;
                            $abono->cta_ordenante = !empty($asig['cta_ordenante']) ? strtoupper($asig['cta_ordenante']) : null;
                            $abono->is_foreign_bank_ord = (bool) ($asig['is_foreign_bank_ord'] ?? false);
                            $abono->num_operacion = $asig['num_operacion'] ?? null;
                            $abono->save();
                        }

                        $resp = $complementoService->emitir($receipt, $abono, $idx + 1);
                        $complementosIniciales[] = $resp;
                        if (!$resp['ok']) {
                            LogFacturacion::complementoPago('cfdi.complemento_pago.initial_warning', [
                                'shop_id' => $shop->id,
                                'receipt_id' => $receipt->id,
                                'cfdi_invoice_id' => $invoice->id,
                                'error_message' => $resp['message'],
                                'metadata' => [
                                    'partial_payment_id' => $abono->id,
                                ],
                            ], 'warning');
                        }
                    }
                }
            }

            LogFacturacion::implocal('cfdi.implocal.success', [
                'shop_id' => $shop->id,
                'cfdi_invoice_id' => $invoice->id,
                'receipt_id' => $receipt->id,
                'uuid' => $uuid,
                'pipeline' => 'xml_compat',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'http_status' => 200,
                'metadata' => [
                    'emisor_rfc' => $emisor->rfc,
                    'receptor_rfc' => $receptorRfc,
                    'total' => $total,
                    'metodo_pago' => $metodoPago,
                    'total_retenciones_federales' => $retTotal,
                    'total_impuestos_locales_retenidos' => $totalImplocalRet,
                    'total_impuestos_locales_trasladados' => $totalImplocalTras,
                ],
            ]);

            return [
                'ok' => true,
                'invoice' => $invoice,
                'hub_service' => $hub,
                'conceptos_cortesia_excluidos' => $conceptosCortesia,
                'metodo_pago' => $metodoPago,
                'complementos_iniciales' => $complementosIniciales,
            ];

        } catch (\Exception $e) {
            if ($folio !== null) {
                $emisor->revertirFolio();
            }
            LogFacturacion::implocal('cfdi.implocal.error', [
                'shop_id' => $shop->id,
                'receipt_id' => $receipt->id,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error_code' => 'exception',
                'error_message' => $e->getMessage(),
                'metadata' => ['folio_revertido' => $folio],
            ], 'error');
            return [
                'ok' => false,
                'message' => 'Error al timbrar (XML compat): ' . $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Validación de scope reducida. Solo bloquea:
     *  - Sin impuestos_locales (no debería llegar aquí)
     *  - Receptor Público en General (regla SAT: exige informacion_global incompatible con implocal)
     *  - Estructura inválida de impuestos_locales
     *
     * Las antiguas limitaciones "v1" (PUE only, sin cortesías, sin descuento global,
     * sin retenciones federales) fueron levantadas el 2026-05-20.
     */
    private function validarScope(Receipt $receipt, array $data): ?string
    {
        if (empty($data['impuestos_locales']) || !is_array($data['impuestos_locales'])) {
            return 'Esta operación requiere al menos un impuesto local. Si no aplica, usa el timbrado normal.';
        }

        if (strtoupper($data['receptor_rfc'] ?? '') === 'XAXX010101000') {
            return 'Las facturas con impuestos locales no pueden emitirse a Público en General. Captura un receptor con RFC propio.';
        }

        // Validar estructura de cada impuesto local
        foreach ($data['impuestos_locales'] as $i => $imp) {
            foreach (['tipo', 'nombre', 'tasa_porcentaje', 'base', 'importe'] as $k) {
                if (!array_key_exists($k, $imp)) {
                    return "impuestos_locales[{$i}]: falta campo '{$k}'";
                }
            }
            if (!in_array($imp['tipo'], ['retencion', 'traslado'], true)) {
                return "impuestos_locales[{$i}]: tipo debe ser 'retencion' o 'traslado'";
            }
            $len = mb_strlen((string) $imp['nombre']);
            if ($len < 3 || $len > 100) {
                return "impuestos_locales[{$i}]: nombre debe tener 3-100 caracteres";
            }
        }

        return null;
    }

    private function resolverClientFiscalData(Receipt $receipt, string $rfc, array $data): ?int
    {
        if (!$receipt->client_id) return null;

        if (!empty($data['client_fiscal_data_id'])) {
            $found = ClientFiscalData::where('id', $data['client_fiscal_data_id'])
                ->where('client_id', $receipt->client_id)
                ->first();
            if ($found) return $found->id;
        }

        $existing = ClientFiscalData::where('client_id', $receipt->client_id)
            ->where('rfc', $rfc)
            ->where('active', true)
            ->first();
        if ($existing) return $existing->id;

        if (!empty($data['guardar_datos_cliente'])) {
            $hasAny = ClientFiscalData::where('client_id', $receipt->client_id)->exists();
            $nuevo = ClientFiscalData::create([
                'client_id' => $receipt->client_id,
                'rfc' => $rfc,
                'razon_social' => strtoupper($data['receptor_razon_social']),
                'regimen_fiscal' => $data['receptor_regimen_fiscal'],
                'uso_cfdi' => $data['receptor_uso_cfdi'],
                'codigo_postal' => $data['receptor_codigo_postal'],
                'is_default' => !$hasAny,
                'active' => true,
            ]);
            return $nuevo->id;
        }

        return null;
    }
}
