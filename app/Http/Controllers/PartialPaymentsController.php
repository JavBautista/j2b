<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

use App\Models\Receipt;
use App\Models\PartialPayments;
use App\Models\CfdiPagoComplemento;
use App\Services\Facturacion\CfdiComplementoPagoService;

class PartialPaymentsController extends Controller
{
    /**
     * Agregar pago parcial/abono a una nota
     * Documentación: j2b-app/xdev/ventas/PLAN_CENTRALIZACION_PAGOS.md
     */
    public function store(Request $request)
    {
        $date_today = Carbon::now();

        // Validación campos opcionales por-abono (Pagos 2.0)
        $request->validate([
            'receipt_id'            => 'required|integer|exists:receipts,id',
            'amount'                => 'required|numeric|min:0.01',
            'payment_date'          => 'nullable|date',
            'payment_method'        => 'nullable|string|in:01,02,03,04,05,06,28,29,99',
            'shop_bank_account_id'  => 'nullable|integer|exists:shop_bank_accounts,id',
            'bank_ord_code'         => 'nullable|string|max:10',
            'cta_ordenante'         => ['nullable','string','max:50','regex:/^[A-Z0-9_]{10,50}$/i'],
            'is_foreign_bank_ord'   => 'nullable|boolean',
            'num_operacion'         => 'nullable|string|max:100',
        ]);

        // Obtener receipt y suma actual ANTES del nuevo pago
        $receipt = Receipt::with('partialPayments')->findOrFail($request->receipt_id);
        $suma_actual = $receipt->partialPayments->sum('amount');

        // Fecha real del pago (FechaPago del complemento PPD). Valida no-futura y no-anterior a la factura.
        $fecha_pago = \App\Services\Pagos\PaymentDateResolver::resolver($request->payment_date, $receipt);

        // Sobrepago: si el abono supera lo que falta para liquidar, el excedente va al saldo a favor del cliente.
        $saldo_pendiente = round($receipt->total - $suma_actual, 2);
        $abono_amount    = round((float) $request->amount, 2);
        $excedente       = 0.0;
        if ($saldo_pendiente > 0 && $abono_amount > $saldo_pendiente) {
            $excedente    = round($abono_amount - $saldo_pendiente, 2);
            $abono_amount = $saldo_pendiente; // la nota se salda exacto; el resto va a saldo a favor
        }

        // Calcular nueva suma después de este pago
        $nueva_suma = round($suma_actual + $abono_amount, 2);

        // Determinar tipo de pago: 'liquidacion' si completa, 'abono' si no
        $payment_type = ($nueva_suma >= $receipt->total) ? 'liquidacion' : 'abono';

        // Crear el pago
        $payment = new PartialPayments();
        $payment->receipt_id = $request->receipt_id;
        $payment->amount = $abono_amount;
        $payment->payment_type = $payment_type;
        $payment->payment_date = $fecha_pago;
        $payment->payment_method = $request->payment_method ?? '99';
        $payment->shop_bank_account_id = $request->shop_bank_account_id;
        $payment->bank_ord_code = $request->bank_ord_code;
        $payment->cta_ordenante = $request->cta_ordenante ? strtoupper($request->cta_ordenante) : null;
        $payment->is_foreign_bank_ord = (bool) $request->is_foreign_bank_ord;
        $payment->num_operacion = $request->num_operacion;
        $payment->save();

        // Actualizar receipt
        $receipt->received = $nueva_suma;
        if($nueva_suma >= $receipt->total){
            $receipt->finished = 1;
            $receipt->status = Receipt::STATUS_PAGADA;
            if($receipt->credit){
                $receipt->credit = 0;
                $receipt->credit_completed = 1;
            }
        }
        $receipt->save();

        // Excedente del abono → saldo a favor del cliente (cuenta corriente).
        if ($excedente > 0 && $receipt->client) {
            app(\App\Services\ClientAccountService::class)->registrarMovimiento(
                $receipt->client,
                \App\Models\ClientAccountMovement::TYPE_SOBREPAGO_NOTA,
                $excedente,
                [
                    'reference'   => $receipt,
                    'description' => 'Excedente del abono a la nota ' . ($receipt->folio ?? $receipt->id),
                    'created_by'  => optional($request->user())->id,
                ]
            );
        }

        // Hook PPD: emitir complemento de pago si la nota tiene factura PPD vigente.
        $complementoResult = $this->emitirComplementoSiAplica($receipt, $payment);

        // Recargar para devolver con todos los pagos
        $receipt->load('partialPayments');

        return response()->json([
            'ok' => true,
            'receipt' => $receipt,
            'complemento' => $complementoResult,
            'excedente_saldo_favor' => $excedente > 0 ? $excedente : null,
            'account_balance' => ($excedente > 0 && $receipt->client) ? (float) $receipt->client->account_balance : null,
        ]);
    }

