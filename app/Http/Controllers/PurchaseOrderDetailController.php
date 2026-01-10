<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrderDetail;
use Illuminate\Http\Request;

class PurchaseOrderDetailController extends Controller
{
    public function getDetail(Request $request)
    {
        $detail = PurchaseOrderDetail::where('purchase_order_id',$request->purchase_order_id)->get();
        return response()->json([
            'ok'=>true,
            'detail' => $detail,
        ]);
    }
}
