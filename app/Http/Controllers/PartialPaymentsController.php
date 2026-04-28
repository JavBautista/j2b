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

        // Obtener receipt y suma actual ANTES del nuevo pago
        $receipt = Receipt::with('partialPayments')->findOrFail($request->receipt_id);
        $suma_actual = $receipt->partialPayments->sum('amount');

        // Calcular nueva suma después de este pago
        $nueva_suma = $suma_actual + $request->amount;

        // Determinar tipo de pago: 'liquidacion' si completa, 'abono' si no
        $payment_type = ($nueva_suma >= $receipt->total) ? 'liquidacion' : 'abono';

        // Crear el pago
        $payment = new PartialPayments();
        $payment->receipt_id = $request->receipt_id;
        $payment->amount = $request->amount;
        $payment->payment_type = $payment_type;
        $payment->payment_date = $date_today;
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

        // Hook PPD: emitir complemento de pago si la nota tiene factura PPD vigente.
        $complementoResult = $this->emitirComplementoSiAplica($receipt, $payment);

        // Recargar para devolver con todos los pagos
        $receipt->load('partialPayments');

        return response()->json([
            'ok' => true,
            'receipt' => $receipt,
            'complemento' => $complementoResult,
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

    public function delete(Request $request)
    {
        $payment = PartialPayments::findOrFail($request->id);
        $receipt_id=$payment->receipt_id;

        // Guard: no se puede eliminar pagos de una nota con CFDI vigente
        $receipt_check = Receipt::findOrFail($receipt_id);
        if($receipt_check->is_tax_invoiced){
            return response()->json([
                'ok'=>false,
                'message'=>'No se puede eliminar pagos de una nota facturada (CFDI vigente).'
            ], 422);
        }

        $payment->delete();

        //Una vez eliminado el pago, volvemos a sumar todos los pagos actuales que quedaron para actualizar el total de la nota
        $pagos = PartialPayments::where('receipt_id',$receipt_id)->get();

        $suma_pagos=0;
        foreach ($pagos as $data) $suma_pagos+= $data['amount'];

        //Obtenmos el recibo para actualizar los datos del recibo
        $receipt = Receipt::with('partialPayments')->findOrFail($receipt_id);
        $receipt->received = $suma_pagos;
        if($suma_pagos >= $receipt->total){
            $receipt->finished=1;
            $receipt->status=Receipt::STATUS_PAGADA;
        }else{
            $receipt->finished=0;
            $receipt->status=Receipt::STATUS_POR_COBRAR;
        }
        $receipt->save();

        return response()->json([
            'ok'=>true,
            'receipt'=>$receipt,
        ]);
    }
}
