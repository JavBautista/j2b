<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use App\Models\Receipt;
use App\Models\PartialPayments;

class PartialPaymentsController extends Controller
{
    public function store(Request $request)
    {
        $date_today     = Carbon::now();
        $payment= new PartialPayments();
        $payment->receipt_id    = $request->receipt_id;
        $payment->amount        = $request->amount;
        $payment->payment_date  = $date_today;
        $payment->save();

        $pagos = PartialPayments::where('receipt_id',$request->receipt_id)->get();

        $suma_pagos=0;
        foreach ($pagos as $data) {
            $suma_pagos+= $data['amount'];
        }

        $receipt = Receipt::with('partialPayments')->findOrFail($request->receipt_id);
        $receipt->received = $suma_pagos;
        if($suma_pagos >= $receipt->total){
            $receipt->finished=1;
            $receipt->status='PAGADA';
        }
        $receipt->save();

        return response()->json([
            'ok'=>true,
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