    protected function emitirComplementoSiAplica(Receipt $receipt, PartialPayments $abono): ?array
    {
        $invoice = $receipt->cfdiInvoice()->first();

        if (!$invoice) return null;

        if ($invoice->metodo_pago === 'PUE') {
            Log::warning('Abono registrado en nota facturada como PUE — sin complemento', [
                'receipt_id' => $receipt->id,
                'partial_payment_id' => $abono->id,
                'invoice_uuid' => $invoice->uuid,
            ]);
            return null;
        }

        if ($invoice->metodo_pago !== 'PPD' || $invoice->status !== 'vigente') {
            Log::warning('Abono registrado pero la factura no es PPD vigente — sin complemento', [
                'receipt_id' => $receipt->id,
                'partial_payment_id' => $abono->id,
                'invoice_uuid' => $invoice->uuid,
                'invoice_metodo_pago' => $invoice->metodo_pago,
                'invoice_status' => $invoice->status,
            ]);
            return null;
        }

        $numParcialidad = $invoice->complementos()
            ->where('status', CfdiPagoComplemento::STATUS_VIGENTE)
            ->count() + 1;

        $service = new CfdiComplementoPagoService();
        $result = $service->emitir($receipt, $abono, $numParcialidad);

        if (!$result['ok']) {
            Log::warning('Complemento de pago falló al registrar abono', [
                'receipt_id' => $receipt->id,
                'partial_payment_id' => $abono->id,
                'invoice_uuid' => $invoice->uuid,
                'error' => $result['message'],
            ]);
        }

        return ['ok' => $result['ok'], 'message' => $result['message']];
    }

    /**
     * Fase 2 — Aplicar saldo a favor del cliente como abono de una nota.
     * Espejo de Admin\ReceiptsController::aplicarSaldoFavor (web). Genera un PartialPayments marcado
     * (payment_method 99 / num_operacion SALDO-FAVOR) + un movimiento aplicacion_venta (−) en la cuenta.
     */
    public function applyBalance(Request $request, $id)
    {
        $user = $request->user();
        $shop = $user->shop;

        $receipt = Receipt::where('shop_id', $shop->id)
            ->with('partialPayments', 'client')
            ->findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Guard fiscal (D1): aplicar saldo a una nota facturada requiere CFDI de anticipos (fuera de alcance).
        if ($receipt->cfdiInvoice()->first()) {
            return response()->json([
                'ok'      => false,
                'message' => 'Esta nota está facturada. Aplicar saldo a favor a notas facturadas requiere CFDI de anticipos (no disponible aún). Usa un abono normal.',
            ], 422);
        }

        $client = $receipt->client;
        if (!$client) {
            return response()->json([
                'ok'      => false,
                'message' => 'La nota no tiene cliente asignado; no se puede aplicar saldo a favor.',
            ], 422);
        }

        $monto = round((float) $request->amount, 2);

        // Tope 1: el adeudo de la nota (sin sobrepago: el saldo a favor no se sobre-aplica).
        $suma_actual     = $receipt->partialPayments->sum('amount');
        $saldo_pendiente = round($receipt->total - $suma_actual, 2);
        if ($saldo_pendiente <= 0) {
            return response()->json([
                'ok'      => false,
                'message' => 'La nota ya está saldada.',
            ], 422);
        }
        if ($monto > $saldo_pendiente) {
            return response()->json([
                'ok'      => false,
                'message' => 'El monto excede el adeudo de la nota ($' . number_format($saldo_pendiente, 2) . ').',
            ], 422);
        }

        // Tope 2: el saldo a favor disponible del cliente.
        if (round((float) $client->account_balance, 2) < $monto) {
            return response()->json([
                'ok'      => false,
                'message' => 'Saldo a favor insuficiente. Disponible: $' . number_format((float) $client->account_balance, 2) . '.',
            ], 422);
        }

        $nueva_suma   = round($suma_actual + $monto, 2);
        $payment_type = ($nueva_suma >= $receipt->total) ? 'liquidacion' : 'abono';

        \Illuminate\Support\Facades\DB::transaction(function () use ($receipt, $client, $monto, $payment_type, $nueva_suma, $user) {
            $payment = new PartialPayments();
            $payment->receipt_id     = $receipt->id;
            $payment->amount         = $monto;
            $payment->payment_type   = $payment_type;
            $payment->payment_date   = Carbon::today()->toDateString();
            $payment->payment_method = '99';            // operativo: no es pago bancarizado
            $payment->num_operacion  = 'SALDO-FAVOR';   // marcador legible del origen
            $payment->save();

            $receipt->received = $nueva_suma;
            if ($nueva_suma >= $receipt->total) {
                $receipt->finished = 1;
                $receipt->status   = Receipt::STATUS_PAGADA;
                if ($receipt->credit) {
                    $receipt->credit           = 0;
                    $receipt->credit_completed = 1;
                }
            }
            $receipt->save();

            // Consume el saldo a favor (−). Vinculado al abono para poder revertirlo si se elimina.
            app(\App\Services\ClientAccountService::class)->registrarMovimiento(
                $client,
                \App\Models\ClientAccountMovement::TYPE_APLICACION_VENTA,
                -$monto,
                [
                    'reference'   => $payment,
                    'description' => 'Aplicado a la nota ' . ($receipt->folio ?? $receipt->id),
                    'created_by'  => $user->id,
                ]
            );
        });

        $receipt->load('partialPayments', 'client');

        return response()->json([
            'ok'              => true,
            'receipt'         => $receipt,
            'account_balance' => (float) $receipt->client->account_balance,
        ]);
    }

