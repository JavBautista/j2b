<?php

namespace App\Http\Controllers;

use App\Models\Product;
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

    public function getgetStockCurrentDetail(Request $request)
    {
        $detail = ReceiptDetail::where('receipt_id',$request->receipt_id)->get();
        $detail_current_stock=[];
        foreach($detail as $data){
            if($data->type=='product'){
                $prod = Product::findOrFail($data->product_id);
                $tmp=['product_id'=>$prod->id,'stock'=>$prod->stock];
                array_push($detail_current_stock,$tmp);
            }
        }

        return response()->json([
            'ok'=>true,
            'detail_current_stock' => $detail_current_stock,
        ]);
    }//getgetStockCurrentDetail()

    /*public function getDetail(Request $request)
    {
        $detail = ReceiptDetail::where('receipt_id',$request->receipt_id)->get();
        return response()->json([
            'ok'=>true,
            'detail' => $detail,
        ]);
    }*/

}
