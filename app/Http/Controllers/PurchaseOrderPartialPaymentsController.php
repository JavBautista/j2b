<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPartialPayments;

class PurchaseOrderPartialPaymentsController extends Controller
{
    public function store(Request $request)
    {
        $date_today     = Carbon::now();
        $payment= new PurchaseOrderPartialPayments();
        $payment->purchase_order_id    = $request->purchase_order_id;
        $payment->amount        = $request->amount;
        $payment->payment_date  = $date_today;
        $payment->save();

        $pagos = PurchaseOrderPartialPayments::where('purchase_order_id',$request->purchase_order_id)->get();

        $suma_pagos=0;
        foreach ($pagos as $data) {
            $suma_pagos+= $data['amount'];
        }

        $purchase_order = PurchaseOrder::with('partialPayments')->findOrFail($request->purchase_order_id);

        //Ver si cuando la suma de pagos es mayor o igual que el total se cambia el estatus de PO
        /*
        if($suma_pagos >= $purchase_order->total){
            $purchase_order->status='COMPLETA';
            $purchase_order->expiration = null;
        }
        $purchase_order->save();
        */

        return response()->json([
            'ok'=>true,
            'purchase_order' => $purchase_order
        ]);
    }
}
