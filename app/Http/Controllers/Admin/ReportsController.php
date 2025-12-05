<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\ReceiptDetail;
use App\Models\Product;
use App\Models\Client;
use App\Models\PurchaseOrder;
use App\Models\Expense;
use App\Models\PartialPayment;
use App\Models\PurchaseOrderPartialPayment;
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
        $porCobrar = 0;
        $abonos = 0;

        foreach ($receipts as $receipt) {
            $ventasTotal += $receipt->total;

            if ($receipt->type == 'venta') {
                $totalVentas++;
            } else {
                $totalRentas++;
            }

            if ($receipt->status == 'PAGADA') {
                $pagadas += $receipt->total;
            }
            if ($receipt->status == 'POR COBRAR') {
                $porCobrar += $receipt->total;
            }

            foreach ($receipt->partialPayments as $pp) {
                $abonos += $pp->amount;
            }
        }

        $adeudos = $ventasTotal - $abonos;

        // Agrupar por método de pago
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
            'periodo' => [
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d')
            ],
            'resumen' => [
                'total_notas' => $totalNotas,
                'total_ventas' => $totalVentas,
                'total_rentas' => $totalRentas,
                'monto_total' => round($ventasTotal, 2),
                'pagadas' => round($pagadas, 2),
                'por_cobrar' => round($porCobrar, 2),
                'abonos' => round($abonos, 2),
                'adeudos' => round($adeudos, 2),
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
     * Reporte: Utilidad por Producto
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

        // Obtener detalles de ventas
        $query = ReceiptDetail::with(['product.category'])
            ->whereHas('receipt', function ($q) use ($shop, $fechaInicio, $fechaFin) {
                $q->where('shop_id', $shop->id)
                    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                    ->where('quotation', 0)
                    ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION']);
            })
            ->where('type', 'product')
            ->whereNotNull('product_id');

        $detalles = $query->get();

        // Agrupar por producto
        $porProducto = $detalles->groupBy('product_id')->map(function ($items, $productId) {
            $producto = $items->first()->product;
            $qty = $items->sum('qty');
            $ingresos = $items->sum('subtotal');
            $costo = $items->sum(function ($item) {
                return ($item->cost ?? 0) * $item->qty;
            });
            $utilidad = $ingresos - $costo;
            $margen = $ingresos > 0 ? round(($utilidad / $ingresos) * 100, 2) : 0;

            return [
                'product_id' => $productId,
                'codigo' => $producto->key ?? '',
                'nombre' => $producto->name ?? 'Producto eliminado',
                'categoria' => $producto->category->name ?? 'Sin categoría',
                'categoria_id' => $producto->category_id,
                'qty' => $qty,
                'ingresos' => round($ingresos, 2),
                'costo' => round($costo, 2),
                'utilidad' => round($utilidad, 2),
                'margen' => $margen
            ];
        });

        // Filtrar por categoría si se especifica
        if ($categoriaId && $categoriaId != 'TODOS') {
            $porProducto = $porProducto->where('categoria_id', $categoriaId);
        }

        // Ordenar por utilidad descendente
        $porProducto = $porProducto->sortByDesc('utilidad')->values();

        // Totales
        $totalIngresos = $porProducto->sum('ingresos');
        $totalCosto = $porProducto->sum('costo');
        $totalUtilidad = $porProducto->sum('utilidad');
        $margenGlobal = $totalIngresos > 0 ? round(($totalUtilidad / $totalIngresos) * 100, 2) : 0;

        return response()->json([
            'ok' => true,
            'periodo' => [
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d')
            ],
            'totales' => [
                'ingresos' => $totalIngresos,
                'costo' => $totalCosto,
                'utilidad' => $totalUtilidad,
                'margen' => $margenGlobal,
                'productos' => $porProducto->count()
            ],
            'productos' => $porProducto
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
     * Reporte: Ingresos vs Egresos (Flujo de Caja)
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

        // INGRESOS: Pagos recibidos (abonos + pagos completos)
        $ingresosPagosCompletos = Receipt::where('shop_id', $shop->id)
            ->where('finished', 1)
            ->where('quotation', 0)
            ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->sum('received');

        $ingresosAbonos = PartialPayment::whereHas('receipt', function ($q) use ($shop) {
            $q->where('shop_id', $shop->id)
                ->where('quotation', 0)
                ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION']);
        })->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->sum('amount');

        $totalIngresos = $ingresosPagosCompletos + $ingresosAbonos;

        // EGRESOS: Pagos a proveedores
        $egresosCompras = PurchaseOrderPartialPayment::whereHas('purchaseOrder', function ($q) use ($shop) {
            $q->where('shop_id', $shop->id);
        })->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->sum('amount');

        // EGRESOS: Gastos operativos
        $egresosGastos = Expense::where('shop_id', $shop->id)
            ->whereBetween('date', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')])
            ->where('status', 'PAGADO')
            ->sum('total');

        $totalEgresos = $egresosCompras + $egresosGastos;
        $balance = $totalIngresos - $totalEgresos;

        return response()->json([
            'ok' => true,
            'periodo' => [
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d')
            ],
            'ingresos' => [
                'pagos_completos' => round($ingresosPagosCompletos, 2),
                'abonos' => round($ingresosAbonos, 2),
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
}
