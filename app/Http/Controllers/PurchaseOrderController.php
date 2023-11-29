<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PDF;


class PurchaseOrderController extends Controller
{
    public function getAll(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $filtro_status = $request->status;
        $filtro_pagadas = $request->filtro_pagadas;
        $filtro_buscar = isset($request->buscar)?trim($request->buscar):'';

        $purchase_orders = PurchaseOrder::with('partialPayments')
                        ->with('shop')
                        ->with('supplier')
                        ->whereHas('supplier', function (Builder $query) use($filtro_buscar) {
                            $query->where('name', 'like', '%'.$filtro_buscar.'%');
                        })
                        ->where('shop_id',$shop->id)
                        ->where('payable',$filtro_pagadas)
                        ->when( $request->status!='TODOS', function ($query) use($request) {
                            return $query->where('status',$request->status);
                        })
                        ->orderBy('id','desc')
                        ->paginate(10);

        /*if($filtro_status=='TODOS'){
            $purchase_orders = PurchaseOrder::with('partialPayments')
                            ->with('shop')
                            ->with('supplier')
                            ->whereHas('supplier', function (Builder $query) use($filtro_buscar) {
                                $query->where('name', 'like', '%'.$filtro_buscar.'%');
                            })
                            ->where('shop_id',$shop->id)
                            ->orderBy('id','desc')
                            ->paginate(10);

        }else{
            $purchase_orders = PurchaseOrder::with('partialPayments')
                            ->with('supplier')
                            ->with('shop')
                            ->whereHas('supplier', function (Builder $query) use($filtro_buscar) {
                                $query->where('name', 'like', '%'.$filtro_buscar.'%');
                            })
                            ->where('shop_id',$shop->id)
                            ->where('status',$filtro_status)
                            ->orderBy('id','desc')
                            ->paginate(10);
        }*/
        return $purchase_orders;
    }//.index

    public function store(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        date_default_timezone_set('America/Mexico_City');
        $po = $request->purchase_order;
        $date_today     = Carbon::now();
        //formateamos la fecha de vencimiento
        $fecha_exp_request =Carbon::parse($po['expiration']);
        $expiration=Carbon::createFromFormat('Y-m-d H:i:s',$fecha_exp_request)->format('Y-m-d');
        //Guardamos todos los datos de la PO, deben de venir desde la APP con algun valor
        $purchase_order = new PurchaseOrder();
        $purchase_order->shop_id = $shop->id;
        $purchase_order->supplier_id = $po['supplier_id'];
        $purchase_order->status      = $po['status'];
        $purchase_order->expiration  = $expiration;
        $purchase_order->observation = $po['observation'];
        $purchase_order->payment     = $po['payment'];
        $purchase_order->total       = $po['total'];
        $purchase_order->payable     = $po['payable'];
        $purchase_order->save();

        //Guardaremos el detalle de la orden
        $details = json_decode($request->detail);

        foreach($details as $data){
            $detail = new PurchaseOrderDetail();
            $detail->purchase_order_id  = $purchase_order->id;
            $detail->product_id  = $data->id;
            $detail->description = $data->description;
            $detail->qty         = $data->qty;
            $detail->price       = $data->price;
            $detail->subtotal    = $data->subtotal;
            $detail->save();
        }//.foreach

        $po = PurchaseOrder::with('partialPayments')
                    ->with('supplier')
                    ->with('shop')
                    ->findOrFail($purchase_order->id);

        return response()->json([
            'ok'=>true,
            'purchase_order' => $po,
        ]);
    }//.store()

