<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CfdiEmisor;
use App\Models\CfdiInvoice;
use App\Models\CfdiPagoComplemento;
use App\Models\ClientFiscalData;
use App\Models\Receipt;
use App\Services\Facturacion\CfdiCancelacionService;
use App\Services\Facturacion\CfdiComplementoPagoService;
use App\Services\Facturacion\CfdiTimbradoService;
use App\Services\Facturacion\HubCfdiService;
use App\Services\Facturacion\Logging\LogFacturacion;
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

        if ($request->boolean('solo_con_retenciones')) {
            $query->where('total_retenciones', '>', 0);
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
                'total_retenciones' => round($vigentes->sum('total_retenciones'), 2),
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
                    'total_retenciones' => $f->total_retenciones,
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

        $receipt = Receipt::with(['detail.product', 'client.fiscalData'])
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

        // Validación SAT: la tasa de IVA debe estar en el catálogo c_TasaOCuota (México: 16%, 8%, 0%).
        // Se valida ANTES de abrir el formulario para no hacer perder tiempo al usuario.
        if ($receipt->iva > 0) {
            $tasaEfectiva = round($receipt->effectiveTaxRate(), 2);
            if (!in_array($tasaEfectiva, [0.0, 8.0, 16.0])) {
                $tasaTxt = rtrim(rtrim(number_format($tasaEfectiva, 2, '.', ''), '0'), '.');
                return response()->json([
                    'ok' => false,
                    'message' => "Esta nota tiene una tasa de impuesto de {$tasaTxt}%, que no es válida para facturación CFDI en México. El SAT solo permite 16%, 8% o 0%. Edita la nota y elige una tasa válida.",
                ], 422);
            }
        }

        return response()->json([
            'ok' => true,
            'receipt' => $receipt,
            'metodo_pago_calculado' => (float) $receipt->received < (float) $receipt->saldoEfectivoEsperado() ? 'PPD' : 'PUE',
            'emisor' => [
                'rfc' => $emisor->rfc,
                'razon_social' => $emisor->razon_social,
                'regimen_fiscal' => $emisor->regimen_fiscal,
                'codigo_postal' => $emisor->codigo_postal,
                'serie' => $emisor->serie,
                'timbres_disponibles' => $emisor->timbresDisponibles(),
                // Defaults de retenciones (frontend los usa para prellenar el modal).
                // Tasas en formato decimal (0.10 = 10%).
                'ret_isr_default_aplica' => (bool) $emisor->ret_isr_default_aplica,
                'ret_isr_default_tasa' => $emisor->ret_isr_default_tasa,
                'ret_iva_default_aplica' => (bool) $emisor->ret_iva_default_aplica,
                'ret_iva_default_tasa' => $emisor->ret_iva_default_tasa,
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
            // Retenciones (opcionales). Tasas en formato decimal (0.10 = 10%).
            'ret_isr_aplica' => 'nullable|boolean',
            'ret_isr_tasa' => 'nullable|numeric|between:0,1',
            'ret_iva_aplica' => 'nullable|boolean',
            'ret_iva_tasa' => 'nullable|numeric|between:0,1',
            'conceptos_sat' => 'nullable|array',
            'conceptos_sat.*.aplica_retencion' => 'nullable|boolean',
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

        // Si va a ser PPD y tiene abonos previos sin forma SAT real, exigir decisión del usuario.
        // PPD se mide contra el saldo en efectivo (total - retenciones), no contra el total comercial.
        $esPPD = (float) $receipt->received < (float) $receipt->saldoEfectivoEsperado();
        if ($esPPD) {
            $abonosPendientes = $receipt->getAbonosPreviosPendientesMetodo();
            if ($abonosPendientes->count() > 0) {
                $abonosPrevios = $request->input('abonos_previos');
                $errorPayload = $this->validarAbonosPrevios($abonosPrevios, $abonosPendientes);
                if ($errorPayload) {
                    return response()->json($errorPayload, 422);
                }
            }
        }

        // Delegar al servicio compartido (misma lógica para web admin y API Ionic)
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
            'abonos_previos' => $request->input('abonos_previos'),
            'ret_isr_aplica' => (bool) $request->input('ret_isr_aplica', false),
            'ret_isr_tasa' => (float) $request->input('ret_isr_tasa', 0),
            'ret_iva_aplica' => (bool) $request->input('ret_iva_aplica', false),
            'ret_iva_tasa' => (float) $request->input('ret_iva_tasa', 0),
            'impuestos_locales' => $request->input('impuestos_locales'),
        ]);

        if (!$result['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'],
            ], $result['status'] ?? 500);
        }

        $invoice = $result['invoice'];

        // Guardar XML + PDF local (sólo web admin pre-genera PDF, para tener vista previa rápida)
        $service->guardarArchivosLocales(
            $result['hub_service'],
            $invoice,
            $shop->id,
            fn($inv) => $this->generarPdf($inv)
        );

        return response()->json([
            'ok' => true,
            'message' => 'Factura timbrada exitosamente',
            'uuid' => $invoice->uuid,
            'invoice_id' => $invoice->id,
            'serie' => $invoice->serie,
            'folio' => $invoice->folio,
            'conceptos_cortesia_excluidos' => $result['conceptos_cortesia_excluidos'],
        ]);
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

        $startedAt = microtime(true);

        LogFacturacion::cancelacion('cfdi.cancelacion.attempt', [
            'shop_id' => $shop->id,
            'cfdi_invoice_id' => $invoice->id,
            'uuid' => $invoice->uuid,
            'metadata' => [
                'motivo' => $request->motivo,
                'folio_sustitucion' => $request->folio_sustitucion,
            ],
        ]);

        try {
            $cancelService = app(CfdiCancelacionService::class);
            $result = $cancelService->cancelar(
                $invoice,
                $request->motivo,
                $request->folio_sustitucion
            );

            if (!$result['success']) {
                LogFacturacion::cancelacion('cfdi.cancelacion.error', [
                    'shop_id' => $shop->id,
                    'cfdi_invoice_id' => $invoice->id,
                    'uuid' => $invoice->uuid,
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    'error_message' => is_string($result['error']) ? $result['error'] : json_encode($result['error']),
                    'metadata' => [
                        'motivo' => $request->motivo,
                        'folio_sustitucion' => $request->folio_sustitucion,
                    ],
                ], 'error');

                return response()->json([
                    'ok' => false,
                    'message' => 'Error al cancelar: ' . ($result['error'] ?? 'Error desconocido'),
                ]);
            }

            // Interpretar la respuesta del SAT/HUB: ¿cancelación FINAL o quedó EN PROCESO?
            // (regla SAT: facturas que requieren aceptación del receptor esperan hasta 3 días hábiles).
            $interpret = CfdiCancelacionService::interpretarRespuesta($result['data'] ?? null);
            $ahora = Carbon::now('America/Mexico_City');
            $esFinal = $interpret['final'];

            $datosCancelacion = [
                'motivo_cancelacion' => $request->motivo,
                'folio_sustitucion' => $request->folio_sustitucion,
                'fecha_solicitud_cancelacion' => $ahora,
                'estatus_cancelacion_sat' => $interpret['estatus_cancelacion_sat'],
                'estatus_uuid_sat' => $interpret['estatus_uuid_sat'],
                'cancelacion_status' => $interpret['cancelacion_status'] ?? ($esFinal ? 'aceptada' : null),
            ];

            // Solo se marca CANCELADA cuando es final (aceptada o respuesta ambigua → comportamiento previo).
            // Si quedó EN PROCESO / RECHAZADA, el CFDI sigue VIGENTE ante el SAT: no revertir el receipt.
            if ($esFinal) {
                $datosCancelacion['status'] = 'cancelada';
                $datosCancelacion['fecha_cancelacion'] = $ahora;
            }

            $invoice->update($datosCancelacion);

            if ($esFinal && $invoice->receipt_id) {
                $receipt = Receipt::find($invoice->receipt_id);
                if ($receipt) {
                    $receipt->is_tax_invoiced = false;
                    $receipt->save();
                }
            }

            // Loguear SIEMPRE la respuesta cruda + interpretación (desbloquea afinar el shape real).
            LogFacturacion::cancelacion('cfdi.cancelacion.result', [
                'shop_id' => $shop->id,
                'cfdi_invoice_id' => $invoice->id,
                'uuid' => $invoice->uuid,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'http_status' => 200,
                // Badge de la bitácora: éxito (verde) si fue final, warning si quedó en proceso/rechazada.
                'status' => $esFinal ? 'success' : 'warning',
                'metadata' => [
                    'motivo' => $request->motivo,
                    'folio_sustitucion' => $request->folio_sustitucion,
                    'cancelacion_status' => $datosCancelacion['cancelacion_status'],
                    'es_final' => $esFinal,
                    'reconocido' => $interpret['reconocido'],
                    'estatus_uuid_sat' => $interpret['estatus_uuid_sat'],
                    'estatus_cancelacion_sat' => $interpret['estatus_cancelacion_sat'],
                    'respuesta_cruda' => $result['data'] ?? null,
                ],
            ], $esFinal ? 'info' : 'warning');

            if ($esFinal) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Factura cancelada exitosamente',
                    'cancelacion_status' => $datosCancelacion['cancelacion_status'],
                ]);
            }

            return response()->json([
                'ok' => true,
                'en_proceso' => true,
                'cancelacion_status' => $datosCancelacion['cancelacion_status'],
                'message' => $interpret['cancelacion_status'] === 'en_proceso'
                    ? 'La cancelación quedó EN PROCESO ante el SAT: el receptor debe aceptarla (hasta 3 días hábiles). La factura sigue vigente hasta que el SAT confirme.'
                    : 'El SAT no aceptó la cancelación (' . ($interpret['estatus_cancelacion_sat'] ?: $interpret['cancelacion_status']) . '). La factura sigue vigente.',
            ]);

        } catch (\Exception $e) {
            LogFacturacion::cancelacion('cfdi.cancelacion.error', [
                'shop_id' => $shop->id,
                'cfdi_invoice_id' => $invoice->id,
                'uuid' => $invoice->uuid,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error_code' => 'exception',
                'error_message' => $e->getMessage(),
            ], 'error');

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

        // 1. Servir desde storage local si existe (solo XML; el PDF se regenera siempre desde la vista)
        if ($formato === 'xml' && $invoice->$pathColumn && Storage::disk('cfdi')->exists($invoice->$pathColumn)) {
            $content = Storage::disk('cfdi')->get($invoice->$pathColumn);

            return response($content)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        }

        // 2. Fallback según formato
        try {
            if ($formato === 'pdf') {
                // PDF: regenerar siempre desde la vista (representación visual, no documento legal)
                $content = $this->generarPdf($invoice);
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
     * Valida el payload abonos_previos cuando una nota PPD tiene abonos sin forma SAT.
     * Devuelve null si OK, o un array de error 422 con detalle del problema.
     *
     * @param mixed                                    $abonosPrevios input del request (puede ser null/array)
     * @param \Illuminate\Database\Eloquent\Collection $abonosPendientes lista de partial_payments que requieren decisión
     */
    public function validarAbonosPrevios($abonosPrevios, $abonosPendientes): ?array
    {
        if (!is_array($abonosPrevios) || empty($abonosPrevios['estrategia'])) {
            return [
                'ok' => false,
                'message' => 'Esta nota tiene abonos previos sin forma de pago. Debes decidir qué hacer con ellos antes de timbrar.',
                'requiere_decision' => true,
                'abonos_pendientes' => $abonosPendientes->map(fn($a) => [
                    'id' => $a->id,
                    'amount' => (float) $a->amount,
                    'payment_date' => $a->payment_date,
                    'payment_type' => $a->payment_type,
                ])->values(),
            ];
        }

        $estrategia = $abonosPrevios['estrategia'];

        if ($estrategia === 'consolidar') {
            $consolidado = $abonosPrevios['consolidado'] ?? null;
            if (!is_array($consolidado) || empty($consolidado['payment_method'])) {
                return ['ok' => false, 'message' => 'Falta payment_method en abonos_previos.consolidado'];
            }
            if ($consolidado['payment_method'] === '99') {
                return ['ok' => false, 'message' => 'El SAT rechaza forma de pago 99 en complementos. Elige una forma específica.'];
            }
            if (!empty($consolidado['fecha_pago']) && $this->fechaPagoEsFutura($consolidado['fecha_pago'])) {
                return ['ok' => false, 'message' => 'La fecha del consolidado no puede ser futura.'];
            }
            return null;
        }

        if ($estrategia === 'separar') {
            $asignaciones = collect($abonosPrevios['asignaciones'] ?? [])->keyBy('partial_payment_id');
            $faltan = [];
            foreach ($abonosPendientes as $a) {
                $asig = $asignaciones->get($a->id);
                if (!$asig || empty($asig['payment_method']) || $asig['payment_method'] === '99') {
                    $faltan[] = $a->id;
                    continue;
                }
                if (!empty($asig['fecha_pago']) && $this->fechaPagoEsFutura($asig['fecha_pago'])) {
                    return ['ok' => false, 'message' => "La fecha del abono #{$a->id} no puede ser futura."];
                }
            }
            if (count($faltan) > 0) {
                return [
                    'ok' => false,
                    'message' => 'Faltan asignaciones de forma de pago para algunos abonos previos.',
                    'partial_payment_ids_sin_asignar' => $faltan,
                ];
            }
            return null;
        }

        return ['ok' => false, 'message' => "Estrategia inválida: {$estrategia}. Usa 'separar' o 'consolidar'."];
    }

    /**
     * ¿La fecha de pago capturada es futura? (límite: fin del día de hoy, TZ México).
     * No validamos "anterior a la emisión" porque los abonos previos al timbrado PPD
     * legítimamente tienen fecha anterior al CFDI que se está emitiendo ahora.
     */
    private function fechaPagoEsFutura($fecha): bool
    {
        try {
            return Carbon::parse($fecha, 'America/Mexico_City')
                ->gt(Carbon::now('America/Mexico_City')->endOfDay());
        } catch (\Throwable $e) {
            return false; // no parseable → el servicio la ignora y conserva la fecha guardada
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
        // Pipeline xml_compat guarda los atributos del comprobante anidados; la vista PDF los espera en root.
        if (($invoice->pipeline_timbrado ?? 'json') === 'xml_compat' && isset($requestData['comprobante']) && is_array($requestData['comprobante'])) {
            $requestData = array_merge($requestData['comprobante'], $requestData);
        }
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
        $monedaSufijo = ($requestData['moneda'] ?? 'MXN') === 'MXN' ? 'M.N.' : 'USD';
        $importeLetra = $this->numeroALetras($invoice->total) . ' ' . $monedaSufijo;

        // Datos adicionales del response_json
        $responseData = $invoice->response_json ?? [];

        $pdf = Pdf::loadView($invoice->shop->pdfView('factura'), [
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

    /**
     * Listar abonos previos al timbrado PPD que aún no tienen forma de pago SAT.
     * El frontend usa esta lista para decidir si abrir el diálogo de "estrategia"
     * (separar vs consolidar) antes de timbrar.
     * GET /admin/facturacion/receipt/{receiptId}/abonos-previos-pendientes
     */
    public function abonosPreviosPendientes($receiptId)
    {
        $shop = auth()->user()->shop;

        $receipt = Receipt::where('shop_id', $shop->id)->find($receiptId);
        if (!$receipt) {
            return response()->json(['ok' => false, 'message' => 'Nota no encontrada'], 404);
        }

        $abonos = $receipt->getAbonosPreviosPendientesMetodo();

        return response()->json([
            'ok' => true,
            'requiere_decision' => $abonos->count() > 0,
            'abonos' => $abonos->map(fn($a) => [
                'id' => $a->id,
                'amount' => (float) $a->amount,
                'payment_date' => $a->payment_date,
                'payment_type' => $a->payment_type,
                'payment_method' => $a->payment_method,
            ])->values(),
        ]);
    }

    /**
     * Listar complementos de pago emitidos contra la factura PPD de una nota.
     * GET /admin/facturacion/nota/{receiptId}/complementos
     */
    public function listarComplementos($receiptId)
    {
        $shop = auth()->user()->shop;

        $receipt = Receipt::with('cfdiInvoice')->where('shop_id', $shop->id)->find($receiptId);
        if (!$receipt) {
            return response()->json(['ok' => false, 'message' => 'Nota no encontrada'], 404);
        }

        $invoice = $receipt->cfdiInvoice;
        if (!$invoice) {
            return response()->json([
                'ok' => true,
                'invoice' => null,
                'saldo_insoluto' => 0,
                'complementos' => [],
            ]);
        }

        $complementos = $invoice->complementos()
            ->orderBy('num_parcialidad')
            ->orderBy('id')
            ->get();

        // Enriquecer cada complemento con la fecha real del pago (del abono asociado),
        // que es la fiscalmente relevante (FechaPago del XML), no la fecha de emisión.
        $idsAbonos = $complementos->flatMap(function ($c) {
            $ids = [];
            if ($c->partial_payment_id) {
                $ids[] = $c->partial_payment_id;
            }
            if (!empty($c->consolidated_partial_payment_ids)) {
                $cons = is_array($c->consolidated_partial_payment_ids)
                    ? $c->consolidated_partial_payment_ids
                    : json_decode($c->consolidated_partial_payment_ids, true);
                $ids = array_merge($ids, (array) $cons);
            }
            return $ids;
        })->filter()->unique()->values()->all();

        $fechasAbono = \App\Models\PartialPayments::whereIn('id', $idsAbonos)
            ->pluck('payment_date', 'id'); // strings 'Y-m-d' (columna date)

        $complementosOut = $complementos->map(function ($c) use ($fechasAbono) {
            $arr = $c->toArray();
            $fecha = null;
            if ($c->partial_payment_id && $fechasAbono->has($c->partial_payment_id)) {
                $fecha = $fechasAbono->get($c->partial_payment_id);
            } elseif (!empty($c->consolidated_partial_payment_ids)) {
                $cons = is_array($c->consolidated_partial_payment_ids)
                    ? $c->consolidated_partial_payment_ids
                    : json_decode($c->consolidated_partial_payment_ids, true);
                // Consolidado: usar la fecha del último pago incluido.
                $fechas = collect((array) $cons)->map(fn ($id) => $fechasAbono->get($id))->filter();
                $fecha = $fechas->max();
            }
            $arr['fecha_pago'] = $fecha ? substr((string) $fecha, 0, 10) : null;
            return $arr;
        });

        return response()->json([
            'ok' => true,
            'invoice' => [
                'id' => $invoice->id,
                'uuid' => $invoice->uuid,
                'serie' => $invoice->serie,
                'folio' => $invoice->folio,
                'metodo_pago' => $invoice->metodo_pago,
                'subtotal' => (float) $invoice->subtotal,
                'total_impuestos' => (float) $invoice->total_impuestos,
                'total_retenciones' => (float) $invoice->total_retenciones,
                'total' => (float) $invoice->total,
                'status' => $invoice->status,
            ],
            'saldo_insoluto' => (float) $invoice->saldoInsoluto(),
            'complementos' => $complementosOut,
            'receipt_total_comercial' => (float) $receipt->total,
        ]);
    }

    /**
     * Descargar XML de un complemento de pago.
     * GET /admin/facturacion/complemento/{id}/descargar/xml
     */
    public function descargarComplemento($id, $formato = 'xml')
    {
        $shop = auth()->user()->shop;

        $complemento = CfdiPagoComplemento::where('id', $id)->where('shop_id', $shop->id)->first();
        if (!$complemento) {
            return response()->json(['ok' => false, 'message' => 'Complemento no encontrado'], 404);
        }

        if (!in_array($formato, ['xml', 'pdf'])) {
            return response()->json(['ok' => false, 'message' => 'Formato no válido'], 422);
        }

        if (!$complemento->uuid) {
            return response()->json(['ok' => false, 'message' => 'El complemento no tiene UUID (no fue timbrado).'], 422);
        }

        $contentType = $formato === 'xml' ? 'application/xml' : 'application/pdf';
        $filename = "complemento_{$complemento->serie}{$complemento->folio}.{$formato}";
        $pathColumn = "{$formato}_path";

        if ($complemento->$pathColumn && Storage::disk('cfdi')->exists($complemento->$pathColumn)) {
            $content = Storage::disk('cfdi')->get($complemento->$pathColumn);
            return response($content)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        }

        try {
            if ($formato === 'pdf') {
                $content = $this->generarPdfComplemento($complemento);
                $path = "{$shop->id}/complementos/{$complemento->uuid}.pdf";
                try {
                    Storage::disk('cfdi')->put($path, $content);
                    $complemento->update(['pdf_path' => $path]);
                } catch (\Exception $e) {
                    Log::warning('No se pudo cachear PDF del complemento', [
                        'complemento_id' => $id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                // XML: descargar de TBT
                $hubService = new HubCfdiService();
                $result = $hubService->descargar($complemento->uuid, 'xml');

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

                $path = "{$shop->id}/complementos/{$complemento->uuid}.xml";
                try {
                    Storage::disk('cfdi')->put($path, $content);
                    $complemento->update(['xml_path' => $path]);
                } catch (\Exception $e) {
                    Log::warning('No se pudo cachear XML del complemento', [
                        'complemento_id' => $id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response($content)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            Log::error('Complemento Descarga error', [
                'complemento_id' => $id,
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
     * Genera el PDF de un complemento de pago tipo P.
     */
    public function generarPdfComplemento(CfdiPagoComplemento $complemento): string
    {
        $complemento->loadMissing('cfdiInvoice.emisor');
        $invoice = $complemento->cfdiInvoice;
        $emisor = $invoice->emisor;
        $shop = \App\Models\Shop::find($complemento->shop_id);

        $logoBase64 = null;
        if ($shop && $shop->logo) {
            $logoPath = storage_path('app/public/' . $shop->logo);
            if (file_exists($logoPath)) {
                $logoBase64 = base64_encode(file_get_contents($logoPath));
            }
        }

        $responseData = $complemento->response_json ?? [];
        $timbreFiscal = $responseData['timbre_fiscal'] ?? null;

        // No. certificado del emisor (parsear del XML si está disponible)
        $noCertificadoEmisor = null;
        if ($complemento->xml_path && Storage::disk('cfdi')->exists($complemento->xml_path)) {
            try {
                $xml = Storage::disk('cfdi')->get($complemento->xml_path);
                if (preg_match('/NoCertificado="([^"]+)"/', $xml, $matches)) {
                    $noCertificadoEmisor = $matches[1];
                }
            } catch (\Exception $e) {}
        }

        $catalogos = [
            'forma_pago' => [
                '01' => 'Efectivo', '02' => 'Cheque nominativo', '03' => 'Transferencia electrónica',
                '04' => 'Tarjeta de crédito', '28' => 'Tarjeta de débito', '99' => 'Por definir',
            ],
            'regimen' => [
                '601' => 'General de Ley PM', '603' => 'PM con Fines no Lucrativos',
                '612' => 'Personas Físicas con Act. Empresariales y Profesionales',
                '616' => 'Sin obligaciones fiscales', '621' => 'Incorporación Fiscal',
                '626' => 'Régimen Simplificado de Confianza',
            ],
        ];

        // Datos del request_json para impuestos proporcionales
        $requestData = $complemento->request_json ?? [];
        $tieneIva = false;
        $baseDr = 0;
        $importeDr = 0;
        $taxRate = 0.16;

        $pago = $requestData['complementos']['pagos_20']['pago'][0] ?? null;
        if ($pago && isset($pago['impuestos_p']['traslados_p'][0])) {
            $tieneIva = true;
            $traslado = $pago['impuestos_p']['traslados_p'][0];
            $baseDr = (float) ($traslado['base_p'] ?? 0);
            $importeDr = (float) ($traslado['importe_p'] ?? 0);
            $taxRate = (float) ($traslado['tasa_o_cuota_p'] ?? 0.16);
        }

        // Retenciones del complemento (DR con tasa/base/importe, P con impuesto/importe)
        $impuestosNombre = ['001' => 'ISR', '002' => 'IVA', '003' => 'IEPS'];
        $retencionesDr = [];
        $totalRetenciones = 0;
        $totalRetIsr = 0;
        $totalRetIva = 0;
        if ($pago) {
            foreach ($pago['docto_relacionado'][0]['impuestos_dr']['retenciones_dr'] ?? [] as $r) {
                $retencionesDr[] = [
                    'codigo' => $r['impuesto_dr'] ?? '',
                    'nombre' => $impuestosNombre[$r['impuesto_dr']] ?? '',
                    'tasa' => $r['tasa_o_cuota_dr'] ?? '',
                    'base' => (float) ($r['base_dr'] ?? 0),
                    'importe' => (float) ($r['importe_dr'] ?? 0),
                ];
            }
            foreach ($pago['impuestos_p']['retenciones_p'] ?? [] as $r) {
                $imp = $r['impuesto_p'] ?? '';
                $monto = (float) ($r['importe_p'] ?? 0);
                $totalRetenciones += $monto;
                if ($imp === '001') $totalRetIsr += $monto;
                if ($imp === '002') $totalRetIva += $monto;
            }
        }
        $tieneRetenciones = !empty($retencionesDr);

        $fechaPagoFmt = $pago['fecha_pago'] ?? null;
        if ($fechaPagoFmt) {
            try {
                $fechaPagoFmt = \Carbon\Carbon::parse($fechaPagoFmt)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {}
        }

        // Datos del documento relacionado (moneda y tipo de cambio reales del payload)
        $doctoRel = $pago['docto_relacionado'][0] ?? [];
        $monedaDr = $doctoRel['moneda_dr'] ?? ($invoice->moneda ?? 'MXN');
        $equivalenciaDr = (float) ($doctoRel['equivalencia_dr'] ?? 1);

        // Información bancaria del pago (presente solo si forma bancarizada)
        $infoBancaria = [
            'num_operacion'        => $pago['num_operacion'] ?? null,
            'rfc_emisor_cta_ord'   => $pago['rfc_emisor_cta_ord'] ?? null,
            'nom_banco_ord_ext'    => $pago['nom_banco_ord_ext'] ?? null,
            'cta_ordenante'        => $pago['cta_ordenante'] ?? null,
            'rfc_emisor_cta_ben'   => $pago['rfc_emisor_cta_ben'] ?? null,
            'cta_beneficiario'     => $pago['cta_beneficiario'] ?? null,
        ];
        $tieneInfoBancaria = (bool) array_filter($infoBancaria, fn($v) => $v !== null && $v !== '');

        // QR SAT (mismo formato que factura)
        $qrBase64 = null;
        if ($complemento->uuid && $emisor) {
            $sello = $timbreFiscal['sello'] ?? '';
            $fe = substr($sello, -8);
            $totalFormatted = str_pad('0.000000', 24, '0', STR_PAD_LEFT);
            $qrUrl = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx"
                . "?id={$complemento->uuid}"
                . "&re={$emisor->rfc}"
                . "&rr={$invoice->receptor_rfc}"
                . "&tt={$totalFormatted}"
                . "&fe={$fe}";
            try {
                $qrPngData = base64_decode($this->generarQrPng($qrUrl));
                $qrBase64 = base64_encode($qrPngData);
            } catch (\Exception $e) {
                Log::warning('Complemento PDF: error generando QR', ['error' => $e->getMessage()]);
            }
        }

        $pdf = Pdf::loadView('cfdi.pdf-complemento', [
            'complemento' => $complemento,
            'invoice' => $invoice,
            'emisor' => $emisor,
            'logoBase64' => $logoBase64,
            'noCertificadoEmisor' => $noCertificadoEmisor,
            'timbreFiscal' => $timbreFiscal,
            'catalogos' => $catalogos,
            'tieneIva' => $tieneIva,
            'baseDr' => $baseDr,
            'importeDr' => $importeDr,
            'taxRate' => $taxRate,
            'fechaPagoFmt' => $fechaPagoFmt,
            'qrBase64' => $qrBase64,
            'monedaDr' => $monedaDr,
            'equivalenciaDr' => $equivalenciaDr,
            'tieneRetenciones' => $tieneRetenciones,
            'retencionesDr' => $retencionesDr,
            'totalRetenciones' => $totalRetenciones,
            'totalRetIsr' => $totalRetIsr,
            'totalRetIva' => $totalRetIva,
            'infoBancaria' => $infoBancaria,
            'tieneInfoBancaria' => $tieneInfoBancaria,
        ])->setPaper('letter', 'portrait');

        return $pdf->output();
    }

    /**
     * Re-emitir un complemento que quedó en estado failed.
     * POST /admin/facturacion/complemento/{id}/reemitir
     */
    public function reemitirComplemento($id)
    {
        $shop = auth()->user()->shop;

        $complemento = CfdiPagoComplemento::where('id', $id)->where('shop_id', $shop->id)->first();
        if (!$complemento) {
            return response()->json(['ok' => false, 'message' => 'Complemento no encontrado'], 404);
        }

        $service = new CfdiComplementoPagoService();
        $result = $service->reemitirPendiente($id);

        return response()->json([
            'ok' => $result['ok'],
            'message' => $result['message'],
            'complemento' => $result['complemento'] ?? null,
        ], $result['ok'] ? 200 : 422);
    }
}
