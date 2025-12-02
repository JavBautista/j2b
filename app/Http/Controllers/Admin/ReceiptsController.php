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

        $query = Receipt::with(['partialPayments', 'shop', 'detail', 'client'])
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

        $receipt->save();

        // Guardar pago parcial si aplica
        if (!$es_cotizacion && !$finished && $receipt->received > 0) {
            $partial = new PartialPayments();
            $partial->receipt_id = $receipt->id;
            $partial->amount = $receipt->received;
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
                if (!$es_cotizacion && $data->type == 'product') {
                    $qty = $data->qty;
                    $product = Product::find($data->id);
                    if ($product) {
                        $product->stock = $product->stock - $qty;
                        $product->save();
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
}