    public function delete(Request $request)
    {
        $user = $request->user();

        $payment = PartialPayments::findOrFail($request->id);

        // Guard: no se puede eliminar pagos de una nota con CFDI vigente
        $receipt = Receipt::with('client')->findOrFail($payment->receipt_id);
        if($receipt->is_tax_invoiced){
            return response()->json([
                'ok'=>false,
                'message'=>'No se puede eliminar pagos de una nota facturada (CFDI vigente).'
            ], 422);
        }

        // Si este abono se pagó con saldo a favor (movimiento aplicacion_venta ligado), al eliminarlo
        // se devuelve el monto al cliente con un movimiento de reversa. El ledger es append-only.
        $aplicacion = \App\Models\ClientAccountMovement::where('type', \App\Models\ClientAccountMovement::TYPE_APLICACION_VENTA)
            ->whereMorphedTo('reference', $payment)
            ->first();

        \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $receipt, $aplicacion, $user) {
            if ($aplicacion && $receipt->client) {
                app(\App\Services\ClientAccountService::class)->registrarMovimiento(
                    $receipt->client,
                    \App\Models\ClientAccountMovement::TYPE_AJUSTE_MANUAL,
                    abs((float) $aplicacion->amount),
                    [
                        'reference'   => $receipt,
                        'description' => 'Reversa: se eliminó el abono con saldo a favor de la nota ' . ($receipt->folio ?? $receipt->id),
                        'created_by'  => $user->id,
                    ]
                );
            }

            $payment->delete();

            // Recalcular suma de pagos restantes y actualizar la nota
            $suma_pagos = PartialPayments::where('receipt_id', $receipt->id)->sum('amount');

            $receipt->received = $suma_pagos;
            if ($suma_pagos >= $receipt->total) {
                $receipt->finished = 1;
                $receipt->status = Receipt::STATUS_PAGADA;
            } else {
                $receipt->finished = 0;
                $receipt->status = Receipt::STATUS_POR_COBRAR;
            }
            $receipt->save();
        });

        $receipt->load('partialPayments', 'client');

        return response()->json([
            'ok' => true,
            'receipt' => $receipt,
            'account_balance' => $receipt->client ? (float) $receipt->client->account_balance : null,
        ]);
    }
}
