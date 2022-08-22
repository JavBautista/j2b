<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;
use App\Models\ReceiptDetail;
use App\Models\RentDetail;
use App\Models\PartialPayments;
use Illuminate\Support\Carbon;
use PDF;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $client_id = $request->client_id;
        $receipts = Receipt::with('detail')
                        ->where('client_id',$client_id)
                        ->orderBy('id','desc')
                        ->paginate(10);
        return $receipts;
    }//.index

    private function removeSpecialChar($str)
    {
        $res = preg_replace('/[@\.\;\" "]+/', '_', $str);
        return $res;
    }
    public function printReceiptRent(Request $request){
        if(!isset($request->id)) return null;
        $id= $request->id;
        $name_file = $this->removeSpecialChar($request->name_file);
        $receipt = Receipt::with('partialPayments')
                            ->with('detail')
                            ->with('client')
                            ->findOrFail($id);

        $pdf = PDF::loadView('receipt_rent_pdf',['receipt'=>$receipt]);
        return $pdf->stream($name_file.'.pdf',array("Attachment" => false));
    }


    public function getAll(Request $request)
    {
        $filtro_status = $request->status;
        $filtro_buscar = $request->buscar;

        if($filtro_status=='TODOS'){
            $receipts = Receipt::with('partialPayments')
                            ->with('client')
                            ->orderBy('id','desc')
                            ->paginate(10);

        }else{
            $receipts = Receipt::with('partialPayments')
                            ->where('status',$filtro_status)
                            ->with('client')
                            ->orderBy('id','desc')
                            ->paginate(10);
        }
        return $receipts;
    }//.index

    public function store(Request $request)
    {
        $rcp = $request->receipt;
        $date_today     = Carbon::now();

        //calculamos si el pago recibido es mayor o igual que el total de la nota
        //finalizamos la nota, si no se tomara como un abono
        $finished = ($rcp['received'] >= $rcp['total'])?1:0;

        $receipt = new Receipt();
        $receipt->client_id   = $rcp['client_id'];
        $receipt->rent_id   = $rcp['rent_id'];
        $receipt->type        = $rcp['type'];
        $receipt->description = $rcp['description'];
        $receipt->observation = $rcp['observation'];
        $receipt->status      = $rcp['status'];
        $receipt->payment     = $rcp['payment'];
        $receipt->subtotal    = $rcp['subtotal'];
        $receipt->discount    = $rcp['discount'];
        $receipt->received    = $rcp['received'];
        $receipt->total       = $rcp['total'];
        $receipt->iva         = $rcp['iva'];
        $receipt->finished    = $finished;
        $receipt->discount_concept = $rcp['discount_concept'];
        $receipt->save();

        //si la nota no es finalizada porque el pago es menor que total
        //guardaremos el abono como un pago parcial de la nota
        if(!$finished){
            if($receipt->received>0){
                $partial= new PartialPayments();
                $partial->receipt_id = $receipt->id;
                $partial->amount = $receipt->received;
                $partial->payment_date = $date_today;
                $partial->save();
            }
        }

        $details = json_decode($request->detail);

        foreach($details as $data){
            $detail = new ReceiptDetail();
            $detail->receipt_id  = $receipt->id;
            $detail->product_id  = $data->id;
            $detail->descripcion = $data->name;
            $detail->qty         = $data->qty;
            $detail->price       = $data->cost;
            $detail->subtotal    = $data->subtotal;
            $detail->save();
        }

        $eq_new_counts = json_decode($request->eq_new_counts);
        foreach($eq_new_counts as $enc){
            $rent_equipo = RentDetail::findOrFail( $enc->equipo_id);
            if($rent_equipo->monochrome)
                $rent_equipo->counter_mono  =  $enc->equipo_new_count_monochrome;
            if($rent_equipo->color)
                $rent_equipo->counter_color =  $enc->equipo_new_count_color;

            $rent_equipo->save();
        }

        return response()->json([
            'ok'=>true,
            'receipt' => $receipt,
        ]);
    }

    public function updateStatus(Request $request){
        $receipt = Receipt::findOrFail($request->receipt_id);
        $receipt->status=$request->new_status;
        $receipt->save();
        return response()->json([
                'ok'=>true,
                'receipt' => $receipt,
        ]);
    }

    public function updateInfo(Request $request){
        $rcp = $request->receipt;

        $receipt = Receipt::findOrFail($rcp['id']);
        $receipt->status      = $rcp['status'];
        $receipt->payment     = $rcp['payment'];
        $receipt->subtotal    = $rcp['subtotal'];
        $receipt->discount    = $rcp['discount'];
        $receipt->received    = $rcp['received'];
        $receipt->total       = $rcp['total'];
        $receipt->description = $rcp['description'];
        $receipt->observation = $rcp['observation'];
        $receipt->save();

        $details = json_decode($request->detail);

        foreach($details as $data){
            $detail = ReceiptDetail::findOrFail($data->id);
            $detail->qty         = $data->qty;
            $detail->price       = $data->price;
            $detail->subtotal    = $data->subtotal;
            $detail->save();
        }

        return response()->json([
                'ok'=>true,
                'receipt' => $receipt,
        ]);
    }


    public function test(Request $reques){
        /*
        //$var = "[{"cost":52,"qty":1,"name":"Luis"},{"cost":51,"qty":1,"name":"Luigi Bros"}]";
        //$var = json_decode($reques->detail);
        $var = json_decode($reques->detail);
        foreach($var as $detail){
            echo $detail->name.', '.$detail->qty.'<br>';
        }*/
    }

    public function delete(Request $request)
    {
        ReceiptDetail::where('receipt_id', $request->id)->delete();
        PartialPayments::where('receipt_id', $request->id)->delete();

        $receipt=Receipt::destroy($request->id);
        return response()->json([
            'ok'=>true
        ]);
    }
}
