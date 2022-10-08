<?php

namespace App\Http\Controllers;

use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderPayments;

class PurchaseOrderController extends Controller
{
    public function getAll(Request $request)
    {
        $filtro_status = $request->status;
        $filtro_buscar = isset($request->buscar)?trim($request->buscar):'';

        if($filtro_status=='TODOS'){
            $purchase_orders = PurchaseOrder::with('partialPayments')
                            ->with('supplier')
                            ->whereHas('supplier', function (Builder $query) use($filtro_buscar) {
                                $query->where('name', 'like', '%'.$filtro_buscar.'%');
                            })
                            ->orderBy('id','desc')
                            ->paginate(10);

        }else{
            $purchase_orders = PurchaseOrder::with('partialPayments')
                            ->with('supplier')
                            ->whereHas('supplier', function (Builder $query) use($filtro_buscar) {
                                $query->where('name', 'like', '%'.$filtro_buscar.'%');
                            })
                            ->where('status',$filtro_status)
                            ->orderBy('id','desc')
                            ->paginate(10);
        }
        return $purchase_orders;
    }//.index

    public function store(Request $request)
    {
        $po = $request->purchase_order;
        $date_today     = Carbon::now();
        //formateamos la fecha de vencimiento
        $ff =Carbon::parse($po['expiration']);
        $exp=Carbon::createFromFormat('Y-m-d H:i:s',$ff)->format('Y-m-d');
        //Guardamos todos los datos de la PO, deben de venir desde la APP con algun valor
        $purchase_order = new PurchaseOrder();
        $purchase_order->supplier_id = $po['supplier_id'];
        $purchase_order->status      = $po['status'];
        $purchase_order->expiration  = $exp;
        $purchase_order->observation = $po['observation'];
        $purchase_order->payment     = $po['payment'];
        $purchase_order->total       = $po['total'];
        $purchase_order->save();

        //Guardaremos el detalle de la orden
        $details = json_decode($request->detail);

        foreach($details as $data){
            $detail = new PurchaseOrderDetail();
            $detail->purchase_order_id  = $purchase_order->id;
            $detail->product_id  = $data->id;
            $detail->descripcion = $data->descripcion;
            $detail->qty         = $data->qty;
            $detail->price       = $data->price;
            $detail->subtotal    = $data->subtotal;
            $detail->save();
        }//.foreach

        return response()->json([
            'ok'=>true,
            'purchase_order' => $purchase_order,
        ]);
    }
}
