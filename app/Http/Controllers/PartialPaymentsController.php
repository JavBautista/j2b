<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use App\Models\Receipt;
use App\Models\PartialPayments;

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
            $receipt->status = 'PAGADA';
            if($receipt->credit){
                $receipt->credit = 0;
                $receipt->credit_completed = 1;
            }
        }
        $receipt->save();

        // Recargar para devolver con todos los pagos
        $receipt->load('partialPayments');

        return response()->json([
            'ok' => true,
            'receipt' => $receipt
        ]);
    }

    public function delete(Request $request)
    {
        $payment = PartialPayments::findOrFail($request->id);
        $receipt_id=$payment->receipt_id;
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
            $receipt->status='PAGADA';
        }else{
            $receipt->finished=0;
            $receipt->status='POR COBRAR';
        }
        $receipt->save();

        return response()->json([
            'ok'=>true,
            'receipt'=>$receipt,
        ]);
    }
}
