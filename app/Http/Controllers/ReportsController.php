<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;


class ReportsController extends Controller
{
    public function mensual(Request $request){
        $mm   = ($request->month<10)?'0'.$request->month:$request->month;
        $yyyy = $request->year;

        $request_fecha = $yyyy.'-'.$mm.'-01';
        $fix_date = Carbon::parse($request_fecha);
        $start = $fix_date->copy()->startOfMonth()->format('Y-m-d');
        $end   = $fix_date->copy()->endOfMonth()->format('Y-m-d');

        $receipts = Receipt::with('partialPayments')
                            ->whereBetween('created_at',[$start,$end])
                            ->where('quotation',0)
                            ->where('status','<>','CANCELADA')
                            ->orderBy('created_at','desc')
                            ->get();

        $count_receipst = $receipts->count();
        $rentas=0;
        $ventas=0;
        $receipts_total=0;
        $pagadas=0;
        $por_cobrar=0;

        $abonos=0;
        $adeudos=0;

        foreach($receipts as $venta){
            $receipts_total+= $venta->total;

            if($venta->type=='venta'){
                $ventas++;
            }else{
                $rentas++;
            }

            if($venta->status=='PAGADA'){
                $pagadas+=$venta->total;
            }
            if($venta->status=='POR COBRAR'){
                $por_cobrar+=$venta->total;
            }

            foreach($venta->partialPayments as $pp){
                $abonos += $pp->amount;
            }
        }//foreach

        $adeudos= $receipts_total - $abonos;

        $receipts_total = number_format($receipts_total,2);
        $pagadas = number_format($pagadas,2);
        $por_cobrar = number_format($por_cobrar,2);
        $abonos = number_format($abonos,2);


        $adeudos= number_format($adeudos,2);

        $data=[
            'receipts_num'=>$count_receipst,
            'ventas'=>$ventas,
            'rentas'=>$rentas,
            'receipts_total'=>$receipts_total,
            'pagadas'=>$pagadas,
            'por_cobrar'=>$por_cobrar,
            'abonos'=>$abonos,
            'adeudos'=>$adeudos,
        ];
        return response()->json([
                'ok'=>true,
                'start'=>$start,
                'end' => $end,
                'data'=>$data
        ]);

    }

    public function clientesAdeudos(Request $request){

        $clientes = Client::where('active',1)->get();

        $receipts = Receipt::with('partialPayments')
                            ->where('quotation',0)
                            ->where('status','POR COBRAR')
                            ->orderBy('created_at','desc')
                            ->get();

        $data=[];
        foreach($clientes as $cl){
            $notas=0;
            $total=0;
            $abonos=0;
            $adeudo=0;
            foreach($receipts as $rcp){
                if($rcp->client_id==$cl->id){
                    $notas++;
                    $total+=$rcp->total;
                    foreach($rcp->partialPayments as $pp){
                        $abonos+=$pp->amount;
                    }
                }//if
            }//foreach2

            if($notas>0){
                $adeudo = $total - $abonos;
                $total = number_format($total,2);
                $abonos = number_format($abonos,2);
                $adeudo = number_format($adeudo,2);
                $data[]=[
                    'cliente'=>$cl->name,
                    'company'=>$cl->company,
                    'notas_por_cobrar'=>$notas,
                    'total'=>$total,
                    'abonos'=>$abonos,
                    'adeudo'=>$adeudo,
                ];
            }


        }//foreach1


        return response()->json([
                'ok'=>true,
                'data'=>$data
        ]);

    }
}
