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

            // === Calcular impuestos proporcionales del pago ===
            // La factura original tiene IVA incluido si total_impuestos > 0.
            // Para el complemento se desglosa el IVA proporcional al monto pagado.
            $tieneIva = (float) $invoice->total_impuestos > 0;
            $taxDecimal = $shop->getTaxDecimal();
            $taxDivisor = $shop->getTaxDivisor();

            if ($tieneIva) {
                $baseDr = round($impPagado / $taxDivisor, 2);
                $importeDr = round($impPagado - $baseDr, 2);
            } else {
                $baseDr = $impPagado;
                $importeDr = 0;
            }

            // === Armar payload tipo P (estructura HUB CFDI: complementos.pagos_20) ===
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
                        'totales' => [
                            'total_traslados_base_iva16' => $tieneIva ? $fmt($baseDr) : '0.00',
                            'total_traslados_impuesto_iva16' => $tieneIva ? $fmt($importeDr) : '0.00',
                            'monto_total_pagos' => $fmt($impPagado),
                        ],
                        'pago' => [[
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
                            'docto_relacionado' => [array_merge([
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
                            ], $tieneIva ? [
                                'impuestos_dr' => [
                                    'traslados_dr' => [[
                                        'base_dr' => $fmt($baseDr),
                                        'impuesto_dr' => '002',
                                        'tipo_factor_dr' => 'Tasa',
                                        'tasa_o_cuota_dr' => $shop->getTaxSatRate(),
                                        'importe_dr' => $fmt($importeDr),
                                    ]],
                                ],
                            ] : [])],
                            ...($tieneIva ? ['impuestos_p' => [
                                'traslados_p' => [[
                                    'base_p' => $fmt($baseDr),
                                    'impuesto_p' => '002',
                                    'tipo_factor_p' => 'Tasa',
                                    'tasa_o_cuota_p' => $shop->getTaxSatRate(),
                                    'importe_p' => $fmt($importeDr),
                                ]],
                            ]] : []),
                        ]],
                    ],
                ],
            ];

            $complemento->request_json = $payload;
            $complemento->save();

            // === Llamar PAC ===
            $hubService = new HubCfdiService();
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
