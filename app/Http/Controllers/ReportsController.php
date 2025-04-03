<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\PurchaseOrder;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\Expense;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use App\Exports\IngresosExport;
use App\Exports\EgresosExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;


class ReportsController extends Controller
{
    public function mensual(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $mm   = ($request->month<10)?'0'.$request->month:$request->month;
        $yyyy = $request->year;

        $request_fecha = $yyyy.'-'.$mm.'-01';
        $fix_date = Carbon::parse($request_fecha);
        $start = $fix_date->copy()->startOfMonth()->format('Y-m-d');
        $end   = $fix_date->copy()->endOfMonth()->format('Y-m-d');

        $receipts = Receipt::with('partialPayments')
                            ->whereBetween('created_at',[$start,$end])
                            ->where('shop_id',$shop->id)
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

    }//mensual()

    public function rentasMensual(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $mm   = ($request->month<10)?'0'.$request->month:$request->month;
        $yyyy = $request->year;

        $request_fecha = $yyyy.'-'.$mm.'-01';
        $fix_date = Carbon::parse($request_fecha);
        $start = $fix_date->copy()->startOfMonth()->format('Y-m-d');
        $end   = $fix_date->copy()->endOfMonth()->format('Y-m-d');

        $receipts = Receipt::with('partialPayments')
                            ->where('shop_id',$shop->id)
                            ->whereBetween('created_at',[$start,$end])
                            ->where('type','renta')
                            ->where('quotation',0)
                            ->where('status','<>','CANCELADA')
                            ->orderBy('created_at','desc')
                            ->get();

        $count_receipst = $receipts->count();
        $rentas=0;
        $receipts_total=0;
        $pagadas=0;
        $por_cobrar=0;

        $abonos=0;
        $adeudos=0;

        foreach($receipts as $venta){
            $receipts_total+= $venta->total;
            $rentas++;
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

    }//rentasMensual()

    public function clientesAdeudos(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $clientes = Client::where('active',1)->get();

        $receipts = Receipt::with('partialPayments')
                            ->where('shop_id',$shop->id)
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

        $total_data = count($data);

        return response()->json([
                'ok'=>true,
                'data'=>$data,
                'total_data' => $total_data,
        ]);

    }//clientesAdeudos

    public function ingresosxFechas(Request $request){
        // Obtener usuario autenticado y su tienda
        $user = $request->user();
        $shop = $user->shop;

        // Validar que las fechas sean correctas
        $request->validate([
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        // Convertir fechas a formato Carbon para asegurar el formato correcto
        $fechaInicio = Carbon::parse($request->fechaInicio)->startOfDay();
        $fechaFin    = Carbon::parse($request->fechaFin)->endOfDay();

        // Obtener los recibos en el rango de fechas seleccionado
        $receipts = Receipt::with(['partialPayments', 'shop', 'detail', 'client'])
            ->where('shop_id', $shop->id)
            ->where('quotation', 0)
            ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION'])
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->where(function ($q) use ($fechaInicio, $fechaFin) {
                    // Recibos con pagos parciales en el rango
                    $q->whereHas('partialPayments', function ($subquery) use ($fechaInicio, $fechaFin) {
                        $subquery->whereBetween('created_at', [$fechaInicio, $fechaFin]);
                    });
                })->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                    // Recibos finalizados con fecha de creación en el rango
                    $q->where('finished', 1)
                      ->whereBetween('created_at', [$fechaInicio, $fechaFin]);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Inicializar variables
        $totalIngresos = 0;
        $ingresos = [];

        foreach ($receipts as $receipt) {
            $ingresoNota = 0; // Variable para acumular ingresos de cada nota

            $pagosFiltrados = collect(); 
            
            if ($receipt->finished && $receipt->created_at->between($fechaInicio, $fechaFin))  {
                // Si la nota está finalizada, el ingreso es el monto recibido al momento de la creación
                $ingresoNota = $receipt->received;
            } else {
                // Filtrar los pagos parciales que estén dentro del rango de fechas
                $pagosFiltrados = $receipt->partialPayments->filter(function ($pp) use ($fechaInicio, $fechaFin) {
                    return Carbon::parse($pp->created_at)->between($fechaInicio, $fechaFin);
                });

                // Sumar solo los pagos parciales dentro del rango de fechas
                $ingresoNota = $pagosFiltrados->sum('amount');
            }

            // Acumular ingresos totales
            $totalIngresos += $ingresoNota;

            // Agregar la entrada al reporte
            $ingresos[] = [
                'id' => $receipt->id, // ID de la nota
                'nombre' => $receipt->client->name,
                'folio' => $receipt->folio,
                'fecha' => $receipt->created_at->format('Y-m-d'),
                'descripcion' => $receipt->type,
                'monto' => (float) $ingresoNota,
                'detalle' => $pagosFiltrados->map(function ($pp) {
                    return [
                        'fecha' => $pp->created_at->format('Y-m-d'),
                        'monto' => (float) $pp->amount
                    ];
                }),
                'receipt' => $receipt
            ];
        }

        // Devolver la respuesta en formato JSON
        return response()->json([
            'ok' => true,
            'fechaInicio' => $fechaInicio->format('Y-m-d'),
            'fechaFin' => $fechaFin->format('Y-m-d'),
            'totalIngresos' => number_format($totalIngresos, 2, '.', ''),
            'ingresos' => $ingresos
        ]);
    }//.ingresosxFechas()

    public function descargarIngresosExcel(Request $request){
        // Obtener usuario autenticado y su tienda
        $user = $request->user();
        $shop = $user->shop;

        // Validar que las fechas sean correctas
        $request->validate([
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        // Generar archivo Excel
        $fileName = 'ingresos_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new IngresosExport($request->fechaInicio, $request->fechaFin, $shop), $fileName);
    }//.descargarIngresosExcel

    public function egresosxFechas(Request $request){
        // Obtener usuario autenticado y su tienda
        $user = $request->user();
        $shop = $user->shop;

        // Validar que las fechas sean correctas
        $request->validate([
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        // Convertir fechas a formato Carbon para asegurar el formato correcto
        $fechaInicio = Carbon::parse($request->fechaInicio)->startOfDay();
        $fechaFin    = Carbon::parse($request->fechaFin)->endOfDay();

        // Inicializar variables
        $totalEgresos = 0;
        $egresos = [];

        // 1. Egresos por PurchaseOrder

        // Obtener los recibos en el rango de fechas seleccionado
        // !Hey que ver si hay que filtrar por estatus o pagadas
        $purchases = PurchaseOrder::with(['partialPayments', 'shop', 'detail', 'supplier'])
            ->where('shop_id', $shop->id)
            ->whereHas('partialPayments', function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        

        foreach ($purchases as $purchase) {
            $egresoNota = 0; // Variable para acumular egresos de cada nota
            
            //Filtrar los pagos parciales que estén dentro del rango de fechas
            $pagosFiltrados = $purchase->partialPayments->filter(function ($pp) use ($fechaInicio, $fechaFin) {
                return Carbon::parse($pp->created_at)->between($fechaInicio, $fechaFin);
            });

            // Sumar solo los pagos parciales dentro del rango de fechas
            $egresoNota = $pagosFiltrados->sum('amount');
            
            // Acumular egresos totales
            $totalEgresos += $egresoNota;

            // Agregar la entrada al reporte
            $egresos[] = [
                'id' => $purchase->id, // ID de la nota
                'tipo' => 'purchase',
                'nombre' => $purchase->supplier->name,
                'folio' => $purchase->folio,
                'fecha' => $purchase->created_at->format('Y-m-d'),
                'descripcion' => $purchase->type,
                'monto' => (float) $egresoNota,
                'detalle' => $pagosFiltrados->map(function ($pp) {
                    return [
                        'fecha' => $pp->created_at->format('Y-m-d'),
                        'monto' => (float) $pp->amount
                    ];
                }),
                'purchase' => $purchase,
                'expense' => null
            ];
        }

        // 2. Egresos por Expenses
        $expenses = Expense::where('shop_id', $shop->id)
            ->where('status', 'PAGADO')
            ->whereBetween('date', [$fechaInicio, $fechaFin])
            ->orderBy('date', 'desc')
            ->get();

        foreach ($expenses as $expense) {
            $totalEgresos += $expense->total;

            $egresos[] = [
                'id' => $expense->id,
                'tipo' => 'expense',
                'nombre' => $expense->name,
                'folio' => null,
                'fecha' => Carbon::parse($expense->date)->format('Y-m-d'),
                'descripcion' => $expense->description ?? 'Gasto',
                'monto' => (float) $expense->total,
                'detalle' => collect([[
                    'fecha' => Carbon::parse($expense->date)->format('Y-m-d'),
                    'monto' => (float) $expense->total
                ]]),
                'expense' => $expense->toArray(),
                'purchase' => null,
            ];
        }

        $egresos = collect($egresos)->sortByDesc('fecha')->values()->all();

        // Devolver la respuesta en formato JSON
        return response()->json([
            'ok' => true,
            'fechaInicio' => $fechaInicio->format('Y-m-d'),
            'fechaFin' => $fechaFin->format('Y-m-d'),
            'totalEgresos' => number_format($totalEgresos, 2, '.', ''),
            'egresos' => $egresos
        ]);
    }//.egresosxFechas()

    public function descargarEgresosExcel(Request $request){
        // Obtener usuario autenticado y su tienda
        $user = $request->user();
        $shop = $user->shop;

        // Validar que las fechas sean correctas
        $request->validate([
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        // Generar archivo Excel
        $fileName = 'ingresos_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new EgresosExport($request->fechaInicio, $request->fechaFin, $shop), $fileName);
    }//.descargarEgresosExcel()

    public function diferenciasMensual(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        // Validar entrada
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2000',
        ]);

        // Generar fechas de inicio y fin del mes
        $mm   = ($request->month < 10) ? '0' . $request->month : $request->month;
        $yyyy = $request->year;
        $fechaBase = Carbon::parse($yyyy . '-' . $mm . '-01');
        $fechaInicio = $fechaBase->copy()->startOfMonth()->startOfDay();
        $fechaFin    = $fechaBase->copy()->endOfMonth()->endOfDay();

        // Convertir parámetro 'facturado' a booleano
        $soloFacturado = $request->has('facturado') ? filter_var($request->facturado, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;



        // Variables para acumular
        $totalIngresos = 0;
        $totalEgresos = 0;

        // (Aquí pondremos la lógica de ingresos y egresos...)

        // Obtener ingresos (receipts + partialPayments + received si está finalizado)
        $receipts = Receipt::with(['partialPayments'])
            ->where('shop_id', $shop->id)
            ->where('quotation', 0)
            ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION'])
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->where(function ($q) use ($fechaInicio, $fechaFin) {
                    // Recibos con pagos parciales en el rango
                    $q->whereHas('partialPayments', function ($subquery) use ($fechaInicio, $fechaFin) {
                        $subquery->whereBetween('created_at', [$fechaInicio, $fechaFin]);
                    });
                })->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                    // Recibos finalizados dentro del rango
                    $q->where('finished', 1)
                      ->whereBetween('created_at', [$fechaInicio, $fechaFin]);
                });
            });

        if (!is_null($soloFacturado)) {
            $receipts->where('is_tax_invoiced', $soloFacturado);
        }

        $receipts = $receipts->get();

        foreach ($receipts as $receipt) {
            $ingresoNota = 0;

            if ($receipt->finished && $receipt->created_at->between($fechaInicio, $fechaFin)) {
                $ingresoNota = $receipt->received;
            } else {
                // Sumar solo los pagos parciales dentro del rango de fechas
                $pagosFiltrados = $receipt->partialPayments->filter(function ($pp) use ($fechaInicio, $fechaFin) {
                    return Carbon::parse($pp->created_at)->between($fechaInicio, $fechaFin);
                });
                $ingresoNota = $pagosFiltrados->sum('amount');
            }

            $totalIngresos += $ingresoNota;
        }

        // EGRESOS: PurchaseOrders
        $purchaseOrders = PurchaseOrder::with('partialPayments')
            ->where('shop_id', $shop->id)
            ->whereHas('partialPayments', function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            });

        if (!is_null($soloFacturado)) {
            $purchaseOrders->where('is_tax_invoiced', $soloFacturado);
        }

        foreach ($purchaseOrders->get() as $purchase) {
            $pagosFiltrados = $purchase->partialPayments->filter(function ($pp) use ($fechaInicio, $fechaFin) {
                return Carbon::parse($pp->created_at)->between($fechaInicio, $fechaFin);
            });

            $totalEgresos += $pagosFiltrados->sum('amount');
        }

        // EGRESOS: Expenses
        $expenses = Expense::where('shop_id', $shop->id)
            ->where('status', 'PAGADO')
            ->whereBetween('date', [$fechaInicio, $fechaFin]);

        if (!is_null($soloFacturado)) {
            $expenses->where('is_tax_invoiced', $soloFacturado);
        }
        
        foreach ($expenses->get() as $expense) {
            $totalEgresos += $expense->total;
        }


        // Calcular diferencia
        $diferencia = $totalIngresos - $totalEgresos;

        return response()->json([
            'ok' => true,
            'fechaInicio' => $fechaInicio->format('Y-m-d'),
            'fechaFin' => $fechaFin->format('Y-m-d'),
            'ingresos' => number_format($totalIngresos, 2, '.', ''),
            'egresos'  => number_format($totalEgresos, 2, '.', ''),
            'diferencia' => number_format($diferencia, 2, '.', '')
        ]);
    }
    //.diferenciasMensual


}//.class ReportsController
