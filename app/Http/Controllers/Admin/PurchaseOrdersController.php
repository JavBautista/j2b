<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrdersController extends Controller
{
    /**
     * Vista de lista de órdenes de compra
     */
    public function list()
    {
        return view('admin.purchase-orders.list');
    }

    /**
     * Obtener lista de órdenes de compra (AJAX)
     */
    public function getList(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $buscar = $request->get('buscar', '');
        $status = $request->get('status', '');
        $payable = $request->get('payable', '');
        $fechaDesde = $request->get('fecha_desde', '');
        $fechaHasta = $request->get('fecha_hasta', '');

        $query = PurchaseOrder::with(['supplier', 'partialPayments'])
            ->where('shop_id', $shop->id);

        // Filtro de búsqueda (proveedor o folio)
        if (!empty($buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where('folio', 'like', '%' . $buscar . '%')
                  ->orWhereHas('supplier', function (Builder $subquery) use ($buscar) {
                      $subquery->where('name', 'like', '%' . $buscar . '%')
                               ->orWhere('company', 'like', '%' . $buscar . '%');
                  });
            });
        }

        // Filtro por estado
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Filtro por payable (por pagar / pagada)
        if ($payable !== '') {
            $query->where('payable', $payable);
        }

        // Filtro por fecha desde
        if (!empty($fechaDesde)) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }

        // Filtro por fecha hasta
        if (!empty($fechaHasta)) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        $orders = $query->orderBy('id', 'desc')->paginate(15);

        return response()->json([
            'ok' => true,
            'orders' => $orders->items(),
            'pagination' => [
                'total' => $orders->total(),
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem()
            ]
        ]);
    }

    /**
     * Vista de crear nueva orden de compra
     */
    public function create()
    {
        return view('admin.purchase-orders.create');
    }

    /**
     * Vista de detalle de orden de compra
     */
    public function show($id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $order = PurchaseOrder::with(['supplier', 'detail.product', 'partialPayments'])
            ->where('shop_id', $shop->id)
            ->findOrFail($id);

        return view('admin.purchase-orders.show', compact('order'));
    }

    /**
     * Vista de editar orden de compra
     */
    public function edit($id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $order = PurchaseOrder::with(['supplier', 'detail.product'])
            ->where('shop_id', $shop->id)
            ->where('status', 'CREADA')
            ->findOrFail($id);

        return view('admin.purchase-orders.edit', compact('order'));
    }

    /**
     * Guardar nueva orden de compra (AJAX)
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        date_default_timezone_set('America/Mexico_City');

        $po = $request->purchase_order;

        // Formatear fecha de vencimiento
        $expiration = null;
        if (!empty($po['expiration'])) {
            $expiration = Carbon::parse($po['expiration'])->format('Y-m-d');
        }

        // Crear la orden de compra
        $purchase_order = new PurchaseOrder();
        $purchase_order->shop_id = $shop->id;
        $purchase_order->supplier_id = $po['supplier_id'];
        $purchase_order->status = $po['status'] ?? 'CREADA';
        $purchase_order->expiration = $expiration;
        $purchase_order->observation = $po['observation'] ?? '';
        $purchase_order->payment = $po['payment'] ?? 'EFECTIVO';
        $purchase_order->total = $po['total'] ?? 0;
        $purchase_order->payable = $po['payable'] ?? 1;
        $purchase_order->save();

        // Guardar el detalle de la orden
        $details = $request->detail;

        foreach ($details as $data) {
            $detail = new PurchaseOrderDetail();
            $detail->purchase_order_id = $purchase_order->id;
            $detail->product_id = $data['product_id'];
            $detail->description = $data['description'];
            $detail->qty = $data['qty'];
            $detail->price = $data['price'];
            $detail->subtotal = $data['subtotal'];
            $detail->save();
        }

        // Recargar con relaciones
        $order = PurchaseOrder::with(['supplier', 'partialPayments', 'shop'])
            ->findOrFail($purchase_order->id);

        return response()->json([
            'ok' => true,
            'order' => $order
        ]);
    }

    /**
     * Obtener detalle de orden de compra (AJAX)
     */
    public function getDetail($id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $order = PurchaseOrder::with(['supplier', 'detail.product', 'partialPayments', 'shop'])
            ->where('shop_id', $shop->id)
            ->findOrFail($id);

        return response()->json([
            'ok' => true,
            'order' => $order
        ]);
    }
}