    public function update(Request $request)
    {
        date_default_timezone_set('America/Mexico_City');
        $po = $request->purchase_order;
        $date_today     = Carbon::now();
        //formateamos la fecha de vencimiento
        $fecha_exp_request =Carbon::parse($po['expiration']);
        $expiration=Carbon::createFromFormat('Y-m-d H:i:s',$fecha_exp_request)->format('Y-m-d');
        //Guardamos todos los datos de la PO, deben de venir desde la APP con algun valor
        $purchase_order = PurchaseOrder::findOrFail($po['purchase_order_id']);
        $purchase_order->supplier_id = $po['supplier_id'];
        $purchase_order->status      = $po['status'];
        $purchase_order->expiration  = $expiration;
        $purchase_order->observation = $po['observation'];
        $purchase_order->payment     = $po['payment'];
        $purchase_order->total       = $po['total'];
        $purchase_order->payable       = $po['payable'];
        $purchase_order->save();

        PurchaseOrderDetail::where('purchase_order_id', $purchase_order->id)->delete();

        //Guardaremos el detalle de la orden
        $details = json_decode($request->detail);
        foreach($details as $data){
            $detail = new PurchaseOrderDetail();
            $detail->purchase_order_id  = $purchase_order->id;
            $detail->product_id  = $data->product_id;
            $detail->description = $data->description;
            $detail->qty         = $data->qty;
            $detail->price       = $data->price;
            $detail->subtotal    = $data->subtotal;
            $detail->save();
        }//.foreach

        $po = PurchaseOrder::with('partialPayments')
                    ->with('supplier')
                    ->with('shop')
                    ->findOrFail($purchase_order->id);

        return response()->json([
            'ok'=>true,
            'purchase_order' => $po,
        ]);
    }//.update()

    public function updateStatus(Request $request){
        $purchase_order = PurchaseOrder::findOrFail($request->purchase_order_id);
        $purchase_order->status=$request->new_status;
        $purchase_order->save();
        return response()->json([
                'ok'=>true,
                'purchase_order' => $purchase_order,
        ]);
    }

    public function updateCompletePurchaseOrder(Request $request){

        $purchase_order = PurchaseOrder::findOrFail($request->purchase_order_id);
        $purchase_order->status = 'COMPLETA';
        $purchase_order->expiration = null;
        $purchase_order->save();

        //Al pasar la PO a completa, sumamos el stock
        $detail = PurchaseOrderDetail::where('purchase_order_id',$purchase_order->id)->get();
        foreach($detail as $data){
            //solo con los items que sean productos
            $product   = Product::find($data->product_id);
            $new_stock = $product->stock + $data->qty;
            $product->stock = $new_stock;
            $product->cost = $data->price;
            $product->save();
        }

        return response()->json([
                'ok'=>true,
                'purchase_order' => $purchase_order,
        ]);

    }//.updateCompletePurchaseOrder()

    public function cancel(Request $request){
        $purchase_order = PurchaseOrder::findOrFail($request->purchase_order_id);
        $purchase_order->status='CANCELADA';
        $purchase_order->save();
        return response()->json([
                'ok'=>true,
                'purchase_order' => $purchase_order,
        ]);
    }//.cancel()

    public function updatePorpagarpagar(Request $request){
        $purchase_order = PurchaseOrder::findOrFail($request->purchase_order_id);
        $purchase_order->payable=$request->val_porpagar;
        $purchase_order->save();
        return response()->json([
                'ok'=>true,
                'purchase_order' => $purchase_order,
        ]);
    }//.updatePorpagarpagar()

    private function removeSpecialChar($str)
    {
        $res = preg_replace('/[@\.\;\" "]+/', '_', $str);
        return $res;
    }

    public function printPurchaseOrder(Request $request){
        if(!isset($request->id)) return null;
        /*ESTE DEBE LLEGAR POR REQUEST O OBTENERSE DEL RECEIPT*/
        //$shop_id = 1;
        /*....*/
        $id= $request->id;
        $name_file = $this->removeSpecialChar($request->name_file);
        $purchase_order = PurchaseOrder::with('partialPayments')
                            ->with('detail')
                            ->with('shop')
                            ->with('supplier')
                            ->findOrFail($id);

        //$shop = Shop::findOrFail($shop_id);

        $pdf = PDF::loadView('purchase_order_pdf',['purchase_order'=>$purchase_order]);
        return $pdf->stream($name_file.'.pdf',array("Attachment" => false));
    }
}
