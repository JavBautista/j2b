<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\ReceiptDetail;
use App\Models\ReceiptInfoExtra;
use App\Models\PartialPayments;
use App\Models\Client;
use App\Models\Product;
use App\Models\Service;
use App\Models\RentDetail;
use App\Models\ExtraFieldShop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class ReceiptsController extends Controller
{
    /**
     * Vista de listado general de notas de venta
     */
    public function list()
    {
        return view('admin.receipts.list');
    }

    /**
     * API para obtener listado de notas de venta (AJAX)
     */
    public function getList(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $buscar = $request->get('buscar', '');
        $tipo = $request->get('tipo', '');
        $status = $request->get('status', '');
        $fechaDesde = $request->get('fecha_desde', '');
        $fechaHasta = $request->get('fecha_hasta', '');

        $query = Receipt::with(['partialPayments', 'shop', 'detail', 'client', 'cfdiInvoice:id,receipt_id,uuid,serie,folio,status'])
                        ->where('shop_id', $shop->id);

        // Filtro por tipo (venta o cotización)
        if ($tipo === 'venta') {
            $query->where('quotation', 0);
        } elseif ($tipo === 'cotizacion') {
            $query->where('quotation', 1);
        }

        // Filtro por status
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Filtro por fecha
        if (!empty($fechaDesde)) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }
        if (!empty($fechaHasta)) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        // Filtro de búsqueda por folio o nombre de cliente
        if (!empty($buscar)) {
            $query->where(function($q) use ($buscar) {
                $q->where('folio', 'like', '%' . $buscar . '%')
                  ->orWhere('id', 'like', '%' . $buscar . '%')
                  ->orWhereHas('client', function($clientQuery) use ($buscar) {
                      $clientQuery->where('name', 'like', '%' . $buscar . '%')
                                  ->orWhere('movil', 'like', '%' . $buscar . '%');
                  });
            });
        }

        $receipts = $query->orderBy('id', 'desc')->paginate(15);

        return response()->json([
            'ok' => true,
            'receipts' => $receipts->items(),
            'pagination' => [
                'total' => $receipts->total(),
                'current_page' => $receipts->currentPage(),
                'per_page' => $receipts->perPage(),
                'last_page' => $receipts->lastPage(),
                'from' => $receipts->firstItem(),
                'to' => $receipts->lastItem()
            ]
        ]);
    }

    /**
     * Obtener detalle completo de una nota de venta
     */
    public function getDetail($id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $receipt = Receipt::with(['partialPayments', 'shop', 'detail', 'client', 'infoExtra'])
                          ->where('id', $id)
                          ->where('shop_id', $shop->id)
                          ->first();

        if (!$receipt) {
            return response()->json(['ok' => false, 'message' => 'Nota no encontrada'], 404);
        }

        // Agregar imagen a cada item del detalle
        if ($receipt->detail) {
            foreach ($receipt->detail as $detail) {
                $detail->image = null;
                if ($detail->type === 'product') {
                    $product = Product::find($detail->product_id);
                    if ($product) {
                        $detail->image = $product->image;
                    }
                } elseif ($detail->type === 'service') {
                    // Servicios no tienen imagen, se usará placeholder
                    $detail->image = null;
                } elseif ($detail->type === 'equipment') {
                    $equipo = RentDetail::with('images')->find($detail->product_id);
                    if ($equipo && $equipo->images && count($equipo->images) > 0) {
                        $detail->image = $equipo->images[0]->image;
                    }
                }
            }
        }

        return response()->json([
            'ok' => true,
            'receipt' => $receipt
        ]);
    }

    /**
     * Mostrar vista de recibos de un cliente
     */
    public function index(Request $request, $clientId)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece al shop del usuario
        $client = Client::where('id', $clientId)
                       ->where('shop_id', $shop->id)
                       ->firstOrFail();

        return view('admin.receipts.index', compact('client'));
    }

    /**
     * API para obtener recibos de un cliente (AJAX)
     */
    public function getReceipts(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;
        
        $clientId = $request->get('client_id');
        $buscar = $request->get('buscar', '');

        // Verificar que el cliente pertenece al shop del usuario
        $client = Client::where('id', $clientId)
                       ->where('shop_id', $shop->id)
                       ->firstOrFail();

        $query = Receipt::with(['partialPayments', 'shop', 'detail', 'client'])
                        ->where('client_id', $clientId)
                        ->where('shop_id', $shop->id);

        // Filtro de búsqueda por folio o número de recibo
        if (!empty($buscar)) {
            $query->where(function($q) use ($buscar) {
                $q->where('folio', 'like', '%' . $buscar . '%')
                  ->orWhere('id', 'like', '%' . $buscar . '%');
            });
        }

        $receipts = $query->orderBy('id', 'desc')->paginate(10);

        $response = $receipts->toArray();
        $response['pagination'] = [
            'total' => $receipts->total(),
            'current_page' => $receipts->currentPage(),
            'per_page' => $receipts->perPage(),
            'last_page' => $receipts->lastPage(),
            'from' => $receipts->firstItem(),
            'to' => $receipts->lastItem()
        ];

        return response()->json([
            'success' => true,
            'receipts' => $response['data'],
            'pagination' => $response['pagination'],
            'client' => $client
        ]);
    }

    /**
     * Vista para crear nueva nota de venta
     */
    public function create()
    {
        return view('admin.receipts.create');
    }

    /**
     * Guardar nueva nota de venta
     * Replica la lógica de ReceiptController@store para el frontend web
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $rcp = $request->receipt;
        $date_today = Carbon::now();

        // Verificar datos mínimos
        if (empty($rcp['client_id'])) {
            return response()->json(['ok' => false, 'message' => 'Debe seleccionar un cliente'], 422);
        }

        // Verificar que el cliente pertenece al shop
        $client = Client::where('id', $rcp['client_id'])
                        ->where('shop_id', $shop->id)
                        ->first();
        if (!$client) {
            return response()->json(['ok' => false, 'message' => 'Cliente no válido'], 422);
        }

        $es_cotizacion = isset($rcp['quotation']) ? $rcp['quotation'] : 0;
        $es_credito = isset($rcp['credit']) ? $rcp['credit'] : 0;

        // La nota no será finalizada por default
        $finished = 0;
        if (!$es_cotizacion) {
            $finished = ($rcp['received'] >= $rcp['total']) ? 1 : 0;
        }

        // Generar folio automático
        $ultimo_folio = Receipt::where('shop_id', $shop->id)->max('folio');
        $nuevo_folio = $ultimo_folio ? $ultimo_folio + 1 : 1;

        // Crear el receipt
        $receipt = new Receipt();
        $receipt->folio = $nuevo_folio;
        $receipt->shop_id = $shop->id;
        $receipt->client_id = $rcp['client_id'];
        $receipt->created_by = $user->name;
        $receipt->rent_id = $rcp['rent_id'] ?? 0;
        $receipt->type = $rcp['type'] ?? 'venta';
        $receipt->rent_yy = 0;
        $receipt->rent_mm = 0;
        $receipt->rent_periodo = '';
        $receipt->description = $rcp['description'] ?? '';
        $receipt->observation = $rcp['observation'] ?? '';
        $receipt->discount = $rcp['discount'] ?? 0;
        $receipt->discount_concept = $rcp['discount_concept'] ?? '$';
        $receipt->subtotal = $rcp['subtotal'] ?? 0;
        $receipt->total = $rcp['total'] ?? 0;
        $receipt->iva = $rcp['iva'] ?? 0;
        $receipt->finished = $finished;
        $receipt->status = $rcp['status'] ?? 'POR COBRAR';
        $receipt->payment = $rcp['payment'] ?? 'EFECTIVO';
        $receipt->received = $rcp['received'] ?? 0;
        $receipt->origin = 'ADMIN';
        $receipt->quotation = $es_cotizacion;

        if ($es_cotizacion && !empty($rcp['quotation_expiration'])) {
            $ff = Carbon::parse($rcp['quotation_expiration'], 'America/Mexico_City');
            $receipt->quotation_expiration = $ff->format('Y-m-d');
        }

        $receipt->credit = $es_credito;
        if ($es_credito && !empty($rcp['credit_date_notification'])) {
            $c_ff = Carbon::parse($rcp['credit_date_notification'], 'America/Mexico_City');
            $receipt->credit_date_notification = $c_ff->format('Y-m-d');
            $receipt->credit_type = $rcp['credit_type'] ?? 'semanal';
        }

        // Si viene de una tarea, guardar la referencia
        if (!empty($rcp['task_id'])) {
            $receipt->task_id = $rcp['task_id'];
        }

        $receipt->save();

        // Guardar pago parcial si aplica
        // NOTA: Ahora SIEMPRE creamos partial_payment para centralizar ingresos
        //       Documentación: j2b-app/xdev/ventas/PLAN_CENTRALIZACION_PAGOS.md
        if (!$es_cotizacion && $receipt->received > 0) {
            // Validar que no exceda el total (protección contra errores de usuario)
            $monto_a_registrar = min($receipt->received, $receipt->total);

            // Determinar tipo de pago: 'unico' si paga todo, 'inicial' si es parcial
            $payment_type = ($monto_a_registrar >= $receipt->total) ? 'unico' : 'inicial';

            $partial = new PartialPayments();
            $partial->receipt_id = $receipt->id;
            $partial->amount = $monto_a_registrar;
            $partial->payment_type = $payment_type;
            $partial->payment_date = $date_today;
            $partial->save();
        }

        // Guardar info extra
        if (!empty($request->info_extra)) {
            $info_extra = is_string($request->info_extra)
                ? json_decode($request->info_extra, true)
                : $request->info_extra;

            if (!empty($info_extra)) {
                foreach ($info_extra as $field_name => $value) {
                    $receiptInfoExtra = new ReceiptInfoExtra();
                    $receiptInfoExtra->receipt_id = $receipt->id;
                    $receiptInfoExtra->field_name = $field_name;
                    $receiptInfoExtra->value = $value;
                    $receiptInfoExtra->save();
                }
            }
        }

        // Guardar detalle
        $details = is_string($request->detail)
            ? json_decode($request->detail)
            : $request->detail;

        if (!empty($details)) {
            foreach ($details as $data) {
                $product_cost = 0;

                // Si es producto y NO es cotización: descontar stock
                // EXCEPCIÓN: Si viene de tarea (from_task_product_id), el stock ya fue descontado
                $fromTaskProductId = isset($data->from_task_product_id) ? $data->from_task_product_id : null;
                if (!$es_cotizacion && $data->type == 'product') {
                    $qty = $data->qty;
                    $product = Product::find($data->id);
                    if ($product) {
                        // Solo descontar si NO viene de una tarea
                        if (!$fromTaskProductId) {
                            $product->stock = $product->stock - $qty;
                            $product->save();
                        }
                        $product_cost = $product->cost ?? 0;
                    }
                }

                // Si es cotización pero producto, obtener costo
                if ($es_cotizacion && $data->type == 'product') {
                    $product = Product::find($data->id);
                    if ($product) {
                        $product_cost = $product->cost ?? 0;
                    }
                }

                // Si es equipo y NO es cotización: desactivar
                if (!$es_cotizacion && $data->type == 'equipment') {
                    $equipo = RentDetail::find($data->id);
                    if ($equipo) {
                        $equipo->active = 0;
                        $equipo->save();
                    }
                }

                $detail = new ReceiptDetail();
                $detail->receipt_id = $receipt->id;
                $detail->product_id = $data->id;
                $detail->type = $data->type;
                $detail->descripcion = $data->name ?? $data->descripcion ?? '';
                $detail->qty = $data->qty ?? 1;
                $detail->price = $data->cost ?? $data->price ?? 0;
                $detail->cost = $product_cost;
                $detail->discount = $data->discount ?? 0;
                $detail->discount_concept = $data->discount_concept ?? '';
                $detail->subtotal = $data->subtotal ?? 0;
                $detail->from_task_product_id = $fromTaskProductId;
                $detail->save();

                // Si viene de tarea, marcar el task_product como facturado
                if ($fromTaskProductId) {
                    \App\Models\TaskProduct::where('id', $fromTaskProductId)
                        ->update(['receipt_id' => $receipt->id]);
                }
            }
        }

        // Obtener el receipt completo
        $rr = Receipt::with('partialPayments')
                    ->with('infoExtra')
                    ->with('shop')
                    ->with('client')
                    ->findOrFail($receipt->id);

        return response()->json([
            'ok' => true,
            'receipt' => $rr,
            'message' => 'Nota de venta creada exitosamente'
        ]);
    }

    /**
     * Obtener campos extra configurados para la tienda
     */
    public function getExtraFields()
    {
        $user = Auth::user();
        $shop = $user->shop;

        $extraFields = ExtraFieldShop::where('shop_id', $shop->id)
                                       ->where('active', 1)
                                       ->get();

        return response()->json([
            'ok' => true,
            'extra_fields' => $extraFields
        ]);
    }

    /**
     * Obtener servicios para el modal de selección
     */
    public function getServices(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $buscar = $request->buscar ?? '';

        $query = Service::where('active', 1)
                        ->where('shop_id', $shop->id);

        if (!empty($buscar)) {
            $query->where('name', 'like', '%' . $buscar . '%');
        }

        $services = $query->orderBy('id', 'desc')->paginate(10);

        return response()->json($services);
    }

    /**
     * Obtener equipos para el modal de selección
     */
    public function getEquipment(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $buscar = $request->buscar ?? '';

        $query = RentDetail::with('images')
            ->where('active', 1)
            ->where('rent_id', 0)
            ->where('shop_id', $shop->id);

        if (!empty($buscar)) {
            $terms = explode(' ', $buscar);
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQuery) use ($term) {
                        $subQuery->where('trademark', 'like', "%$term%")
                                 ->orWhere('model', 'like', "%$term%")
                                 ->orWhere('serial_number', 'like', "%$term%");
                    });
                }
            });
        }

        $equipments = $query->orderBy('id', 'desc')->paginate(10);

        return response()->json($equipments);
    }

    /**
     * Vista para editar una nota de venta
     */
    public function edit($id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el receipt pertenece al shop
        $receipt = Receipt::where('id', $id)
                          ->where('shop_id', $shop->id)
                          ->firstOrFail();

        return view('admin.receipts.edit', ['receiptId' => $id]);
    }

    /**
     * Vista para ver una nota de venta (solo lectura)
     */
    public function show($id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el receipt pertenece al shop
        $receipt = Receipt::where('id', $id)
                          ->where('shop_id', $shop->id)
                          ->firstOrFail();

        return view('admin.receipts.show', ['receiptId' => $id]);
    }

    /**
     * Obtener stock actual de productos en el detalle del receipt
     * Necesario para edición: stock_disponible = stock_actual + qty_guardada
     */
    public function getStockCurrentDetail($id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el receipt pertenece al shop
        $receipt = Receipt::where('id', $id)
                          ->where('shop_id', $shop->id)
                          ->firstOrFail();

        $detail = ReceiptDetail::where('receipt_id', $id)->get();
        $detail_current_stock = [];

        foreach ($detail as $data) {
            if ($data->type == 'product') {
                $product = Product::find($data->product_id);
                if ($product) {
                    $tmp = [
                        'product_id' => $product->id,
                        'stock' => $product->stock
                    ];
                    array_push($detail_current_stock, $tmp);
                }
            }
        }

        return response()->json([
            'ok' => true,
            'detail_current_stock' => $detail_current_stock
        ]);
    }

    /**
     * Actualizar una nota de venta
     * Replica la lógica de ReceiptController@updateReceiptVentas para el frontend web
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $shop = $user->shop;
        $date_today = Carbon::now();

        // Verificar que el receipt pertenece al shop
        $receipt = Receipt::where('id', $id)
                          ->where('shop_id', $shop->id)
                          ->firstOrFail();

        // Verificar que no esté facturado
        if ($receipt->is_tax_invoiced) {
            return response()->json(['ok' => false, 'message' => 'No se puede editar una nota facturada'], 422);
        }

        $rcp = $request->receipt;

        // Verificar datos mínimos
        if (empty($rcp['client_id'])) {
            return response()->json(['ok' => false, 'message' => 'Debe seleccionar un cliente'], 422);
        }

        // Verificar que el cliente pertenece al shop
        $client = Client::where('id', $rcp['client_id'])
                        ->where('shop_id', $shop->id)
                        ->first();
        if (!$client) {
            return response()->json(['ok' => false, 'message' => 'Cliente no válido'], 422);
        }

        $es_cotizacion = isset($rcp['quotation']) ? $rcp['quotation'] : 0;
        $es_credito = isset($rcp['credit']) ? $rcp['credit'] : 0;

        // La nota no será finalizada por default
        $finished = 0;
        if (!$es_cotizacion) {
            $finished = ($rcp['received'] >= $rcp['total']) ? 1 : 0;
        }

        // ==================== RESTAURAR STOCK ANTERIOR ====================
        // Si NO es cotización, devolvemos el stock temporalmente
        // y reactivamos los equipos (por si los eliminan de la nota)
        // EXCEPTO productos que vienen de tarea (from_task_product_id) - ese stock ya fue manejado en la tarea
        if (!$receipt->quotation) {
            $detail_bd = ReceiptDetail::where('receipt_id', $receipt->id)->get();
            foreach ($detail_bd as $dt_bd) {
                if ($dt_bd->type == 'product' && !$dt_bd->from_task_product_id) {
                    $product = Product::find($dt_bd->product_id);
                    if ($product) {
                        $product->stock = $product->stock + $dt_bd->qty;
                        $product->save();
                    }
                }
                if ($dt_bd->type == 'equipment') {
                    $equipo = RentDetail::find($dt_bd->product_id);
                    if ($equipo) {
                        $equipo->active = 1;
                        $equipo->save();
                    }
                }
            }
        }

        // ==================== ACTUALIZAR RECEIPT ====================
        $receipt->client_id = $rcp['client_id'];
        $receipt->rent_id = $rcp['rent_id'] ?? 0;
        $receipt->type = $rcp['type'] ?? 'venta';
        $receipt->description = $rcp['description'] ?? '';
        $receipt->observation = $rcp['observation'] ?? '';
        $receipt->discount = $rcp['discount'] ?? 0;
        $receipt->discount_concept = $rcp['discount_concept'] ?? '$';
        $receipt->subtotal = $rcp['subtotal'] ?? 0;
        $receipt->total = $rcp['total'] ?? 0;
        $receipt->iva = $rcp['iva'] ?? 0;
        $receipt->finished = $finished;
        $receipt->status = $rcp['status'] ?? 'POR COBRAR';
        $receipt->payment = $rcp['payment'] ?? 'EFECTIVO';
        // No actualizamos received aquí para no perder el historial de pagos
        $receipt->quotation = $es_cotizacion;

        if ($es_cotizacion && !empty($rcp['quotation_expiration'])) {
            $ff = Carbon::parse($rcp['quotation_expiration'], 'America/Mexico_City');
            $receipt->quotation_expiration = $ff->format('Y-m-d');
        } else {
            $receipt->quotation_expiration = null;
        }

        $receipt->credit = $es_credito;
        if ($es_credito && !empty($rcp['credit_date_notification'])) {
            $c_ff = Carbon::parse($rcp['credit_date_notification'], 'America/Mexico_City');
            $receipt->credit_date_notification = $c_ff->format('Y-m-d');
            $receipt->credit_type = $rcp['credit_type'] ?? 'semanal';
        } else {
            $receipt->credit_date_notification = null;
            $receipt->credit_type = null;
        }

        $receipt->save();

        // ==================== ACTUALIZAR INFO EXTRA ====================
        ReceiptInfoExtra::where('receipt_id', $receipt->id)->delete();

        if (!empty($request->info_extra)) {
            $info_extra = is_string($request->info_extra)
                ? json_decode($request->info_extra, true)
                : $request->info_extra;

            if (!empty($info_extra)) {
                foreach ($info_extra as $field_name => $value) {
                    $receiptInfoExtra = new ReceiptInfoExtra();
                    $receiptInfoExtra->receipt_id = $receipt->id;
                    $receiptInfoExtra->field_name = $field_name;
                    $receiptInfoExtra->value = $value;
                    $receiptInfoExtra->save();
                }
            }
        }

        // ==================== ACTUALIZAR DETALLE ====================
        ReceiptDetail::where('receipt_id', $receipt->id)->delete();

        $details = is_string($request->detail)
            ? json_decode($request->detail)
            : $request->detail;

        if (!empty($details)) {
            foreach ($details as $data) {
                $product_cost = 0;
                $fromTaskProductId = $data->from_task_product_id ?? null;

                // Si es producto y NO es cotización: descontar stock
                // EXCEPTO si viene de tarea (from_task_product_id) - ese stock ya fue manejado
                if (!$es_cotizacion && $data->type == 'product') {
                    $product = Product::find($data->id);
                    if ($product) {
                        if (!$fromTaskProductId) {
                            $product->stock = $product->stock - $data->qty;
                            $product->save();
                        }
                        $product_cost = $product->cost ?? 0;
                    }
                }

                // Si es cotización pero producto, obtener costo
                if ($es_cotizacion && $data->type == 'product') {
                    $product = Product::find($data->id);
                    if ($product) {
                        $product_cost = $product->cost ?? 0;
                    }
                }

                // Si es equipo y NO es cotización: desactivar
                if (!$es_cotizacion && $data->type == 'equipment') {
                    $equipo = RentDetail::find($data->id);
                    if ($equipo) {
                        $equipo->active = 0;
                        $equipo->save();
                    }
                }

                $detail = new ReceiptDetail();
                $detail->receipt_id = $receipt->id;
                $detail->product_id = $data->id;
                $detail->type = $data->type;
                $detail->descripcion = $data->name ?? $data->descripcion ?? '';
                $detail->qty = $data->qty ?? 1;
                $detail->price = $data->cost ?? $data->price ?? 0;
                $detail->cost = $product_cost;
                $detail->discount = $data->discount ?? 0;
                $detail->discount_concept = $data->discount_concept ?? '';
                $detail->subtotal = $data->subtotal ?? 0;
                $detail->from_task_product_id = $data->from_task_product_id ?? null;
                $detail->save();
            }
        }

        // Obtener el receipt completo
        $rr = Receipt::with('partialPayments')
                    ->with('infoExtra')
                    ->with('shop')
                    ->with('client')
                    ->findOrFail($receipt->id);

        return response()->json([
            'ok' => true,
            'receipt' => $rr,
            'message' => 'Nota actualizada exitosamente'
        ]);
    }

    /**
     * Agregar pago parcial / abono a una nota
     */
    public function storePartialPayment(Request $request, $id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $receipt = Receipt::where('shop_id', $shop->id)
            ->with('partialPayments')
            ->findOrFail($id);

        $suma_actual = $receipt->partialPayments->sum('amount');
        $nueva_suma = $suma_actual + $request->amount;

        // Determinar tipo de pago
        $payment_type = ($nueva_suma >= $receipt->total) ? 'liquidacion' : 'abono';

        $payment = new PartialPayments();
        $payment->receipt_id = $receipt->id;
        $payment->amount = $request->amount;
        $payment->payment_type = $payment_type;
        $payment->payment_date = now();
        $payment->save();

        // Actualizar receipt
        $receipt->received = $nueva_suma;
        if ($nueva_suma >= $receipt->total) {
            $receipt->finished = 1;
            $receipt->status = 'PAGADA';
            if ($receipt->credit) {
                $receipt->credit = 0;
                $receipt->credit_completed = 1;
            }
        }
        $receipt->save();

        $receipt->load('partialPayments');

        return response()->json([
            'ok' => true,
            'receipt' => $receipt
        ]);
    }

    /**
     * Eliminar pago parcial de una nota
     */
    public function deletePartialPayment($paymentId)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $payment = PartialPayments::findOrFail($paymentId);

        // Verificar que el pago pertenece a una nota de esta tienda
        $receipt = Receipt::where('shop_id', $shop->id)
            ->where('id', $payment->receipt_id)
            ->firstOrFail();

        $payment->delete();

        // Recalcular suma de pagos restantes
        $suma_pagos = PartialPayments::where('receipt_id', $receipt->id)->sum('amount');

        $receipt->received = $suma_pagos;
        if ($suma_pagos >= $receipt->total) {
            $receipt->finished = 1;
            $receipt->status = 'PAGADA';
        } else {
            $receipt->finished = 0;
            $receipt->status = 'POR COBRAR';
        }
        $receipt->save();

        $receipt->load('partialPayments');

        return response()->json([
            'ok' => true,
            'receipt' => $receipt
        ]);
    }

    /**
     * Cancelar nota - restaura stock de productos y reactiva equipos
     */
    public function cancelReceipt($id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $receipt = Receipt::where('shop_id', $shop->id)->findOrFail($id);
        $receipt->status = 'CANCELADA';
        $receipt->save();

        // Solo ajustar stock si es nota de venta (no cotización)
        if ($receipt->type == 'venta' && !$receipt->quotation) {
            $detail = ReceiptDetail::where('receipt_id', $receipt->id)->get();
            foreach ($detail as $data) {
                if ($data->type == 'product') {
                    $product = Product::find($data->product_id);
                    if ($product) {
                        $product->stock = $product->stock + $data->qty;
                        $product->save();
                    }
                }
                if ($data->type == 'equipment') {
                    $equipo = RentDetail::find($data->product_id);
                    if ($equipo) {
                        $equipo->active = 1;
                        $equipo->save();
                    }
                }
            }
        }

        return response()->json([
            'ok' => true,
            'receipt' => $receipt
        ]);
    }

    /**
     * Toggle marcar como facturado / no facturado
     */
    public function toggleInvoiced(Request $request, $id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $receipt = Receipt::where('shop_id', $shop->id)->findOrFail($id);
        $receipt->is_tax_invoiced = $request->is_facturado ? true : false;
        $receipt->save();

        return response()->json([
            'ok' => true,
            'receipt' => $receipt
        ]);
    }

    /**
     * Convertir cotización a nota de venta - descuenta stock
     */
    public function convertToSale($id)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $receipt = Receipt::where('shop_id', $shop->id)->findOrFail($id);
        $receipt->quotation = 0;
        $receipt->quotation_expiration = null;
        $receipt->save();

        // Descontar stock al pasar a venta
        $detail = ReceiptDetail::where('receipt_id', $receipt->id)->get();
        foreach ($detail as $data) {
            if ($data->type == 'product') {
                $product = Product::find($data->product_id);
                if ($product) {
                    $new_stock = $product->stock - $data->qty;
                    $product->stock = max(0, $new_stock);
                    $product->save();
                }
            }
        }

        $receipt->load('partialPayments');

        return response()->json([
            'ok' => true,
            'receipt' => $receipt
        ]);
    }
}
