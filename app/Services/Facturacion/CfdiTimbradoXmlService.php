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
 * LIMITACIONES iniciales (cubren el caso típico cedular: arrendamiento PUE):
 *  - PUE únicamente (no PPD por ahora)
 *  - Sin cortesías mezcladas
 *  - Sin descuento global en la factura
 *  - Sin retenciones federales (ISR/IVA del catálogo 001/002) mezcladas
 *  - Receptor con RFC distinto a XAXX010101000 (no público general)
 *
 * Los casos fuera de scope retornan ['ok' => false, 'status' => 422] con
 * mensaje explicativo. El refactor para compartir prorrateo con el pipeline
 * JSON queda como TODO post-validación-sandbox (Sesión 11 del plan).
 */
class CfdiTimbradoXmlService
{
    public function emitir(Receipt $receipt, Shop $shop, CfdiEmisor $emisor, array $data): array
    {
        $fmt = fn($n) => number_format((float) $n, 2, '.', '');
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
            $fechaEmision = Carbon::now('America/Mexico_City')->format('Y-m-d\TH:i:s');
            $receptorRfc = strtoupper($data['receptor_rfc']);

            // ===== Construir conceptos (versión simple, IVA 16% directo) =====
            $taxDecimal = $shop->getTaxDecimal();
            $taxSatRate = $shop->getTaxSatRate();
            $tieneIva = $receipt->iva > 0;
            $taxDivisor = $shop->getTaxDivisor();

            $conceptos = [];
            $subtotalTotal = 0.0;
            $ivaTotal = 0.0;

            foreach ($receipt->detail as $item) {
                if ($item->is_complimentary) continue;

                $valorUnitario = $tieneIva
                    ? round($item->price, 2)
                    : round($item->price / $taxDivisor, 2);
                $importe = round($valorUnitario * $item->qty, 2);
                if ($importe <= 0) continue;

                $base = $importe;
                $ivaItem = round($base * $taxDecimal, 2);

                $satOverride = collect($data['conceptos_sat'] ?? [])->firstWhere('detail_id', $item->id);
                $claveProdServ = $satOverride['clave_prod_serv'] ?? $item->product?->sat_product_code ?? '01010101';
                $claveUnidad = $satOverride['clave_unidad'] ?? $item->product?->sat_unit_code ?? 'E48';
                $descripcionRaw = $satOverride['descripcion'] ?? $item->descripcion;
                $descripcionSat = preg_replace('/\s+/', ' ', trim(str_replace(["\n", "\r", "\t", "|"], [' ', '', ' ', '-'], $descripcionRaw)));

                $conceptos[] = [
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

                $subtotalTotal += $importe;
                $ivaTotal += $ivaItem;
            }

            $subtotalTotal = round($subtotalTotal, 2);
            $ivaTotal = round($ivaTotal, 2);

            // ===== Calcular totales implocal =====
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

            $total = round($subtotalTotal + $ivaTotal - $totalImplocalRet + $totalImplocalTras, 2);

            // ===== Armar input del CfdiXmlBuilder =====
            $xmlInput = [
                'comprobante' => [
                    'serie' => $emisor->serie ?? 'A',
                    'folio' => (string) $folio,
                    'fecha' => $fechaEmision,
                    'forma_pago' => $data['forma_pago'],
                    'metodo_pago' => 'PUE',
                    'subtotal' => $subtotalTotal,
                    'moneda' => $shop->getCurrencyCode(),
                    'total' => $total,
                    'tipo_comprobante' => 'I',
                    'lugar_expedicion' => $emisor->codigo_postal,
                    'exportacion' => '01',
                ],
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
                'impuestos' => [
                    'total_impuestos_trasladados' => $ivaTotal,
                    'traslados' => [[
                        'base' => $subtotalTotal,
                        'impuesto' => '002',
                        'tipo_factor' => 'Tasa',
                        'tasa_cuota' => $taxSatRate,
                        'importe' => $ivaTotal,
                    ]],
                ],
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
            // FechaTimbrado viene embebida en el XML sellado (atributo TimbreFiscalDigital.FechaTimbrado).
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
                'forma_pago' => $data['forma_pago'],
                'metodo_pago' => 'PUE',
                'subtotal' => $subtotalTotal,
                'total_impuestos' => $ivaTotal,
                'total_retenciones' => 0,
                'total_impuestos_locales_retenidos' => $totalImplocalRet,
                'total_impuestos_locales_trasladados' => $totalImplocalTras,
                'total' => $total,
                'status' => 'vigente',
                'pipeline_timbrado' => 'xml_compat',
                'request_json' => $xmlInput,
                'response_json' => $responseData,
            ]);

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

            $receipt->is_tax_invoiced = true;
            $receipt->status = Receipt::STATUS_PAGADA;
            $receipt->save();
            $emisor->increment('timbres_usados');

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
                    'total_impuestos_locales_retenidos' => $totalImplocalRet,
                    'total_impuestos_locales_trasladados' => $totalImplocalTras,
                ],
            ]);

            return [
                'ok' => true,
                'invoice' => $invoice,
                'hub_service' => $hub,
                'conceptos_cortesia_excluidos' => [],
                'metodo_pago' => 'PUE',
                'complementos_iniciales' => [],
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
     * Retorna mensaje de error si el caso está fuera del scope inicial, null si OK.
     */
    private function validarScope(Receipt $receipt, array $data): ?string
    {
        if (empty($data['impuestos_locales']) || !is_array($data['impuestos_locales'])) {
            return 'Esta operación requiere al menos un impuesto local. Si no aplica, usa el timbrado normal.';
        }

        if (strtoupper($data['receptor_rfc'] ?? '') === 'XAXX010101000') {
            return 'Las facturas con impuestos locales no pueden emitirse a Público en General. Captura un receptor con RFC propio.';
        }

        $esPPD = (float) $receipt->received < (float) $receipt->total;
        if ($esPPD) {
            return 'Las facturas con impuestos locales requieren PUE en esta versión. Cobra la totalidad antes de facturar o consulta a soporte.';
        }

        $tieneCortesias = false;
        foreach ($receipt->detail as $item) {
            if ($item->is_complimentary) { $tieneCortesias = true; break; }
        }
        if ($tieneCortesias) {
            return 'Las facturas con impuestos locales no pueden mezclar conceptos de cortesía en esta versión.';
        }

        if (((float) ($receipt->discount ?? 0)) > 0) {
            return 'Las facturas con impuestos locales no soportan descuento global en esta versión.';
        }

        if (!empty($data['ret_isr_aplica']) || !empty($data['ret_iva_aplica'])) {
            return 'Las facturas con impuestos locales no se pueden combinar con retenciones federales (ISR/IVA) en esta versión.';
        }

        // Validar estructura de impuestos_locales
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
