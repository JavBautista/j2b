<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\ReceiptDetail;
use App\Models\Product;
use App\Models\Client;
use App\Models\PurchaseOrder;
use App\Models\Expense;
use App\Models\PartialPayments;
use App\Models\PurchaseOrderPartialPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportsController extends Controller
{
    /**
     * Vista principal de reportes
     */
    public function index()
    {
        $shop = auth()->user()->shop;
        return view('admin.reports.index', compact('shop'));
    }

    /**
     * Reporte: Resumen de Ventas por período
     * Modos: 'generadas' (por fecha de nota) o 'cobradas' (por fecha de pago)
     */
    public function ventasResumen(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        // Fechas por defecto: mes actual
        $fechaInicio = $request->fecha_inicio
            ? Carbon::parse($request->fecha_inicio)->startOfDay()
            : Carbon::now()->startOfMonth();
        $fechaFin = $request->fecha_fin
            ? Carbon::parse($request->fecha_fin)->endOfDay()
            : Carbon::now()->endOfDay();

        // Modo: 'generadas' (default) o 'cobradas'
        $modo = $request->modo ?? 'generadas';

        if ($modo === 'cobradas') {
            return $this->ventasResumenCobradas($shop, $fechaInicio, $fechaFin);
        }

        // === MODO GENERADAS (por fecha de creacion de nota) ===
        $receipts = Receipt::with('partialPayments')
            ->where('shop_id', $shop->id)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->where('quotation', 0)
            ->where('status', '<>', 'CANCELADA')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calcular totales
        $totalNotas = $receipts->count();
        $totalVentas = 0;
        $totalRentas = 0;
        $ventasTotal = 0;
        $pagadas = 0;
        $adeudoReal = 0;
        $abonos = 0;
        $cobrado = 0;

        foreach ($receipts as $receipt) {
            $ventasTotal += $receipt->total;

            if ($receipt->type == 'venta') {
                $totalVentas++;
            } else {
                $totalRentas++;
            }

            $abonosNota = $receipt->partialPayments->sum('amount');
            $abonos += $abonosNota;

            if ($receipt->status == 'PAGADA') {
                $pagadas += $receipt->total;
                $cobrado += $receipt->total;
            }

            if ($receipt->status == 'POR COBRAR') {
                $adeudoReal += ($receipt->total - $abonosNota);
                $cobrado += $abonosNota;
            }
        }

        $porMetodoPago = $receipts->where('status', 'PAGADA')
            ->groupBy('payment')
            ->map(function ($group) {
                return [
                    'cantidad' => $group->count(),
                    'total' => $group->sum('total')
                ];
            });

        return response()->json([
            'ok' => true,
            'modo' => 'generadas',
            'periodo' => [
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d')
            ],
            'resumen' => [
                'total_notas' => $totalNotas,
                'total_ventas' => $totalVentas,
                'total_rentas' => $totalRentas,
                'monto_total' => round($ventasTotal, 2),
                'cobrado' => round($cobrado, 2),
                'por_cobrar' => round($adeudoReal, 2),
                'pagadas' => round($pagadas, 2),
                'abonos' => round($abonos, 2),
            ],
            'por_metodo_pago' => $porMetodoPago,
            'detalle' => $receipts->map(function ($r) {
                return [
                    'id' => $r->id,
                    'folio' => $r->folio,
                    'fecha' => $r->created_at->format('Y-m-d'),
                    'cliente' => $r->client->name ?? 'Sin cliente',
                    'tipo' => $r->type,
                    'status' => $r->status,
                    'total' => $r->total,
                    'payment' => $r->payment
                ];
            })
        ]);
    }

    /**
     * Modo COBRADAS: Dinero realmente recibido en el periodo
     * Simplificado: TODO pago está en partial_payments
     */
    private function ventasResumenCobradas($shop, $fechaInicio, $fechaFin)
    {
        // Todos los pagos están en partial_payments
        $pagos = PartialPayments::with('receipt.client')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->whereHas('receipt', function ($q) use ($shop) {
                $q->where('shop_id', $shop->id)
                    ->where('quotation', 0)
                    ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $totalCobrado = $pagos->sum('amount');
        $cantidadPagos = $pagos->count();

        // Agrupar por tipo de pago (unico, inicial, abono, liquidacion)
        $porTipo = $pagos->groupBy('payment_type')->map(function ($grupo, $tipo) {
            return [
                'cantidad' => $grupo->count(),
                'monto' => round($grupo->sum('amount'), 2)
            ];
        });

        // Construir detalle
        $detalle = $pagos->map(function ($pago) {
            $r = $pago->receipt;
            return [
                'id' => $r->id,
                'folio' => $r->folio,
                'fecha' => Carbon::parse($pago->created_at)->format('Y-m-d'),
                'cliente' => $r->client->name ?? 'Sin cliente',
                'tipo' => $r->type,
                'tipo_pago' => $pago->payment_type ?? 'abono',
                'monto' => round($pago->amount, 2),
                'payment' => $r->payment
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'modo' => 'cobradas',
            'periodo' => [
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d')
            ],
            'resumen' => [
                'total_cobrado' => round($totalCobrado, 2),
                'cantidad_pagos' => $cantidadPagos,
                'por_tipo' => $porTipo,
            ],
            'detalle' => $detalle
        ]);
    }

    /**
     * Reporte: Utilidad por Producto/Servicio/Renta
     * Modos: 'cobradas' (solo notas PAGADAS) o 'generadas' (todas las notas)
     */
    public function ventasUtilidad(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $fechaInicio = $request->fecha_inicio
            ? Carbon::parse($request->fecha_inicio)->startOfDay()
            : Carbon::now()->startOfMonth();
        $fechaFin = $request->fecha_fin
            ? Carbon::parse($request->fecha_fin)->endOfDay()
            : Carbon::now()->endOfDay();

        $categoriaId = $request->categoria_id;
        $modo = $request->modo ?? 'cobradas'; // Default: solo notas pagadas

        // Obtener detalles de ventas (todos los tipos)
        $query = ReceiptDetail::with(['product.category', 'receipt'])
            ->whereHas('receipt', function ($q) use ($shop, $fechaInicio, $fechaFin, $modo) {
                $q->where('shop_id', $shop->id)
                    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                    ->where('quotation', 0)
                    ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION']);

                // Modo cobradas: solo notas PAGADAS
                if ($modo === 'cobradas') {
                    $q->where('status', 'PAGADA');
                }
            });

        $detalles = $query->get();

        // Separar por tipo para procesar diferente
        $productos = [];
        $servicios = [];
        $rentas = [];

        // Totales globales
        $totalIngresos = 0;
        $totalCosto = 0;

        // Procesar PRODUCTOS (tienen costo)
        $detallesProductos = $detalles->whereIn('type', ['product', 'equipment'])->whereNotNull('product_id');
        $porProducto = $detallesProductos->groupBy('product_id')->map(function ($items, $productId) use (&$totalIngresos, &$totalCosto) {
            $producto = $items->first()->product;
            $qty = $items->sum('qty');
            $ingresos = $items->sum('subtotal');
            $costo = $items->sum(function ($item) {
                return ($item->cost ?? 0) * $item->qty;
            });
            $utilidad = $ingresos - $costo;
            $margen = $ingresos > 0 ? round(($utilidad / $ingresos) * 100, 2) : 0;

            $totalIngresos += $ingresos;
            $totalCosto += $costo;

            return [
                'tipo' => 'producto',
                'product_id' => $productId,
                'codigo' => $producto->key ?? '',
                'nombre' => $producto->name ?? 'Producto eliminado',
                'categoria' => $producto->category->name ?? 'Sin categoría',
                'categoria_id' => $producto->category_id ?? 0,
                'qty' => $qty,
                'ingresos' => round($ingresos, 2),
                'costo' => round($costo, 2),
                'utilidad' => round($utilidad, 2),
                'margen' => $margen
            ];
        });

        // Procesar SERVICIOS (100% ganancia, costo = 0)
        $detallesServicios = $detalles->where('type', 'service');
        $totalServiciosIngresos = $detallesServicios->sum('subtotal');
        $totalIngresos += $totalServiciosIngresos;

        if ($detallesServicios->count() > 0) {
            $servicios = [[
                'tipo' => 'servicio',
                'product_id' => null,
                'codigo' => 'SERV',
                'nombre' => 'Servicios',
                'categoria' => 'Servicios',
                'categoria_id' => 0,
                'qty' => $detallesServicios->count(),
                'ingresos' => round($totalServiciosIngresos, 2),
                'costo' => 0,
                'utilidad' => round($totalServiciosIngresos, 2),
                'margen' => 100
            ]];
        }

        // Procesar RENTAS (100% ganancia, costo = 0)
        $detallesRentas = $detalles->where('type', 'rent');
        $totalRentasIngresos = $detallesRentas->sum('subtotal');
        $totalIngresos += $totalRentasIngresos;

        if ($detallesRentas->count() > 0) {
            $rentas = [[
                'tipo' => 'renta',
                'product_id' => null,
                'codigo' => 'RENT',
                'nombre' => 'Rentas de Equipo',
                'categoria' => 'Rentas',
                'categoria_id' => 0,
                'qty' => $detallesRentas->count(),
                'ingresos' => round($totalRentasIngresos, 2),
                'costo' => 0,
                'utilidad' => round($totalRentasIngresos, 2),
                'margen' => 100
            ]];
        }

        // Combinar todos los resultados
        $todosItems = collect($porProducto->values())
            ->merge($servicios)
            ->merge($rentas);

        // Filtrar por categoría si se especifica (solo aplica a productos)
        if ($categoriaId && $categoriaId != 'TODOS') {
            $todosItems = $todosItems->filter(function ($item) use ($categoriaId) {
                return $item['categoria_id'] == $categoriaId || $item['tipo'] !== 'producto';
            });
        }

        // Ordenar por utilidad descendente
        $todosItems = $todosItems->sortByDesc('utilidad')->values();

        // Calcular totales
        $totalUtilidad = $totalIngresos - $totalCosto;
        $margenGlobal = $totalIngresos > 0 ? round(($totalUtilidad / $totalIngresos) * 100, 2) : 0;

        return response()->json([
            'ok' => true,
            'modo' => $modo,
            'periodo' => [
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d')
            ],
            'totales' => [
                'ingresos' => round($totalIngresos, 2),
                'costo' => round($totalCosto, 2),
                'utilidad' => round($totalUtilidad, 2),
                'margen' => $margenGlobal,
                'items' => $todosItems->count(),
                'servicios' => round($totalServiciosIngresos, 2),
                'rentas' => round($totalRentasIngresos, 2)
            ],
            'productos' => $todosItems
        ]);
    }

    /**
     * Reporte: Inventario Valorizado
     */
    public function inventario(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $categoriaId = $request->categoria_id;
        $filtroStock = $request->filtro_stock ?? 'TODOS'; // TODOS, BAJO, SIN

        $query = Product::with('category')
            ->where('shop_id', $shop->id)
            ->where('active', 1);

        // Filtrar por categoría
        if ($categoriaId && $categoriaId != 'TODOS') {
            $query->where('category_id', $categoriaId);
        }

        // Filtrar por stock
        if ($filtroStock == 'BAJO') {
            $query->where('stock', '>', 0)->where('stock', '<=', 5);
        } elseif ($filtroStock == 'SIN') {
            $query->where('stock', '<=', 0);
        }

        $productos = $query->orderBy('stock', 'asc')->get();

        // Calcular valores
        $data = $productos->map(function ($p) {
            $disponible = $p->stock - ($p->reserve ?? 0);
            $valorStock = $p->stock * $p->cost;
            $alerta = $p->stock <= 5 ? ($p->stock <= 0 ? 'sin_stock' : 'bajo_stock') : null;

            return [
                'id' => $p->id,
                'codigo' => $p->key,
                'nombre' => $p->name,
                'categoria' => $p->category->name ?? 'Sin categoría',
                'stock' => $p->stock,
                'reserva' => $p->reserve ?? 0,
                'disponible' => $disponible,
                'costo' => round($p->cost, 2),
                'valor_stock' => round($valorStock, 2),
                'alerta' => $alerta
            ];
        });

        // Totales
        $totalProductos = $data->count();
        $totalUnidades = $data->sum('stock');
        $valorTotal = $data->sum('valor_stock');
        $productosBajoStock = $data->where('alerta', 'bajo_stock')->count();
        $productosSinStock = $data->where('alerta', 'sin_stock')->count();

        return response()->json([
            'ok' => true,
            'resumen' => [
                'total_productos' => $totalProductos,
                'total_unidades' => $totalUnidades,
                'valor_total' => round($valorTotal, 2),
                'bajo_stock' => $productosBajoStock,
                'sin_stock' => $productosSinStock
            ],
            'productos' => $data
        ]);
    }

    /**
     * Reporte: Flujo de Caja (Ingresos vs Egresos)
     * Simplificado: TODO ingreso está en partial_payments
     */
    public function ingresosEgresos(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $fechaInicio = $request->fecha_inicio
            ? Carbon::parse($request->fecha_inicio)->startOfDay()
            : Carbon::now()->startOfMonth();
        $fechaFin = $request->fecha_fin
            ? Carbon::parse($request->fecha_fin)->endOfDay()
            : Carbon::now()->endOfDay();

        // Filtro de facturado: true=solo facturado, false=no facturado, null=todos
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
            ->whereBetween('date', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')])
            ->where('status', 'PAGADO')
            ->where('active', 1);

        if (!is_null($soloFacturado)) {
            $queryGastos->where('is_tax_invoiced', $soloFacturado);
        }
        $egresosGastos = $queryGastos->sum('total');

        $totalEgresos = $egresosCompras + $egresosGastos;
        $balance = $totalIngresos - $totalEgresos;

        return response()->json([
            'ok' => true,
            'periodo' => [
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d')
            ],
            'filtro_facturado' => $soloFacturado,
            'ingresos' => [
                'total' => round($totalIngresos, 2)
            ],
            'egresos' => [
                'compras' => round($egresosCompras, 2),
                'gastos' => round($egresosGastos, 2),
                'total' => round($totalEgresos, 2)
            ],
            'balance' => round($balance, 2)
        ]);
    }

    /**
     * Reporte: Clientes con Adeudo
     */
    public function clientesAdeudos(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $receipts = Receipt::with(['client', 'partialPayments'])
            ->where('shop_id', $shop->id)
            ->where('status', 'POR COBRAR')
            ->where('quotation', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        // Agrupar por cliente
        $porCliente = $receipts->groupBy('client_id')->map(function ($notas, $clientId) {
            $cliente = $notas->first()->client;
            $totalFacturado = $notas->sum('total');
            $totalAbonos = $notas->sum(function ($nota) {
                return $nota->partialPayments->sum('amount');
            });
            $adeudo = $totalFacturado - $totalAbonos;

            // Antigüedad: días desde la nota más vieja
            $notaMasVieja = $notas->sortBy('created_at')->first();
            $antiguedad = $notaMasVieja ? $notaMasVieja->created_at->diffInDays(now()) : 0;

            return [
                'client_id' => $clientId,
                'nombre' => $cliente->name ?? 'Cliente eliminado',
                'empresa' => $cliente->company ?? '',
                'telefono' => $cliente->phone ?? $cliente->movil ?? '',
                'notas_pendientes' => $notas->count(),
                'total_facturado' => round($totalFacturado, 2),
                'total_abonos' => round($totalAbonos, 2),
                'adeudo' => round($adeudo, 2),
                'antiguedad_dias' => $antiguedad
            ];
        });

        // Ordenar por adeudo descendente
        $porCliente = $porCliente->sortByDesc('adeudo')->values();

        // Totales
        $totalClientes = $porCliente->count();
        $totalAdeudo = $porCliente->sum('adeudo');

        return response()->json([
            'ok' => true,
            'resumen' => [
                'total_clientes' => $totalClientes,
                'total_adeudo' => round($totalAdeudo, 2)
            ],
            'clientes' => $porCliente
        ]);
    }

    /**
     * Reporte: Top Productos (más vendidos)
     */
    public function topProductos(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $fechaInicio = $request->fecha_inicio
            ? Carbon::parse($request->fecha_inicio)->startOfDay()
            : Carbon::now()->startOfMonth();
        $fechaFin = $request->fecha_fin
            ? Carbon::parse($request->fecha_fin)->endOfDay()
            : Carbon::now()->endOfDay();

        $ordenarPor = $request->ordenar_por ?? 'qty'; // qty o ingresos
        $limite = $request->limite ?? 20;

        $detalles = ReceiptDetail::with('product')
            ->whereHas('receipt', function ($q) use ($shop, $fechaInicio, $fechaFin) {
                $q->where('shop_id', $shop->id)
                    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                    ->where('quotation', 0)
                    ->whereNotIn('status', ['CANCELADA']);
            })
            ->where('type', 'product')
            ->whereNotNull('product_id')
            ->get();

        // Agrupar por producto
        $porProducto = $detalles->groupBy('product_id')->map(function ($items, $productId) {
            $producto = $items->first()->product;
            return [
                'product_id' => $productId,
                'codigo' => $producto->key ?? '',
                'nombre' => $producto->name ?? 'Producto eliminado',
                'categoria' => $producto->category->name ?? 'Sin categoría',
                'qty' => $items->sum('qty'),
                'ingresos' => round($items->sum('subtotal'), 2)
            ];
        });

        // Ordenar
        if ($ordenarPor == 'ingresos') {
            $porProducto = $porProducto->sortByDesc('ingresos');
        } else {
            $porProducto = $porProducto->sortByDesc('qty');
        }

        // Limitar y agregar ranking
        $porProducto = $porProducto->take($limite)->values()->map(function ($item, $index) {
            $item['ranking'] = $index + 1;
            return $item;
        });

        return response()->json([
            'ok' => true,
            'periodo' => [
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d')
            ],
            'ordenado_por' => $ordenarPor,
            'productos' => $porProducto
        ]);
    }

    /**
     * Obtener categorías para filtros
     */
    public function getCategorias()
    {
        $shop = auth()->user()->shop;
        $categorias = \App\Models\Category::where('shop_id', $shop->id)
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['ok' => true, 'categorias' => $categorias]);
    }

    /**
     * Reporte: Diferencias Mensual (Ingresos - Egresos)
     * Simplificado: TODO ingreso está en partial_payments
     */
    public function diferenciasMensual(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        // Parámetros: mes y año
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Generar fechas de inicio y fin del mes
        $mm = str_pad($month, 2, '0', STR_PAD_LEFT);
        $fechaBase = Carbon::parse($year . '-' . $mm . '-01');
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
            'periodo' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d')
            ],
            'filtro_facturado' => $soloFacturado,
            'ingresos' => round($totalIngresos, 2),
            'egresos' => round($totalEgresos, 2),
            'diferencia' => round($diferencia, 2)
        ]);
    }

    /**
     * Reporte: Ventas por Período (Semanal/Mensual/Trimestral)
     */
    public function ventasPeriodo(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        // Parámetros
        $tipoPeriodo = $request->input('tipo_periodo', 'mensual'); // semanal, mensual, trimestral
        $modo = $request->input('modo', 'generadas'); // 'generadas' o 'cobradas'
        $tipoVenta = $request->input('tipo_venta', 'todas'); // 'todas', 'venta', 'renta'

        // Fechas por defecto: últimos 3 meses
        $fechaInicio = $request->input('fecha_inicio', now()->subMonths(3)->format('Y-m-d'));
        $fechaFin = $request->input('fecha_fin', now()->format('Y-m-d'));

        $fechaInicioCarbon = Carbon::parse($fechaInicio)->startOfDay();
        $fechaFinCarbon = Carbon::parse($fechaFin)->endOfDay();

        // Determinar cómo calcular según el modo
        if ($modo === 'cobradas') {
            $periodos = $this->calcularPeriodosCobradas($shop, $fechaInicioCarbon, $fechaFinCarbon, $tipoPeriodo, $tipoVenta);
        } else {
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

        // Calcular comparación vs período anterior
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
                    'ventas_actual' => round($ventasUltimo, 2),
                    'periodo_anterior' => $penultimoPeriodo['periodo'],
                    'ventas_anterior' => round($ventasPenultimo, 2),
                    'cambio_porcentaje' => round($cambio, 2),
                    'tendencia' => $cambio > 0 ? 'alza' : ($cambio < 0 ? 'baja' : 'estable'),
                ];
            }
        }

        // Resumen general
        $resumen = [
            'total_ventas' => round($totalVentas, 2),
            'total_tickets' => $totalTickets,
            'ticket_promedio' => round($ticketPromedio, 2),
            'cantidad_periodos' => $periodos->count(),
            'mejor_periodo' => $mejorPeriodo,
            'peor_periodo' => $peorPeriodo,
            'comparacion_periodo_anterior' => $comparacionPeriodoAnterior,
        ];

        // Agregar info de pendientes si es modo cobradas
        if ($modo === 'cobradas') {
            $resumen['total_pendiente'] = round($totalPendiente, 2);
            $resumen['total_comprometido'] = round($totalVentas + $totalPendiente, 2);
        }

        return response()->json([
            'ok' => true,
            'tipo_periodo' => $tipoPeriodo,
            'modo' => $modo,
            'tipo_venta' => $tipoVenta,
            'periodo' => [
                'inicio' => $fechaInicio,
                'fin' => $fechaFin
            ],
            'resumen' => $resumen,
            'periodos' => $periodos->values(),
        ]);
    }

    /**
     * Helper: Calcular períodos en modo GENERADAS (total de notas creadas)
     */
    private function calcularPeriodosGeneradas($shop, $fechaInicio, $fechaFin, $tipoPeriodo, $tipoVenta)
    {
        $query = Receipt::where('shop_id', $shop->id)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION'])
            ->where('quotation', false);

        if ($tipoVenta !== 'todas') {
            $query->where('type', $tipoVenta);
        }

        $receipts = $query->get();

        $periodos = $receipts->groupBy(function($receipt) use ($tipoPeriodo) {
            return $this->getPeriodoKey($receipt->created_at, $tipoPeriodo);
        })->map(function($grupo, $periodo) {
            return [
                'periodo' => $periodo,
                'num_tickets' => $grupo->count(),
                'total_ventas' => round($grupo->sum('total'), 2),
                'ticket_promedio' => round($grupo->avg('total'), 2),
                'ticket_maximo' => round($grupo->max('total'), 2),
                'ticket_minimo' => round($grupo->min('total'), 2),
            ];
        })->sortKeys();

        return $periodos;
    }

    /**
     * Helper: Calcular períodos en modo COBRADAS (dinero realmente recibido)
     * Simplificado: TODO pago está en partial_payments
     */
    private function calcularPeriodosCobradas($shop, $fechaInicio, $fechaFin, $tipoPeriodo, $tipoVenta)
    {
        // Todos los pagos están en partial_payments
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
                'total_ventas' => round($data['cobrado'], 2),
                'pendiente_cobrar' => round($data['pendiente'] ?? 0, 2),
                'ticket_promedio' => $montos->count() > 0 ? round($montos->avg(), 2) : 0,
                'ticket_maximo' => $montos->count() > 0 ? round($montos->max(), 2) : 0,
                'ticket_minimo' => $montos->count() > 0 ? round($montos->min(), 2) : 0,
            ];
        })->sortKeys();

        return $periodos;
    }

    /**
     * Helper: Obtener la clave del período según el tipo
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
}
