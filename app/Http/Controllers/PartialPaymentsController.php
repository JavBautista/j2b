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

        $receipt = Receipt::findOrFail($request->receipt_id);
        $receipt->received = $suma_pagos;
        if($suma_pagos >= $receipt->total){
            $receipt->finished=1;
        }
        $receipt->save();

        return response()->json([
            'ok'=>true,
            'receipt' => $receipt,
            'payment' => $payment,
        ]);
    }
}
