<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\PurchaseOrder;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\Expense;
use App\Models\PartialPayments;
use App\Models\PurchaseOrderPartialPayments;

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

    /**
     * Reporte de Ingresos por Fechas
     * SIMPLIFICADO: Todo ingreso está en partial_payments
     */
    public function ingresosxFechas(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $request->validate([
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        $fechaInicio = Carbon::parse($request->fechaInicio)->startOfDay();
        $fechaFin    = Carbon::parse($request->fechaFin)->endOfDay();

        // SIMPLIFICADO: Todo ingreso está en partial_payments
        $pagos = PartialPayments::with(['receipt.client', 'receipt.detail'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->whereHas('receipt', function ($q) use ($shop) {
                $q->where('shop_id', $shop->id)
                    ->where('quotation', 0)
                    ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Agrupar pagos por receipt_id para mostrar por nota
        $pagosPorReceipt = $pagos->groupBy('receipt_id');

        $totalIngresos = $pagos->sum('amount');
        $ingresos = [];

        foreach ($pagosPorReceipt as $receiptId => $pagosNota) {
            $receipt = $pagosNota->first()->receipt;
            $montoTotal = $pagosNota->sum('amount');

            $ingresos[] = [
                'id' => $receipt->id,
                'nombre' => $receipt->client->name ?? 'Sin cliente',
                'folio' => $receipt->folio,
                'fecha' => $pagosNota->first()->created_at->format('Y-m-d'),
                'descripcion' => $receipt->type,
                'monto' => (float) $montoTotal,
                'detalle' => $pagosNota->map(function ($pp) {
                    return [
                        'fecha' => $pp->created_at->format('Y-m-d'),
                        'monto' => (float) $pp->amount,
                        'tipo' => $pp->payment_type ?? 'abono'
                    ];
                })->values(),
                'receipt' => $receipt
            ];
        }

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

    

    //METODO CORRECTO DODNDE SE TOMABAN LOS PAGOS PARCIALES DEL PURCHASE COMO EGRESSOS
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
            ->where('active', 1)
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
    


    /**
     * Reporte de Diferencias Mensual (Ingresos - Egresos)
     * SIMPLIFICADO: Todo ingreso está en partial_payments
     */
    public function diferenciasMensual(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2000',
        ]);

        // Generar fechas de inicio y fin del mes
        $mm = str_pad($request->month, 2, '0', STR_PAD_LEFT);
        $fechaBase = Carbon::parse($request->year . '-' . $mm . '-01');
        $fechaInicio = $fechaBase->copy()->startOfMonth()->startOfDay();
        $fechaFin = $fechaBase->copy()->endOfMonth()->endOfDay();

        // Convertir parámetro 'facturado' a booleano
        $soloFacturado = $request->has('facturado')
            ? filter_var($request->facturado, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        // INGRESOS: Todo está en partial_payments
        $queryIngresos = PartialPayments::whereHas('receipt', function ($q) use ($shop, $soloFacturado) {
            $q->where('shop_id', $shop->id)
                ->where('quotation', 0)
                ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION']);
            if (!is_null($soloFacturado)) {
                $q->where('is_tax_invoiced', $soloFacturado);
            }
        })->whereBetween('created_at', [$fechaInicio, $fechaFin]);

        $totalIngresos = $queryIngresos->sum('amount');

        // EGRESOS: Pagos a proveedores
        $queryCompras = PurchaseOrderPartialPayments::whereHas('purchaseOrder', function ($q) use ($shop, $soloFacturado) {
            $q->where('shop_id', $shop->id);
            if (!is_null($soloFacturado)) {
                $q->where('is_tax_invoiced', $soloFacturado);
            }
        })->whereBetween('created_at', [$fechaInicio, $fechaFin]);

        $egresosCompras = $queryCompras->sum('amount');

        // EGRESOS: Gastos operativos
        $queryGastos = Expense::where('shop_id', $shop->id)
            ->where('status', 'PAGADO')
            ->where('active', 1)
            ->whereBetween('date', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')]);

        if (!is_null($soloFacturado)) {
            $queryGastos->where('is_tax_invoiced', $soloFacturado);
        }

        $egresosGastos = $queryGastos->sum('total');
        $totalEgresos = $egresosCompras + $egresosGastos;
        $diferencia = $totalIngresos - $totalEgresos;

        return response()->json([
            'ok' => true,
            'fechaInicio' => $fechaInicio->format('Y-m-d'),
            'fechaFin' => $fechaFin->format('Y-m-d'),
            'ingresos' => number_format($totalIngresos, 2, '.', ''),
            'egresos' => number_format($totalEgresos, 2, '.', ''),
            'diferencia' => number_format($diferencia, 2, '.', '')
        ]);
    }
    //.diferenciasMensual

    public function ventasUtilidad(Request $request){
        // Obtener usuario autenticado y su tienda
        $user = $request->user();
        $shop = $user->shop;

        // Validar que las fechas sean correctas
        $request->validate([
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        // Convertir fechas a formato Carbon
        $fechaInicio = Carbon::parse($request->fechaInicio)->startOfDay();
        $fechaFin    = Carbon::parse($request->fechaFin)->endOfDay();

        // Obtener receipt_detail con productos en el rango de fechas
        $receipts = Receipt::with(['detail.product.category', 'client'])
            ->where('shop_id', $shop->id)
            ->where('quotation', 0)
            ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->get();

        // Agrupar por producto/servicio
        $ventasAgrupadas = [];

        foreach ($receipts as $receipt) {
            foreach ($receipt->detail as $detail) {
                // Clave única por producto o servicio
                if ($detail->type === 'product' && $detail->product) {
                    $key = 'product_' . $detail->product->id;
                    $nombre = $detail->product->name;
                    $categoria = $detail->product->category->name ?? 'Sin categoría';
                    // Usar el costo guardado en el detalle (costo al momento de la venta)
                    $costo = $detail->cost ?? 0;
                    $imagen = $detail->product->image ?? null;
                } else {
                    // Es servicio
                    $key = 'service_' . md5($detail->descripcion);
                    $nombre = $detail->descripcion;
                    $categoria = 'Servicios';
                    $costo = $detail->cost ?? 0;
                    $imagen = null;
                }

                if (!isset($ventasAgrupadas[$key])) {
                    $ventasAgrupadas[$key] = [
                        'product_id' => $detail->product_id ?? 0,
                        'nombre' => $nombre,
                        'categoria' => $categoria,
                        'type' => $detail->type,
                        'cantidad_vendida' => 0,
                        'total_ventas' => 0,
                        'costo_total' => 0,
                        'imagen' => $imagen
                    ];
                }

                $ventasAgrupadas[$key]['cantidad_vendida'] += $detail->qty;
                $ventasAgrupadas[$key]['total_ventas'] += ($detail->qty * $detail->price);
                $ventasAgrupadas[$key]['costo_total'] += ($detail->qty * $costo);
            }
        }

        // Calcular utilidad y margen
        $ventas = [];
        $totalVentas = 0;
        $totalCosto = 0;

        foreach ($ventasAgrupadas as $item) {
            $utilidad_bruta = $item['total_ventas'] - $item['costo_total'];
            $margen_porcentaje = $item['total_ventas'] > 0
                ? round(($utilidad_bruta / $item['total_ventas']) * 100, 2)
                : 0;

            $totalVentas += $item['total_ventas'];
            $totalCosto += $item['costo_total'];

            $ventas[] = [
                'product_id' => $item['product_id'],
                'nombre' => $item['nombre'],
                'categoria' => $item['categoria'],
                'type' => $item['type'],
                'cantidad_vendida' => $item['cantidad_vendida'],
                'total_ventas' => round($item['total_ventas'], 2),
                'costo_total' => round($item['costo_total'], 2),
                'utilidad_bruta' => round($utilidad_bruta, 2),
                'margen_porcentaje' => $margen_porcentaje,
                'imagen' => $item['imagen']
            ];
        }

        // Ordenar por utilidad bruta descendente
        usort($ventas, function($a, $b) {
            return $b['utilidad_bruta'] <=> $a['utilidad_bruta'];
        });

        // Agregar porcentaje del total
        foreach ($ventas as &$venta) {
            $venta['porcentaje_del_total'] = $totalVentas > 0
                ? round(($venta['total_ventas'] / $totalVentas) * 100, 2)
                : 0;
        }

        $utilidadBrutaTotal = $totalVentas - $totalCosto;
        $margenPromedioTotal = $totalVentas > 0
            ? round(($utilidadBrutaTotal / $totalVentas) * 100, 2)
            : 0;

        return response()->json([
            'ok' => true,
            'fechaInicio' => $fechaInicio->format('Y-m-d'),
            'fechaFin' => $fechaFin->format('Y-m-d'),
            'resumen' => [
                'total_ventas' => number_format($totalVentas, 2, '.', ''),
                'costo_total' => number_format($totalCosto, 2, '.', ''),
                'utilidad_bruta' => number_format($utilidadBrutaTotal, 2, '.', ''),
                'margen_promedio' => $margenPromedioTotal,
                'cantidad_productos' => count($ventas)
            ],
            'ventas' => $ventas
        ]);
    }//.ventasUtilidad()

    public function descargarVentasUtilidadExcel(Request $request){
        // Obtener usuario autenticado y su tienda
        $user = $request->user();
        $shop = $user->shop;

        // Validar que las fechas sean correctas
        $request->validate([
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        // Generar archivo Excel
        $fileName = 'ventas_utilidad_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new \App\Exports\VentasUtilidadExport($request->fechaInicio, $request->fechaFin, $shop), $fileName);
    }//.descargarVentasUtilidadExcel


    /**
     * Reporte de Ventas por Período (Semanal/Mensual/Trimestral)
     * Muestra tendencias de ventas en el tiempo
     *
     * @param Request $request
     * - tipo_periodo: 'semanal', 'mensual', 'trimestral'
     * - modo: 'generadas' (total de notas) o 'cobradas' (dinero realmente cobrado)
     * - tipo_venta: 'todas', 'venta', 'renta'
     * - fechaInicio, fechaFin (opcional, por defecto últimos 3 meses)
     *
     * @return JsonResponse
     */
    public function ventasPeriodo(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        // Parámetros
        $tipoPeriodo = $request->input('tipo_periodo', 'mensual'); // semanal, mensual, trimestral
        $modo = $request->input('modo', 'generadas'); // 'generadas' o 'cobradas'
        $tipoVenta = $request->input('tipo_venta', 'todas'); // 'todas', 'venta', 'renta'

        // Fechas por defecto: últimos 3 meses si no se especifican
        $fechaInicio = $request->input('fechaInicio', now()->subMonths(3)->format('Y-m-d'));
        $fechaFin = $request->input('fechaFin', now()->format('Y-m-d'));

        $fechaInicioCarbon = Carbon::parse($fechaInicio)->startOfDay();
        $fechaFinCarbon = Carbon::parse($fechaFin)->endOfDay();

        // Determinar cómo calcular según el modo
        if ($modo === 'cobradas') {
            // MODO COBRADAS: Usar pagos parciales + received (dinero real)
            $periodos = $this->calcularPeriodosCobradas($shop, $fechaInicioCarbon, $fechaFinCarbon, $tipoPeriodo, $tipoVenta);
        } else {
            // MODO GENERADAS: Usar total de la nota (comportamiento original)
            $periodos = $this->calcularPeriodosGeneradas($shop, $fechaInicioCarbon, $fechaFinCarbon, $tipoPeriodo, $tipoVenta);
        }

        // Calcular resumen general
        $totalVentas = $periodos->sum(function($p) {
            return is_numeric($p['total_ventas']) ? floatval($p['total_ventas']) : 0;
        });
        $totalTickets = $periodos->sum('num_tickets');
        $ticketPromedio = $totalTickets > 0 ? $totalVentas / $totalTickets : 0;

        // Para modo cobradas, calcular también el pendiente por cobrar
        $totalPendiente = 0;
        if ($modo === 'cobradas') {
            $totalPendiente = $periodos->sum(function($p) {
                return isset($p['pendiente_cobrar']) ? floatval($p['pendiente_cobrar']) : 0;
            });
        }

        // Encontrar mejor y peor período
        $mejorPeriodo = $periodos->sortByDesc(function($p) {
            return is_numeric($p['total_ventas']) ? floatval($p['total_ventas']) : 0;
        })->first();

        $peorPeriodo = $periodos->sortBy(function($p) {
            return is_numeric($p['total_ventas']) ? floatval($p['total_ventas']) : 0;
        })->first();

        // Calcular comparación vs período anterior (si hay al menos 2 períodos)
        $comparacionPeriodoAnterior = null;
        if ($periodos->count() >= 2) {
            $ultimoPeriodo = $periodos->last();
            $penultimoPeriodo = $periodos->slice(-2, 1)->first();

            $ventasUltimo = is_numeric($ultimoPeriodo['total_ventas']) ? floatval($ultimoPeriodo['total_ventas']) : 0;
            $ventasPenultimo = is_numeric($penultimoPeriodo['total_ventas']) ? floatval($penultimoPeriodo['total_ventas']) : 0;

            if ($ventasPenultimo > 0) {
                $cambio = (($ventasUltimo - $ventasPenultimo) / $ventasPenultimo) * 100;
                $comparacionPeriodoAnterior = [
                    'periodo_actual' => $ultimoPeriodo['periodo'],
                    'ventas_actual' => number_format($ventasUltimo, 2, '.', ''),
                    'periodo_anterior' => $penultimoPeriodo['periodo'],
                    'ventas_anterior' => number_format($ventasPenultimo, 2, '.', ''),
                    'cambio_porcentaje' => number_format($cambio, 2, '.', ''),
                    'tendencia' => $cambio > 0 ? 'alza' : ($cambio < 0 ? 'baja' : 'estable'),
                ];
            }
        }

        // Resumen general
        $resumen = [
            'total_ventas' => number_format($totalVentas, 2, '.', ''),
            'total_tickets' => $totalTickets,
            'ticket_promedio' => number_format($ticketPromedio, 2, '.', ''),
            'cantidad_periodos' => $periodos->count(),
            'mejor_periodo' => $mejorPeriodo,
            'peor_periodo' => $peorPeriodo,
            'comparacion_periodo_anterior' => $comparacionPeriodoAnterior,
        ];

        // Agregar info de pendientes si es modo cobradas
        if ($modo === 'cobradas') {
            $resumen['total_pendiente'] = number_format($totalPendiente, 2, '.', '');
            $resumen['total_comprometido'] = number_format($totalVentas + $totalPendiente, 2, '.', '');
        }

        return response()->json([
            'ok' => true,
            'tipo_periodo' => $tipoPeriodo,
            'modo' => $modo,
            'tipo_venta' => $tipoVenta,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'resumen' => $resumen,
            'periodos' => $periodos->values(),
        ]);
    }//.ventasPeriodo

    /**
     * Calcular períodos en modo GENERADAS (total de notas creadas)
     */
    private function calcularPeriodosGeneradas($shop, $fechaInicio, $fechaFin, $tipoPeriodo, $tipoVenta)
    {
        // Query base
        $query = Receipt::where('shop_id', $shop->id)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION'])
            ->where('quotation', false);

        // Filtrar por tipo de venta
        if ($tipoVenta !== 'todas') {
            $query->where('type', $tipoVenta);
        }

        $receipts = $query->get();

        // Agrupar por período
        $periodos = $receipts->groupBy(function($receipt) use ($tipoPeriodo) {
            return $this->getPeriodoKey($receipt->created_at, $tipoPeriodo);
        })->map(function($grupo, $periodo) {
            return [
                'periodo' => $periodo,
                'num_tickets' => $grupo->count(),
                'total_ventas' => number_format($grupo->sum('total'), 2, '.', ''),
                'ticket_promedio' => number_format($grupo->avg('total'), 2, '.', ''),
                'ticket_maximo' => number_format($grupo->max('total'), 2, '.', ''),
                'ticket_minimo' => number_format($grupo->min('total'), 2, '.', ''),
            ];
        })->sortKeys();

        return $periodos;
    }

    /**
     * Calcular períodos en modo COBRADAS (dinero realmente recibido)
     * SIMPLIFICADO: Todo pago está en partial_payments
     */
    private function calcularPeriodosCobradas($shop, $fechaInicio, $fechaFin, $tipoPeriodo, $tipoVenta)
    {
        // SIMPLIFICADO: Todos los pagos están en partial_payments
        $queryPagos = PartialPayments::with('receipt')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->whereHas('receipt', function ($q) use ($shop, $tipoVenta) {
                $q->where('shop_id', $shop->id)
                    ->where('quotation', 0)
                    ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION']);
                if ($tipoVenta !== 'todas') {
                    $q->where('type', $tipoVenta);
                }
            });

        $pagos = $queryPagos->get();

        // Agrupar por período
        $cobrosPorPeriodo = [];
        foreach ($pagos as $pago) {
            $periodo = $this->getPeriodoKey($pago->created_at, $tipoPeriodo);

            if (!isset($cobrosPorPeriodo[$periodo])) {
                $cobrosPorPeriodo[$periodo] = ['cobrado' => 0, 'montos' => []];
            }

            $cobrosPorPeriodo[$periodo]['cobrado'] += $pago->amount;
            $cobrosPorPeriodo[$periodo]['montos'][] = $pago->amount;
        }

        // Calcular pendientes (notas POR COBRAR creadas en el rango)
        $notasPendientes = Receipt::where('shop_id', $shop->id)
            ->where('quotation', 0)
            ->where('status', 'POR COBRAR')
            ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin]);

        if ($tipoVenta !== 'todas') {
            $notasPendientes->where('type', $tipoVenta);
        }

        foreach ($notasPendientes->get() as $nota) {
            $pendiente = $nota->total - $nota->received;
            if ($pendiente > 0) {
                $periodo = $this->getPeriodoKey($nota->created_at, $tipoPeriodo);
                if (!isset($cobrosPorPeriodo[$periodo])) {
                    $cobrosPorPeriodo[$periodo] = ['cobrado' => 0, 'montos' => [], 'pendiente' => 0];
                }
                $cobrosPorPeriodo[$periodo]['pendiente'] = ($cobrosPorPeriodo[$periodo]['pendiente'] ?? 0) + $pendiente;
            }
        }

        // Convertir a formato estándar
        $periodos = collect($cobrosPorPeriodo)->map(function($data, $periodo) {
            $montos = collect($data['montos']);
            return [
                'periodo' => $periodo,
                'num_tickets' => count($data['montos']),
                'total_ventas' => number_format($data['cobrado'], 2, '.', ''),
                'pendiente_cobrar' => number_format($data['pendiente'] ?? 0, 2, '.', ''),
                'ticket_promedio' => $montos->count() > 0 ? number_format($montos->avg(), 2, '.', '') : '0.00',
                'ticket_maximo' => $montos->count() > 0 ? number_format($montos->max(), 2, '.', '') : '0.00',
                'ticket_minimo' => $montos->count() > 0 ? number_format($montos->min(), 2, '.', '') : '0.00',
            ];
        })->sortKeys();

        return $periodos;
    }

    /**
     * Obtener la clave del período según el tipo
     */
    private function getPeriodoKey($fecha, $tipoPeriodo)
    {
        $carbon = Carbon::parse($fecha);

        return match($tipoPeriodo) {
            'semanal' => $carbon->format('Y') . '-S' . str_pad($carbon->weekOfYear, 2, '0', STR_PAD_LEFT),
            'trimestral' => $carbon->format('Y') . '-T' . ceil($carbon->month / 3),
            default => $carbon->format('Y-m'), // mensual
        };
    }


    /**
     * Descargar Reporte de Ventas por Período en Excel
     */
    public function descargarVentasPeriodoExcel(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        // Generar archivo Excel
        $tipoPeriodo = $request->input('tipo_periodo', 'mensual');
        $fileName = 'ventas_periodo_' . $tipoPeriodo . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new \App\Exports\VentasPeriodoExport(
                $request->input('fechaInicio', now()->subMonths(3)->format('Y-m-d')),
                $request->input('fechaFin', now()->format('Y-m-d')),
                $shop,
                $tipoPeriodo
            ),
            $fileName
        );
    }//.descargarVentasPeriodoExcel


}//.class ReportsController
