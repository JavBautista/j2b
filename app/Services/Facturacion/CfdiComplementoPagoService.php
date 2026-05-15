<?php

namespace App\Services\Facturacion;

use App\Models\CfdiEmisor;
use App\Models\CfdiInvoice;
use App\Models\CfdiPagoComplemento;
use App\Models\PartialPayments;
use App\Models\Receipt;
use App\Models\ShopBankAccount;
use App\Support\SatCatalogos\Bancos;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Encapsula la emisión de Complementos de Pago (CFDI tipo P) contra una factura
 * PPD ya timbrada. Reutilizable desde el flujo automático en storePartialPayment
 * (web + Ionic) y desde el botón "Re-emitir pendiente" de la UI.
 *
 * Retorno estándar:
 *   ['ok' => true,  'complemento' => CfdiPagoComplemento, 'message' => 'OK']
 *   ['ok' => false, 'complemento' => CfdiPagoComplemento|null, 'message' => string]
 */
class CfdiComplementoPagoService
{
    /**
     * Emitir un complemento de pago contra la factura PPD del receipt.
     *
     * @param Receipt          $receipt        Nota con cfdiInvoice cargado
     * @param PartialPayments  $abono          El partial_payment que dispara el complemento
     * @param int              $numParcialidad Número de parcialidad (1 = primer abono)
     */
    public function emitir(Receipt $receipt, PartialPayments $abono, int $numParcialidad): array
    {
        $invoice = $receipt->cfdiInvoice;

        if (!$invoice || $invoice->tipo_comprobante !== 'I' || $invoice->metodo_pago !== 'PPD' || $invoice->status !== 'vigente') {
            return [
                'ok' => false,
                'complemento' => null,
                'message' => 'La nota no tiene una factura PPD vigente.',
            ];
        }

        $shop = $receipt->shop;
        $emisor = CfdiEmisor::where('shop_id', $receipt->shop_id)->where('is_registered', true)->first();

        if (!$emisor) {
            return [
                'ok' => false,
                'complemento' => null,
                'message' => 'No hay emisor CFDI registrado para esta tienda.',
            ];
        }

        if ($emisor->timbresDisponibles() <= 0) {
            return [
                'ok' => false,
                'complemento' => null,
                'message' => 'No hay timbres disponibles para emitir el complemento.',
            ];
        }

        $fmt = fn($n) => number_format((float) $n, 2, '.', '');

        // === Calcular saldos ===
        // saldoInsoluto() solo cuenta complementos status=vigente, así que el registro
        // pending que crearemos abajo no contamina este cálculo.
        $impSaldoAnt = round($invoice->saldoInsoluto(), 2);
        $impPagado = round((float) $abono->amount, 2);

        if ($impPagado <= 0) {
            return [
                'ok' => false,
                'complemento' => null,
                'message' => 'El monto del abono debe ser mayor a cero.',
            ];
        }

        if ($impPagado > $impSaldoAnt) {
            return [
                'ok' => false,
                'complemento' => null,
                'message' => "El abono ({$impPagado}) excede el saldo insoluto ({$impSaldoAnt}).",
            ];
        }

        $impSaldoInsoluto = round($impSaldoAnt - $impPagado, 2);

        // === Registrar complemento pending y reservar folio ===
        $folio = null;
        $complemento = null;

        try {
            $folio = $emisor->siguienteFolioComplemento();
            $fechaEmision = Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s');
            $fechaPago = $abono->payment_date
                ? Carbon::parse($abono->payment_date, 'America/Mexico_City')->format('Y-m-d\TH:i:s')
                : Carbon::now('America/Mexico_City')->format('Y-m-d\TH:i:s');

            // Forma de pago: prioridad al abono (cada abono PPD puede tener forma distinta).
            // Fallback al receipt para registros antiguos sin payment_method seteado.
            $formaPago = $abono->payment_method
                ?: $this->mapearFormaPago($receipt->payment);
            $esBancarizada = in_array($formaPago, ['02','03','04','05','06','28','29'], true);

            $moneda = $shop->getCurrencyCode();
            $monedaPago = $moneda === 'XXX' ? 'MXN' : $moneda;

            // Datos bancarios opcionales del abono (solo se mandan al SAT si forma bancarizada)
            $datosBancarios = $esBancarizada
                ? $this->construirDatosBancarios($abono, $shop->id)
                : [];

            $complemento = CfdiPagoComplemento::create([
                'shop_id' => $shop->id,
                'cfdi_invoice_id' => $invoice->id,
                'partial_payment_id' => $abono->id,
                'serie' => $emisor->serie_complemento ?? 'CP',
                'folio' => $folio,
                'fecha_emision' => $fechaEmision,
                'monto' => $impPagado,
                'forma_pago' => $formaPago,
                'num_parcialidad' => $numParcialidad,
                'imp_saldo_ant' => $impSaldoAnt,
                'imp_pagado' => $impPagado,
                'imp_saldo_insoluto' => $impSaldoInsoluto,
                'status' => CfdiPagoComplemento::STATUS_PENDING,
            ]);

            // === Calcular impuestos proporcionales del pago (IVA traslado + retenciones ISR/IVA) ===
            $imp = $this->calcularImpuestosComplemento($invoice, $impPagado, $shop);
            $bloques = $this->armarBloqueImpuestos($imp);
            $tieneIva = $imp['tieneIva'];

            // === Armar payload tipo P (estructura HUB CFDI: complementos.pagos_20) ===
            $totalesPagos = array_merge(
                $tieneIva ? [
                    'total_traslados_base_iva16' => $fmt($imp['baseDr']),
                    'total_traslados_impuesto_iva16' => $fmt($imp['ivaDr']),
                ] : [],
                $bloques['totales_extra'],
                ['monto_total_pagos' => $fmt($impPagado)]
            );

            $doctoRelacionado = array_merge([
                'id_documento' => $invoice->uuid,
                'serie' => $invoice->serie,
                'folio' => (string) $invoice->folio,
                'moneda_dr' => $monedaPago,
                'equivalencia_dr' => 1,
                'num_parcialidad' => $numParcialidad,
                'imp_saldo_ant' => $fmt($impSaldoAnt),
                'imp_pagado' => $fmt($impPagado),
                'imp_saldo_insoluto' => $fmt($impSaldoInsoluto),
                'objeto_imp_dr' => $tieneIva ? '02' : '01',
            ], !empty($bloques['docto_impuestos_dr']) ? ['impuestos_dr' => $bloques['docto_impuestos_dr']] : []);

            $payload = [
                'serie' => $emisor->serie_complemento ?? 'CP',
                'folio' => (string) $folio,
                'fecha_emision' => $fechaEmision,
                'tipo_comprobante' => 'P',
                'exportacion' => '01',
                'moneda' => 'XXX',
                'lugar_expedicion' => $emisor->codigo_postal,
                'subtotal' => 0,
                'total' => 0,
                'emisor' => [
                    'rfc' => $emisor->rfc,
                    'razon_social' => $emisor->razon_social,
                    'regimen_fiscal' => $emisor->regimen_fiscal,
                ],
                'receptor' => [
                    'rfc' => $invoice->receptor_rfc,
                    'razon_social' => $invoice->receptor_nombre,
                    'regimen_fiscal' => $invoice->receptor_regimen,
                    'uso_cfdi' => 'CP01',
                    'codigo_postal' => $invoice->receptor_cp,
                ],
                'conceptos' => [[
                    'clave_prod_serv' => '84111506',
                    'cantidad' => 1,
                    'clave_unidad' => 'ACT',
                    'descripcion' => 'Pago',
                    'valor_unitario' => 0,
                    'subtotal' => 0,
                    'importe' => 0,
                    'objeto_impuesto' => '01',
                ]],
                'complementos' => [
                    'pagos_20' => [
                        'version' => '2.0',
                        'totales' => $totalesPagos,
                        'pago' => [array_merge([
                            ...array_filter([
                                'fecha_pago' => $fechaPago,
                                'forma_de_pago_p' => $formaPago,
                                'moneda_p' => $monedaPago,
                                'tipo_cambio_p' => 1,
                                'monto' => $fmt($impPagado),
                                'num_operacion' => $datosBancarios['num_operacion'] ?? null,
                                'rfc_emisor_cta_ord' => $datosBancarios['rfc_emisor_cta_ord'] ?? null,
                                'nom_banco_ord_ext' => $datosBancarios['nom_banco_ord_ext'] ?? null,
                                'cta_ordenante' => $datosBancarios['cta_ordenante'] ?? null,
                                'rfc_emisor_cta_ben' => $datosBancarios['rfc_emisor_cta_ben'] ?? null,
                                'cta_beneficiario' => $datosBancarios['cta_beneficiario'] ?? null,
                            ], fn($v) => $v !== null && $v !== ''),
                            'docto_relacionado' => [$doctoRelacionado],
                        ], !empty($bloques['impuestos_p']) ? ['impuestos_p' => $bloques['impuestos_p']] : [])],
                    ],
                ],
            ];

            $complemento->request_json = $payload;
            $complemento->save();

            // === Llamar PAC ===
            // app() para que tests/tinker puedan inyectar fake via container binding.
            $hubService = app(HubCfdiService::class);
            $result = $hubService->timbrar($payload);

            if (!$result['success']) {
                $emisor->revertirFolioComplemento();
                $complemento->update([
                    'status' => CfdiPagoComplemento::STATUS_FAILED,
                    'error_message' => $result['error'] ?? 'Error desconocido',
                    'response_json' => $result['data'] ?? null,
                ]);
                Log::error('Complemento de pago fallido', [
                    'shop_id' => $shop->id,
                    'receipt_id' => $receipt->id,
                    'invoice_uuid' => $invoice->uuid,
                    'folio_revertido' => $folio,
                    'error' => $result['error'],
                ]);
                return [
                    'ok' => false,
                    'complemento' => $complemento,
                    'message' => 'Error al timbrar complemento: ' . ($result['error'] ?? 'Error desconocido'),
                ];
            }

            $responseData = $result['data'];
            $uuid = $responseData['uuid'] ?? null;

            $complemento->update([
                'uuid' => $uuid,
                'fecha_timbrado' => $responseData['fecha_timbrado'] ?? null,
                'status' => CfdiPagoComplemento::STATUS_VIGENTE,
                'response_json' => $responseData,
            ]);

            // Persistir desglose de impuestos en cfdi_pago_complemento_taxes
            $this->persistirImpuestosComplemento($complemento, $imp);

            // Guardar XML local (PDF se genera bajo demanda al descargar — patrón actual)
            $this->guardarXmlLocal($hubService, $complemento, $shop->id);

            $emisor->increment('timbres_usados');

            Log::info('Complemento de pago timbrado', [
                'shop_id' => $shop->id,
                'receipt_id' => $receipt->id,
                'invoice_uuid' => $invoice->uuid,
                'complemento_uuid' => $uuid,
                'monto' => $impPagado,
            ]);

            return [
                'ok' => true,
                'complemento' => $complemento->fresh(),
                'message' => 'Complemento de pago emitido correctamente.',
            ];
        } catch (\Exception $e) {
            if ($folio !== null) {
                $emisor->revertirFolioComplemento();
            }
            if ($complemento) {
                $complemento->update([
                    'status' => CfdiPagoComplemento::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                ]);
            }
            Log::error('Complemento de pago exception', [
                'shop_id' => $shop->id,
                'receipt_id' => $receipt->id,
                'invoice_uuid' => $invoice->uuid,
                'folio_revertido' => $folio,
                'error' => $e->getMessage(),
            ]);
            return [
                'ok' => false,
                'complemento' => $complemento,
                'message' => 'Error al emitir complemento: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Emite UN solo complemento de pago que abarca varios abonos previos.
     * Usado cuando el usuario timbra una nota PPD con abonos previos y elige
     * la estrategia "consolidar" (en lugar de "separar" con un complemento por abono).
     *
     * @param Receipt                  $receipt          Nota con cfdiInvoice cargado
     * @param \Illuminate\Support\Collection $abonos     Colección de PartialPayments a consolidar (>= 1)
     * @param array                    $datos            Decisión del usuario:
     *   - payment_method (string requerido): forma SAT del pago consolidado
     *   - fecha_pago (string opcional ISO): fecha del consolidado, default = la del último abono
     *   - shop_bank_account_id, bank_ord_code, cta_ordenante, is_foreign_bank_ord, num_operacion (opcionales)
     * @param int                      $numParcialidad   Número de parcialidad del complemento
     */
    public function emitirConsolidado(Receipt $receipt, \Illuminate\Support\Collection $abonos, array $datos, int $numParcialidad): array
    {
        $invoice = $receipt->cfdiInvoice;

        if (!$invoice || $invoice->tipo_comprobante !== 'I' || $invoice->metodo_pago !== 'PPD' || $invoice->status !== 'vigente') {
            return ['ok' => false, 'complemento' => null, 'message' => 'La nota no tiene una factura PPD vigente.'];
        }
        if ($abonos->isEmpty()) {
            return ['ok' => false, 'complemento' => null, 'message' => 'No hay abonos para consolidar.'];
        }
        if (empty($datos['payment_method'])) {
            return ['ok' => false, 'complemento' => null, 'message' => 'Falta payment_method para el complemento consolidado.'];
        }

        $shop = $receipt->shop;
        $emisor = CfdiEmisor::where('shop_id', $receipt->shop_id)->where('is_registered', true)->first();

        if (!$emisor) {
            return ['ok' => false, 'complemento' => null, 'message' => 'No hay emisor CFDI registrado para esta tienda.'];
        }
        if ($emisor->timbresDisponibles() <= 0) {
            return ['ok' => false, 'complemento' => null, 'message' => 'No hay timbres disponibles para emitir el complemento.'];
        }

        $fmt = fn($n) => number_format((float) $n, 2, '.', '');

        // === Calcular saldos consolidados ===
        $impSaldoAnt = round($invoice->saldoInsoluto(), 2);
        $impPagado = round((float) $abonos->sum('amount'), 2);

        if ($impPagado <= 0) {
            return ['ok' => false, 'complemento' => null, 'message' => 'La suma de abonos debe ser mayor a cero.'];
        }
        if ($impPagado > $impSaldoAnt) {
            return ['ok' => false, 'complemento' => null, 'message' => "La suma ({$impPagado}) excede el saldo insoluto ({$impSaldoAnt})."];
        }

        $impSaldoInsoluto = round($impSaldoAnt - $impPagado, 2);

        // Abono representativo (legacy partial_payment_id) y array de IDs consolidados
        $abonoPrimero = $abonos->first();
        $consolidatedIds = $abonos->pluck('id')->values()->all();

        // Fecha de pago: la que mande el front, o la del último abono
        $fechaPagoSrc = $datos['fecha_pago']
            ?? optional($abonos->sortByDesc('payment_date')->first())->payment_date
            ?? Carbon::now('America/Mexico_City');

        $folio = null;
        $complemento = null;

        try {
            $folio = $emisor->siguienteFolioComplemento();
            $fechaEmision = Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s');
            $fechaPago = Carbon::parse($fechaPagoSrc, 'America/Mexico_City')->format('Y-m-d\TH:i:s');

            $formaPago = $datos['payment_method'];
            $esBancarizada = in_array($formaPago, ['02','03','04','05','06','28','29'], true);

            $moneda = $shop->getCurrencyCode();
            $monedaPago = $moneda === 'XXX' ? 'MXN' : $moneda;

            // Datos bancarios opcionales (vienen del request, usamos un partial_payment "virtual" para reutilizar el helper)
            $datosBancarios = [];
            if ($esBancarizada) {
                $virtualAbono = new PartialPayments([
                    'shop_bank_account_id' => $datos['shop_bank_account_id'] ?? null,
                    'bank_ord_code' => $datos['bank_ord_code'] ?? null,
                    'cta_ordenante' => $datos['cta_ordenante'] ?? null,
                    'is_foreign_bank_ord' => (bool) ($datos['is_foreign_bank_ord'] ?? false),
                    'num_operacion' => $datos['num_operacion'] ?? null,
                ]);
                $datosBancarios = $this->construirDatosBancarios($virtualAbono, $shop->id);
            }

            $complemento = CfdiPagoComplemento::create([
                'shop_id' => $shop->id,
                'cfdi_invoice_id' => $invoice->id,
                'partial_payment_id' => $abonoPrimero->id,
                'consolidated_partial_payment_ids' => $consolidatedIds,
                'serie' => $emisor->serie_complemento ?? 'CP',
                'folio' => $folio,
                'fecha_emision' => $fechaEmision,
                'monto' => $impPagado,
                'forma_pago' => $formaPago,
                'num_parcialidad' => $numParcialidad,
                'imp_saldo_ant' => $impSaldoAnt,
                'imp_pagado' => $impPagado,
                'imp_saldo_insoluto' => $impSaldoInsoluto,
                'status' => CfdiPagoComplemento::STATUS_PENDING,
            ]);

            $imp = $this->calcularImpuestosComplemento($invoice, $impPagado, $shop);
            $bloques = $this->armarBloqueImpuestos($imp);
            $tieneIva = $imp['tieneIva'];

            $totalesPagos = array_merge(
                $tieneIva ? [
                    'total_traslados_base_iva16' => $fmt($imp['baseDr']),
                    'total_traslados_impuesto_iva16' => $fmt($imp['ivaDr']),
                ] : [],
                $bloques['totales_extra'],
                ['monto_total_pagos' => $fmt($impPagado)]
            );

            $doctoRelacionado = array_merge([
                'id_documento' => $invoice->uuid,
                'serie' => $invoice->serie,
                'folio' => (string) $invoice->folio,
                'moneda_dr' => $monedaPago,
                'equivalencia_dr' => 1,
                'num_parcialidad' => $numParcialidad,
                'imp_saldo_ant' => $fmt($impSaldoAnt),
                'imp_pagado' => $fmt($impPagado),
                'imp_saldo_insoluto' => $fmt($impSaldoInsoluto),
                'objeto_imp_dr' => $tieneIva ? '02' : '01',
            ], !empty($bloques['docto_impuestos_dr']) ? ['impuestos_dr' => $bloques['docto_impuestos_dr']] : []);

            $payload = [
                'serie' => $emisor->serie_complemento ?? 'CP',
                'folio' => (string) $folio,
                'fecha_emision' => $fechaEmision,
                'tipo_comprobante' => 'P',
                'exportacion' => '01',
                'moneda' => 'XXX',
                'lugar_expedicion' => $emisor->codigo_postal,
                'subtotal' => 0,
                'total' => 0,
                'emisor' => [
                    'rfc' => $emisor->rfc,
                    'razon_social' => $emisor->razon_social,
                    'regimen_fiscal' => $emisor->regimen_fiscal,
                ],
                'receptor' => [
                    'rfc' => $invoice->receptor_rfc,
                    'razon_social' => $invoice->receptor_nombre,
                    'regimen_fiscal' => $invoice->receptor_regimen,
                    'uso_cfdi' => 'CP01',
                    'codigo_postal' => $invoice->receptor_cp,
                ],
                'conceptos' => [[
                    'clave_prod_serv' => '84111506',
                    'cantidad' => 1,
                    'clave_unidad' => 'ACT',
                    'descripcion' => 'Pago',
                    'valor_unitario' => 0,
                    'subtotal' => 0,
                    'importe' => 0,
                    'objeto_impuesto' => '01',
                ]],
                'complementos' => [
                    'pagos_20' => [
                        'version' => '2.0',
                        'totales' => $totalesPagos,
                        'pago' => [array_merge([
                            ...array_filter([
                                'fecha_pago' => $fechaPago,
                                'forma_de_pago_p' => $formaPago,
                                'moneda_p' => $monedaPago,
                                'tipo_cambio_p' => 1,
                                'monto' => $fmt($impPagado),
                                'num_operacion' => $datosBancarios['num_operacion'] ?? null,
                                'rfc_emisor_cta_ord' => $datosBancarios['rfc_emisor_cta_ord'] ?? null,
                                'nom_banco_ord_ext' => $datosBancarios['nom_banco_ord_ext'] ?? null,
                                'cta_ordenante' => $datosBancarios['cta_ordenante'] ?? null,
                                'rfc_emisor_cta_ben' => $datosBancarios['rfc_emisor_cta_ben'] ?? null,
                                'cta_beneficiario' => $datosBancarios['cta_beneficiario'] ?? null,
                            ], fn($v) => $v !== null && $v !== ''),
                            'docto_relacionado' => [$doctoRelacionado],
                        ], !empty($bloques['impuestos_p']) ? ['impuestos_p' => $bloques['impuestos_p']] : [])],
                    ],
                ],
            ];

            $complemento->request_json = $payload;
            $complemento->save();

            $hubService = app(HubCfdiService::class);
            $result = $hubService->timbrar($payload);

            if (!$result['success']) {
                $emisor->revertirFolioComplemento();
                $complemento->update([
                    'status' => CfdiPagoComplemento::STATUS_FAILED,
                    'error_message' => $result['error'] ?? 'Error desconocido',
                    'response_json' => $result['data'] ?? null,
                ]);
                Log::error('Complemento consolidado fallido', [
                    'shop_id' => $shop->id,
                    'receipt_id' => $receipt->id,
                    'consolidated_ids' => $consolidatedIds,
                    'error' => $result['error'],
                ]);
                return [
                    'ok' => false,
                    'complemento' => $complemento,
                    'message' => 'Error al timbrar complemento consolidado: ' . ($result['error'] ?? 'Error desconocido'),
                ];
            }

            $responseData = $result['data'];
            $uuid = $responseData['uuid'] ?? null;

            $complemento->update([
                'uuid' => $uuid,
                'fecha_timbrado' => $responseData['fecha_timbrado'] ?? null,
                'status' => CfdiPagoComplemento::STATUS_VIGENTE,
                'response_json' => $responseData,
            ]);

            $this->persistirImpuestosComplemento($complemento, $imp);
            $this->guardarXmlLocal($hubService, $complemento, $shop->id);
            $emisor->increment('timbres_usados');

            Log::info('Complemento consolidado timbrado', [
                'shop_id' => $shop->id,
                'receipt_id' => $receipt->id,
                'consolidated_ids' => $consolidatedIds,
                'complemento_uuid' => $uuid,
                'monto' => $impPagado,
            ]);

            return [
                'ok' => true,
                'complemento' => $complemento->fresh(),
                'message' => 'Complemento consolidado emitido correctamente.',
            ];
        } catch (\Exception $e) {
            if ($folio !== null) {
                $emisor->revertirFolioComplemento();
            }
            if ($complemento) {
                $complemento->update([
                    'status' => CfdiPagoComplemento::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                ]);
            }
            Log::error('Complemento consolidado exception', [
                'shop_id' => $shop->id,
                'receipt_id' => $receipt->id,
                'consolidated_ids' => $consolidatedIds,
                'error' => $e->getMessage(),
            ]);
            return [
                'ok' => false,
                'complemento' => $complemento,
                'message' => 'Error al emitir complemento consolidado: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Reintenta un complemento que quedó en status='failed'. Reutiliza el partial_payment
     * y el num_parcialidad del registro original; recalcula saldos por si entró otro
     * complemento entre el fallo y el reintento.
     */
    public function reemitirPendiente(int $complementoId): array
    {
        $complemento = CfdiPagoComplemento::find($complementoId);

        if (!$complemento) {
            return ['ok' => false, 'complemento' => null, 'message' => 'Complemento no encontrado.'];
        }

        if ($complemento->status !== CfdiPagoComplemento::STATUS_FAILED) {
            return [
                'ok' => false,
                'complemento' => $complemento,
                'message' => 'Solo se pueden re-emitir complementos en estado failed.',
            ];
        }

        $abono = $complemento->partialPayment;
        $receipt = $complemento->cfdiInvoice?->receipt;

        if (!$abono || !$receipt) {
            return [
                'ok' => false,
                'complemento' => $complemento,
                'message' => 'No se pudo localizar el abono o la nota original.',
            ];
        }

        // Recalcular num_parcialidad por si hubo otros complementos vigentes desde el fallo
        $numParcialidad = $complemento->cfdiInvoice
            ->complementos()
            ->where('status', CfdiPagoComplemento::STATUS_VIGENTE)
            ->count() + 1;

        // Borrar el registro failed antes de re-emitir (evita duplicado para el mismo abono)
        $complemento->delete();

        return $this->emitir($receipt, $abono, $numParcialidad);
    }

    /**
     * Mapea la forma de pago humana del receipt a clave SAT (catálogo c_FormaPago).
     * Solo se usa como fallback cuando partial_payments.payment_method está vacío
     * (registros legacy anteriores a la migración add_payment_fields_to_partial_payments).
     */
    protected function mapearFormaPago(?string $payment): string
    {
        return match (strtoupper(trim((string) $payment))) {
            'EFECTIVO'      => '01',
            'CHEQUE'        => '02',
            'TRANSFERENCIA' => '03',
            'TARJETA'       => '04',
            default         => '99',
        };
    }

    /**
     * Construye los nodos bancarios condicionales del nodo pago[0] según el SAT
     * Pagos 2.0. Solo se llama cuando la forma de pago es bancarizada
     * (02,03,04,05,06,28,29). Todos los campos son opcionales — solo se incluyen
     * los que efectivamente se capturaron.
     *
     * Cuenta beneficiaria: viene de shop_bank_accounts (la cuenta del comercio
     * que recibió el pago). Si no se eligió, se intenta usar la default activa.
     * Cuenta ordenante: viene del propio abono (datos del cliente pagador).
     *
     * @return array<string,string>
     */
    protected function construirDatosBancarios(PartialPayments $abono, int $shopId): array
    {
        $out = [];

        if ($abono->num_operacion) {
            $out['num_operacion'] = $abono->num_operacion;
        }

        // Cuenta ORDENANTE (cliente)
        if ($abono->is_foreign_bank_ord) {
            // Banco extranjero: RFC fijo y se manda nom_banco_ord_ext
            $out['rfc_emisor_cta_ord'] = 'XEXX010101000';
            if ($abono->bank_ord_code) {
                $banco = Bancos::find($abono->bank_ord_code);
                if ($banco && !empty($banco['name'])) {
                    $out['nom_banco_ord_ext'] = $banco['name'];
                }
            }
        } elseif ($abono->bank_ord_code) {
            $banco = Bancos::find($abono->bank_ord_code);
            if ($banco && !empty($banco['rfc'])) {
                $out['rfc_emisor_cta_ord'] = $banco['rfc'];
            }
        }
        if ($abono->cta_ordenante) {
            $out['cta_ordenante'] = $abono->cta_ordenante;
        }

        // Cuenta BENEFICIARIA (comercio): explícita en el abono o default activa de la tienda
        $cuentaBeneficiaria = null;
        if ($abono->shop_bank_account_id) {
            $cuentaBeneficiaria = ShopBankAccount::find($abono->shop_bank_account_id);
        }
        if (!$cuentaBeneficiaria) {
            $cuentaBeneficiaria = ShopBankAccount::where('shop_id', $shopId)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();
        }
        if ($cuentaBeneficiaria) {
            if (!empty($cuentaBeneficiaria->bank_rfc)) {
                $out['rfc_emisor_cta_ben'] = $cuentaBeneficiaria->bank_rfc;
            }
            $out['cta_beneficiario'] = $cuentaBeneficiaria->clabe;
        }

        return $out;
    }

    /**
     * Formato SAT para tasas: string con 6 decimales (ej. "0.106667").
     */
    private function fmtTasa(float $tasa): string
    {
        return number_format($tasa, 6, '.', '');
    }

    /**
     * Calcula impuestos proporcionales (traslado IVA + retenciones ISR/IVA) del pago
     * actual sobre la factura PPD. Retorna metadata para el payload Y persistencia.
     *
     * Regla SAT (validada en sandbox §0.16 del plan):
     * - factor = monto_pago / total_factura
     * - baseDr = subtotal_factura × factor
     * - traslado/retención DR = total_correspondiente × factor
     * - Si es el último complemento (cubre el saldo restante), se ajusta por DIFERENCIA
     *   contra lo ya emitido en complementos vigentes anteriores → suma exacta sin centavos perdidos.
     */
    private function calcularImpuestosComplemento(CfdiInvoice $invoice, float $impPagado, $shop): array
    {
        $tieneIva = (float) $invoice->total_impuestos > 0;
        $totalRetenciones = (float) $invoice->total_retenciones;
        $tieneRetenciones = $totalRetenciones > 0;

        // Sin retenciones ni IVA: comportamiento legacy (solo monto, sin impuestos)
        if (!$tieneIva && !$tieneRetenciones) {
            return [
                'tieneIva' => false,
                'tieneRetIsr' => false,
                'tieneRetIva' => false,
                'baseDr' => $impPagado,
                'ivaDr' => 0,
                'retIsrDr' => 0,
                'retIvaDr' => 0,
                'tasaIva' => $shop->getTaxSatRate(),
                'tasaIsr' => null,
                'tasaIvaRet' => null,
            ];
        }

        // Cargar montos globales de la factura
        $subtotalInv = (float) $invoice->subtotal;
        $totalIvaInv = (float) $invoice->total_impuestos;
        $totalInv = (float) $invoice->total;

        // Retenciones globales por impuesto (filas con concepto_index NULL)
        $retGlobales = $invoice->retenciones()->whereNull('concepto_index')->get();
        $retIsrInv = (float) $retGlobales->where('impuesto', '001')->sum('importe');
        $retIvaInv = (float) $retGlobales->where('impuesto', '002')->sum('importe');

        // Base efectiva del CFDI (post-descuento/cortesías). El campo `subtotal`
        // guarda la suma BRUTA de importes; cuando la nota lleva descuento o
        // ítems en cortesía, `total_impuestos` se calcula sobre la base ya
        // descontada, por lo que multiplicar `subtotal * factor` produce un
        // `base_dr` incoherente con `importe_dr`. La base efectiva se reconstruye
        // desde total + retenciones − IVA y se usa para repartir proporcionalmente.
        $baseEfectivaInv = round($totalInv - $totalIvaInv + $retIsrInv + $retIvaInv, 2);

        // Tasas de retención: vienen en las filas POR concepto (las globales no llevan tasa).
        $tasaIsrFila = $invoice->retenciones()->whereNotNull('concepto_index')->where('impuesto', '001')->first();
        $tasaIvaFila = $invoice->retenciones()->whereNotNull('concepto_index')->where('impuesto', '002')->first();
        $tasaIsr = $tasaIsrFila ? $this->fmtTasa((float) $tasaIsrFila->tasa) : null;
        $tasaIvaRet = $tasaIvaFila ? $this->fmtTasa((float) $tasaIvaFila->tasa) : null;

        // ¿Es el último complemento? Cubre exactamente el saldo restante.
        // Solo cuenta complementos vigentes (NO los pending creados en esta misma transacción).
        $complementosVigentes = $invoice->complementos()->where('status', CfdiPagoComplemento::STATUS_VIGENTE)->get();
        $sumImpPagadoPrevio = (float) $complementosVigentes->sum('imp_pagado');
        $faltante = round($totalInv - $sumImpPagadoPrevio, 2);
        $esUltimo = abs($faltante - round($impPagado, 2)) < 0.005;

        if ($esUltimo) {
            // Diferencial: la suma exacta de todos los complementos == totales de la factura.
            $sumBaseDrPrevio = 0;
            $sumIvaDrPrevio = 0;
            $sumRetIsrDrPrevio = 0;
            $sumRetIvaDrPrevio = 0;
            foreach ($complementosVigentes as $c) {
                $sumBaseDrPrevio += (float) $c->taxesDr()->where('tipo', 'traslado')->sum('base');
                $sumIvaDrPrevio += (float) $c->taxesDr()->where('tipo', 'traslado')->sum('importe');
                $sumRetIsrDrPrevio += (float) $c->taxesDr()->where('tipo', 'retencion')->where('impuesto', '001')->sum('importe');
                $sumRetIvaDrPrevio += (float) $c->taxesDr()->where('tipo', 'retencion')->where('impuesto', '002')->sum('importe');
            }
            $baseDr = round($baseEfectivaInv - $sumBaseDrPrevio, 2);
            $ivaDr = $tieneIva ? round($totalIvaInv - $sumIvaDrPrevio, 2) : 0;
            $retIsrDr = $retIsrInv > 0 ? round($retIsrInv - $sumRetIsrDrPrevio, 2) : 0;
            $retIvaDr = $retIvaInv > 0 ? round($retIvaInv - $sumRetIvaDrPrevio, 2) : 0;
        } else {
            $factor = $totalInv > 0 ? $impPagado / $totalInv : 0;
            $baseDr = round($baseEfectivaInv * $factor, 2);
            $ivaDr = $tieneIva ? round($totalIvaInv * $factor, 2) : 0;
            $retIsrDr = $retIsrInv > 0 ? round($retIsrInv * $factor, 2) : 0;
            $retIvaDr = $retIvaInv > 0 ? round($retIvaInv * $factor, 2) : 0;
        }

        return [
            'tieneIva' => $tieneIva,
            'tieneRetIsr' => $retIsrDr >= 0.01,
            'tieneRetIva' => $retIvaDr >= 0.01,
            'baseDr' => $baseDr,
            'ivaDr' => $ivaDr,
            'retIsrDr' => $retIsrDr,
            'retIvaDr' => $retIvaDr,
            'tasaIva' => $shop->getTaxSatRate(),
            'tasaIsr' => $tasaIsr,
            'tasaIvaRet' => $tasaIvaRet,
        ];
    }

    /**
     * Construye los nodos `impuestos_dr`, `impuestos_p` y los totales del bloque pagos_20
     * a partir de la metadata de impuestos calculada.
     *
     * @return array{
     *   docto_impuestos_dr: array,
     *   impuestos_p: array,
     *   totales_extra: array
     * }
     */
    private function armarBloqueImpuestos(array $imp): array
    {
        $fmt = fn($n) => number_format((float) $n, 2, '.', '');

        $traslados = [];
        $retencionesDr = [];
        $traslados_p = [];
        $retenciones_p = [];

        if ($imp['tieneIva'] && $imp['ivaDr'] > 0) {
            $traslados[] = [
                'base_dr' => $fmt($imp['baseDr']),
                'impuesto_dr' => '002',
                'tipo_factor_dr' => 'Tasa',
                'tasa_o_cuota_dr' => $imp['tasaIva'],
                'importe_dr' => $fmt($imp['ivaDr']),
            ];
            $traslados_p[] = [
                'base_p' => $fmt($imp['baseDr']),
                'impuesto_p' => '002',
                'tipo_factor_p' => 'Tasa',
                'tasa_o_cuota_p' => $imp['tasaIva'],
                'importe_p' => $fmt($imp['ivaDr']),
            ];
        }

        if ($imp['tieneRetIsr']) {
            $retencionesDr[] = [
                'base_dr' => $fmt($imp['baseDr']),
                'impuesto_dr' => '001',
                'tipo_factor_dr' => 'Tasa',
                'tasa_o_cuota_dr' => $imp['tasaIsr'] ?? '0.000000',
                'importe_dr' => $fmt($imp['retIsrDr']),
            ];
            $retenciones_p[] = [
                'impuesto_p' => '001',
                'importe_p' => $fmt($imp['retIsrDr']),
            ];
        }
        if ($imp['tieneRetIva']) {
            $retencionesDr[] = [
                'base_dr' => $fmt($imp['baseDr']),
                'impuesto_dr' => '002',
                'tipo_factor_dr' => 'Tasa',
                'tasa_o_cuota_dr' => $imp['tasaIvaRet'] ?? '0.000000',
                'importe_dr' => $fmt($imp['retIvaDr']),
            ];
            $retenciones_p[] = [
                'impuesto_p' => '002',
                'importe_p' => $fmt($imp['retIvaDr']),
            ];
        }

        $impuestosDr = [];
        if (!empty($traslados)) $impuestosDr['traslados_dr'] = $traslados;
        if (!empty($retencionesDr)) $impuestosDr['retenciones_dr'] = $retencionesDr;

        $impuestosP = [];
        if (!empty($traslados_p)) $impuestosP['traslados_p'] = $traslados_p;
        if (!empty($retenciones_p)) $impuestosP['retenciones_p'] = $retenciones_p;

        // Totales extra para pagos_20.totales (suma de retenciones por impuesto)
        $totalesExtra = [];
        if ($imp['tieneRetIsr']) $totalesExtra['total_retenciones_isr'] = $fmt($imp['retIsrDr']);
        if ($imp['tieneRetIva']) $totalesExtra['total_retenciones_iva'] = $fmt($imp['retIvaDr']);

        return [
            'docto_impuestos_dr' => $impuestosDr,
            'impuestos_p' => $impuestosP,
            'totales_extra' => $totalesExtra,
        ];
    }

    /**
     * Persiste filas en cfdi_pago_complemento_taxes post-timbre.
     * Falla aquí NO revierte el timbre: el complemento ya está vigente en TBT.
     */
    private function persistirImpuestosComplemento(CfdiPagoComplemento $complemento, array $imp): void
    {
        try {
            // Scope DR (docto relacionado)
            if ($imp['tieneIva'] && $imp['ivaDr'] > 0) {
                $complemento->taxes()->create([
                    'scope' => 'dr', 'tipo' => 'traslado', 'impuesto' => '002',
                    'tipo_factor' => 'Tasa',
                    'tasa' => $imp['tasaIva'] ? (float) $imp['tasaIva'] : null,
                    'base' => $imp['baseDr'], 'importe' => $imp['ivaDr'],
                ]);
                $complemento->taxes()->create([
                    'scope' => 'p', 'tipo' => 'traslado', 'impuesto' => '002',
                    'tipo_factor' => 'Tasa',
                    'tasa' => $imp['tasaIva'] ? (float) $imp['tasaIva'] : null,
                    'base' => $imp['baseDr'], 'importe' => $imp['ivaDr'],
                ]);
            }
            if ($imp['tieneRetIsr']) {
                $complemento->taxes()->create([
                    'scope' => 'dr', 'tipo' => 'retencion', 'impuesto' => '001',
                    'tipo_factor' => 'Tasa',
                    'tasa' => $imp['tasaIsr'] ? (float) $imp['tasaIsr'] : null,
                    'base' => $imp['baseDr'], 'importe' => $imp['retIsrDr'],
                ]);
                // Scope P: sin tasa ni base (regla SAT, igual que en factura I global)
                $complemento->taxes()->create([
                    'scope' => 'p', 'tipo' => 'retencion', 'impuesto' => '001',
                    'importe' => $imp['retIsrDr'],
                ]);
            }
            if ($imp['tieneRetIva']) {
                $complemento->taxes()->create([
                    'scope' => 'dr', 'tipo' => 'retencion', 'impuesto' => '002',
                    'tipo_factor' => 'Tasa',
                    'tasa' => $imp['tasaIvaRet'] ? (float) $imp['tasaIvaRet'] : null,
                    'base' => $imp['baseDr'], 'importe' => $imp['retIvaDr'],
                ]);
                $complemento->taxes()->create([
                    'scope' => 'p', 'tipo' => 'retencion', 'impuesto' => '002',
                    'importe' => $imp['retIvaDr'],
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Complemento: persistencia de cfdi_pago_complemento_taxes falló', [
                'complemento_id' => $complemento->id,
                'uuid' => $complemento->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Descarga XML del PAC y lo guarda localmente. Replica el patrón de
     * CfdiTimbradoService::guardarArchivosLocales (sin generar PDF).
     */
    protected function guardarXmlLocal(HubCfdiService $hubService, CfdiPagoComplemento $complemento, int $shopId): void
    {
        try {
            $result = $hubService->descargar($complemento->uuid, 'xml');
            if (!$result['success']) {
                Log::warning('Complemento: no se pudo descargar XML post-timbrado', [
                    'complemento_id' => $complemento->id,
                    'error' => $result['error'],
                ]);
                return;
            }
            $base64 = $result['data']['archivo'] ?? $result['data']['base64'] ?? null;
            if (!$base64) return;

            $path = "{$shopId}/complementos/{$complemento->uuid}.xml";
            Storage::disk('cfdi')->put($path, base64_decode($base64));
            $complemento->update(['xml_path' => $path]);
        } catch (\Exception $e) {
            Log::warning('Complemento: excepción guardando XML local', [
                'complemento_id' => $complemento->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
