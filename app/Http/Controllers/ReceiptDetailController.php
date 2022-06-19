<?php

namespace App\Http\Controllers;

use App\Models\ReceiptDetail;
use Illuminate\Http\Request;

class ReceiptDetailController extends Controller
{

    public function store(Request $request)
    {
        $detail = new ReceiptDetail();
        $detail->receipt_id = $request->receipt_id;
        $detail->descripcion = $request->descripcion;
        $detail->qty = $request->qty;
        $detail->price = $request->price;
        $detail->subtotal = $request->subtotal;
    }

    public function getDetail(Request $request)
    {
        $detail = ReceiptDetail::where('receipt_id',$request->receipt_id)->get();
        return response()->json([
            'ok'=>true,
            'detail' => $detail,
        ]);
    }
}
