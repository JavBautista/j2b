<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;
use App\Models\ReceiptDetail;
use App\Models\ReceiptInfoExtra;
use App\Models\RentDetail;
use App\Models\PartialPayments;
use App\Models\Shop;
use App\Models\Rent;
use App\Models\Product;
use Illuminate\Support\Carbon;
use App\Models\ShopReceiptSetting;
use App\Models\PdfPhrase;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Receipts\ReceiptTaxCalculator;

class ReceiptController extends Controller
{
    private function removeSpecialChar($str){
        $res = preg_replace('/[@\.\;\" "]+/', '_', $str);
        return $res;
    }//removeSpecialChar()

    public function index(Request $request){
        $client_id = $request->client_id;
        $receipts = Receipt::with('partialPayments')
                        ->with('shop')
                        ->with('detail')
                        ->with('client')
                        ->where('client_id',$client_id)
                        ->orderBy('id','desc')
                        ->paginate(10);
        return $receipts;
    }//index()

    public function printReceiptRent(Request $request){
        if(!isset($request->id)) return null;
        /*ESTE DEBE LLEGAR POR REQUEST O OBTENERSE DEL RECEIPT*/
        //$shop_id = 1;
        /*....*/

        $id= $request->id;
        $name_file = $this->removeSpecialChar($request->name_file);
        $withImages = $request->has('with_images') && $request->with_images == '1';

        $receipt = Receipt::with('partialPayments')
                            ->with('shop')
                            ->with('detail')
                            ->with('client')
                            ->findOrFail($id);

        // Si se solicitan imágenes, cargar la imagen de cada producto/servicio en el detalle
        if ($withImages) {
            foreach ($receipt->detail as $detail) {
                $detail->image = null;
                if ($detail->type === 'product') {
                    $product = Product::find($detail->product_id);
                    if ($product && $product->image) {
                        $detail->image = $product->image;
                    }
                } elseif ($detail->type === 'equipment') {
                    $equipo = RentDetail::with('images')->find($detail->product_id);
                    if ($equipo && $equipo->images && count($equipo->images) > 0) {
                        $detail->image = $equipo->images[0]->image;
                    }
                }
            }
        }

        //$shop = Shop::findOrFail($shop_id);

        // Configuración de recibos PDF
        $receiptSettings = ShopReceiptSetting::where('shop_id', $receipt->shop_id)->first();

        // Generar QR dinámico si está habilitado
        $qrImage = null;
        if ($receiptSettings === null || $receiptSettings->show_qr) {
            $qrUrlSource = $receiptSettings->qr_url_source ?? 'web';
            $qrUrl = $receipt->shop->$qrUrlSource ?? '';

            if (!empty(trim($qrUrl))) {
                $qrImage = 'data:image/svg+xml;base64,' . base64_encode(
                    QrCode::size(150)->generate($qrUrl)
                );
            }
        }

        $randomPhrase = PdfPhrase::getRandom();

        // Cuentas bancarias para depósito: solo se muestran si la nota tiene saldo pendiente
        // o es a crédito/PPD. La plantilla ya valida visualmente, pero filtramos aquí también.
        $tienePendiente = Receipt::montoMenor($receipt->received, $receipt->total)
            || ((bool) ($receipt->credit ?? false) && !($receipt->credit_completed ?? false));
        $bankAccounts = $tienePendiente
            ? \App\Models\ShopBankAccount::where('shop_id', $receipt->shop_id)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('alias')
                ->get()
            : collect();

        $pdf = PDF::loadView($receipt->shop->pdfView('receipt'), [
            'receipt' => $receipt,
            'withImages' => $withImages,
            'receiptSettings' => $receiptSettings,
            'qrImage' => $qrImage,
            'pdfPhrase' => $randomPhrase['phrase'],
            'pdfPhraseUrl' => $randomPhrase['link_url'],
            'bankAccounts' => $bankAccounts,
        ]);
        return $pdf->stream($name_file.'.pdf',array("Attachment" => false));
    }//printReceiptRent()

    public function getAll(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $filtro_type_receipt = $request->filtro_type_receipt;
        $filtro_type_receipt = strtolower($filtro_type_receipt);

        $filtro_origin_receipt = $request->filtro_origin_receipt;

        // Búsquedas independientes
        $buscar_cliente = isset($request->buscar_cliente) ? trim($request->buscar_cliente) : '';
        $buscar_articulo = isset($request->buscar_articulo) ? trim($request->buscar_articulo) : '';
        $buscar_folio = isset($request->buscar_folio) ? trim($request->buscar_folio) : '';

        $quotation = (isset($request->type_cotizacion) && $request->type_cotizacion == 'true') ? 1 : 0;

        // Filtros dinámicos por campos extras
        $extra_filters = [];
        if ($request->has('extra_filters') && !empty($request->extra_filters)) {
            $extra_filters = json_decode($request->extra_filters, true) ?? [];
        }

        $where_type = ($filtro_type_receipt == 'todos') ? null : $filtro_type_receipt;
        $where_origin = ($filtro_origin_receipt == 'TODOS') ? null : $filtro_origin_receipt;

        $receipts = Receipt::with('partialPayments')
                        ->with('infoExtra')
                        ->with('shop')
                        ->with('client')

                        // ✅ NUEVO: Filtro por CLIENTE (si existe)
                        ->when(!empty($buscar_cliente), function ($query) use($buscar_cliente) {
                            return $query->whereHas('client', function (Builder $subquery) use($buscar_cliente) {
                                $subquery->where('name', 'like', '%'.$buscar_cliente.'%');
                            });
                        })

                        // Filtro por ARTÍCULO (si existe)
                        ->when(!empty($buscar_articulo), function ($query) use($buscar_articulo) {
                            return $query->whereHas('detail', function (Builder $subquery) use($buscar_articulo) {
                                $subquery->where('descripcion', 'like', '%'.$buscar_articulo.'%');
                            });
                        })

                        // Filtro por FOLIO (si existe)
                        ->when(!empty($buscar_folio), function ($query) use($buscar_folio) {
                            return $query->where('folio', $buscar_folio);
                        })

                        // Filtros existentes
                        ->where('shop_id', $shop->id)
                        ->where('quotation', $quotation)
                        ->when($request->status != 'TODOS', function ($query) use($request) {
                            return $query->where('status', $request->status);
                        })
                        ->when($where_type, function ($query, $where_type) {
                            return $query->where('type', $where_type);
                        })
                        ->when($where_origin, function ($query, $where_origin) {
                            return $query->where('origin', $where_origin);
                        })
                        // Filtros dinámicos por campos extras
                        ->when(!empty($extra_filters), function ($query) use ($extra_filters) {
                            foreach ($extra_filters as $filter) {
                                if (!empty($filter['value'])) {
                                    $query->whereHas('infoExtra', function ($q) use ($filter) {
                                        $q->where('field_name', $filter['field_name'])
                                          ->where('value', 'like', '%' . $filter['value'] . '%');
                                    });
                                }
                            }
                            return $query;
                        })
                        ->orderBy('id', 'desc')
                        ->paginate(10);

        return $receipts;

    }//getAll()

    public function descontarInventario(Receipt $receipt){
        $details = $receipt->detail;

        foreach ($details as $data) {
            // Si es un PRODUCTO y NO es cotización: Actualizamos el Stock Inventario
            if ($data->type == 'product') {
                $product = Product::find($data->product_id);
                if ($product) { // Verificar si el producto existe
                    $product->stock -= $data->qty;
                    $product->save();
                }
            }

            // Si es un EQUIPO y NO es cotización: Actualizamos el estatus del equipo
            if ($data->type == 'equipment') {
                $equipo = RentDetail::find($data->product_id);
                if ($equipo) { // Verificar si el equipo existe
                    $equipo->active = 0;
                    $equipo->save();
                }
            }
        }//.foreach
    }

    public function store(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $rcp = $request->receipt;
        $date_today     = Carbon::now();

        $detailList = is_string($request->detail) ? json_decode($request->detail) : $request->detail;
        $itemsValidar = is_array($detailList) ? $detailList : (is_object($detailList) ? (array) $detailList : []);

        // Recálculo de IVA/subtotal/total en backend (fuente de verdad: Shop.tax_rate).
        // No confiamos en los valores enviados por el cliente: pueden venir desfasados
        // si el dispositivo tiene cache vieja de tax_rate o si fueron manipulados.
        $itemsCalc = array_map(function($it) {
            $o = is_array($it) ? $it : (array) $it;
            return [
                'qty'              => $o['qty'] ?? 0,
                'price'            => $o['cost'] ?? 0,
                'discount'         => $o['discount'] ?? 0,
                'is_complimentary' => !empty($o['is_complimentary']),
            ];
        }, $itemsValidar);

        $aplicarIva = floatval($rcp['iva'] ?? 0) > 0;
        $calc = ReceiptTaxCalculator::calcular(
            $shop,
            $itemsCalc,
            floatval($rcp['discount'] ?? 0),
            $aplicarIva
        );

        $cmp = ReceiptTaxCalculator::compararConCliente(
            $calc,
            floatval($rcp['subtotal'] ?? 0),
            floatval($rcp['iva'] ?? 0),
            floatval($rcp['total'] ?? 0)
        );
        if ($cmp['discrepancia']) {
            Log::warning('[ReceiptTaxRecalc] store() discrepancia cliente vs backend', [
                'shop_id'       => $shop->id,
                'user_id'       => $user->id ?? null,
                'tax_rate_shop' => $shop->getTaxRate(),
                'cliente'       => [
                    'subtotal' => $rcp['subtotal'] ?? null,
                    'iva'      => $rcp['iva'] ?? null,
                    'total'    => $rcp['total'] ?? null,
                    'discount' => $rcp['discount'] ?? null,
                ],
                'backend'       => [
                    'subtotal' => $calc['subtotal'],
                    'iva'      => $calc['iva'],
                    'total'    => $calc['total'],
                    'discount' => $calc['descuento_global'],
                ],
                'deltas'        => $cmp['deltas'],
            ]);
        }

        // T9: PAGADA exige received >= total (escenario A: store, sin partial_payments aún)
        // Se valida contra el total recalculado en backend, no contra el enviado por cliente.
        $statusEnviado = $rcp['status'] ?? Receipt::STATUS_POR_COBRAR;
        $receivedEnviado = floatval($rcp['received'] ?? 0);
        if ($statusEnviado === Receipt::STATUS_PAGADA && Receipt::montoMenor($receivedEnviado, $calc['total'])) {
            return response()->json(['ok' => false, 'message' => 'No se puede crear la nota como PAGADA si el monto recibido es menor al total.'], 422);
        }

        // T8: total >= 0 + regla cortesías (todo contra total recalculado)
        if ($calc['total'] < 0) {
            return response()->json(['ok' => false, 'message' => 'No se puede guardar una nota con total negativo.'], 422);
        }
        if (count($itemsValidar) > 0) {
            $hayNoCortesia = false;
            foreach ($itemsValidar as $d) {
                if (empty($d->is_complimentary)) { $hayNoCortesia = true; break; }
            }
            if ($calc['total'] == 0 && $hayNoCortesia) {
                return response()->json(['ok' => false, 'message' => 'Una nota con total $0 requiere que todos los ítems sean cortesía.'], 422);
            }
            if ($calc['total'] > 0 && !$hayNoCortesia) {
                return response()->json(['ok' => false, 'message' => 'Una nota con total mayor a $0 debe incluir al menos un ítem que no sea cortesía.'], 422);
            }
        }

        //Obtenemos el valor que nos dira si es una cotizacion, si no existe ponemos en 0 el valor
        $es_cotizacion  = isset($rcp['quotation'])?$rcp['quotation']:0;

        //Obtenemos el valor que nos dira si es CRedito, si no existe ponemos en 0 el valor
        $es_credito  = isset($rcp['credit'])?$rcp['credit']:0;

        //La nota no sera finalizada por default (en caso que sea cotizacion se quedara como 0)
        $finished=0;
        //Solo si no es cotizacion avaluaremos si estara finalizada o no
        if(!$es_cotizacion){
            //calculamos si el pago recibido es mayor o igual que el total RECALCULADO de la nota
            $finished = ($receivedEnviado >= $calc['total'])?1:0;
        }

        $rent_periodo='';
        $rent_yy = 0;
        $rent_mm = 0;
        if($rcp['type']=='renta'){
            $rnt = Rent::findOrFail($rcp['rent_id']);

            $dia_corte= $rnt->cutoff;

            $_m= ($rcp['rent_mm']>9)?$rcp['rent_mm']:'0'.$rcp['rent_mm'];
            $_d= ($dia_corte>9)?$dia_corte:'0'.$dia_corte;

            $f_ini = $rcp['rent_yy'].'-'.$_m.'-'.$_d;
            $f_fin = $rcp['rent_yy'].'-'.$_m.'-'.$_d;

            $fecha_corte_ini = Carbon::createFromFormat('Y-m-d', $f_ini);
            $fecha_corte_fin = Carbon::createFromFormat('Y-m-d', $f_fin);


            $fecha_corte_fin = $fecha_corte_fin->addMonth()->subDay();

            setlocale(LC_TIME, 'es_ES.UTF-8');
            Carbon::setLocale('es');
            $fecha_corte_ini->locale('es');
            $fecha_corte_fin->locale('es');

            //$desc1 = $fecha_corte_ini->formatLocalized('%d de %B del %Y');
            //$desc2 = $fecha_corte_fin->formatLocalized('%d de %B del %Y');

            $desc1 = $fecha_corte_ini->isoFormat('DD [de] MMMM [del] YYYY');
            $desc2 = $fecha_corte_fin->isoFormat('DD [de] MMMM [del] YYYY');


            $rent_periodo = 'Periodo del  '.$desc1.' al '.$desc2;

            $rent_periodo=strtoupper($rent_periodo);

            $rent_yy=$rcp['rent_yy'];
            $rent_mm=$rcp['rent_mm'];
        }

        //Guardamos todos los datos de la NOTA, deben de venir desde la APP con algun valor
        // El folio se asigna abajo dentro de una transacción con lock para evitar duplicados.
        $receipt = new Receipt();

        $receipt->shop_id     = $shop->id;
        $receipt->client_id   = $rcp['client_id'];
        $receipt->created_by  = $user->name;
        $receipt->rent_id     = $rcp['rent_id'];
        $receipt->type        = $rcp['type'];

        $receipt->rent_yy     = $rent_yy;
        $receipt->rent_mm     = $rent_mm;

        $receipt->rent_periodo= $rent_periodo;

        $receipt->description = $rcp['description'];
        $receipt->observation = $rcp['observation'];
        $receipt->discount_concept = $rcp['discount_concept'];

        $receipt->subtotal    = $calc['subtotal'];
        $receipt->iva         = $calc['iva'];
        $receipt->total       = $calc['total'];
        $receipt->discount    = $calc['descuento_global'];
        $receipt->finished    = $finished;

        //Campos para ventas (Renta o Ventas)
        $receipt->status      = $rcp['status'];
        $receipt->payment     = $rcp['payment'];
        $receipt->received    = $rcp['received'];
        $receipt->origin      = 'ADMIN';

        //Campos para cotizaciones
        //Solo si es una cotizacion guardemos la fecha, si no se guarda por default NULL en el insert BD
        $receipt->quotation   = $es_cotizacion;
        if($es_cotizacion){
            $ff = Carbon::parse($rcp['quotation_expiration'], 'America/Mexico_City');
            $exp = $ff->format('Y-m-d');
            $receipt->quotation_expiration = $exp;
        }

        //Solo si es una credito guardemos la fecha, si no se guarda por default NULL en el insert BD
        $receipt->credit = $es_credito;
        if($es_credito){
            // Asegúrate de que la fecha enviada esté en la zona horaria correcta
            $c_ff = Carbon::parse($rcp['credit_date_notification'], 'America/Mexico_City');
            $c_exp = $c_ff->format('Y-m-d');

            $receipt->credit_date_notification = $c_exp;
            //El tipo de credito puede ser: 'semanal', 'quincenal', 'mensual'
            $receipt->credit_type = $rcp['credit_type'];
        }

        // T12: Si pasa a PAGADA, forzar credit=0 + credit_completed=1
        if ($receipt->status === Receipt::STATUS_PAGADA && $receipt->credit) {
            $receipt->credit = 0;
            $receipt->credit_completed = 1;
        }

        // Asignación de folio + persistencia en transacción con lock pesimista
        // sobre la fila del shop. Serializa la creación de recibos por tienda
        // y evita folios duplicados ante doble click / reintentos de red.
        DB::transaction(function() use($shop, $receipt) {
            Shop::where('id', $shop->id)->lockForUpdate()->first();
            $ultimo_folio = Receipt::where('shop_id', $shop->id)->max('folio');
            $receipt->folio = $ultimo_folio ? $ultimo_folio + 1 : 1;
            $receipt->save();
        });

        //Guardaremos pagos parciales si:
        //1 NO es cotizacion
        //2 El RECIBIDO sea mayor que 0
        //NOTA: Ahora SIEMPRE creamos partial_payment para centralizar ingresos
        //      Documentación: j2b-app/xdev/ventas/PLAN_CENTRALIZACION_PAGOS.md
        if(!$es_cotizacion && $receipt->received > 0){
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
        }//.if(Pagos - Centralización de ingresos)

        /************************************************
        *NUEVO BLOQUE PARA GUARDAR LA INFO EXTRA DEL RECEIPT
        */
        $info_extra = json_decode($request->info_extra, true);
        if (!empty($info_extra)) {
            foreach ($info_extra as $field_name => $value) {
                $receiptInfoExtra = new ReceiptInfoExtra();
                $receiptInfoExtra->receipt_id = $receipt->id; // Asignar el ID del recibo
                $receiptInfoExtra->field_name = $field_name; // Asignar el nombre del campo
                $receiptInfoExtra->value = $value; // Asignar el valor del campo
                $receiptInfoExtra->save(); // Guardar el modelo en la base de datos
            }
        }
        /************************************************/

        //Guardaremos el detalle de la nota
        $details = json_decode($request->detail);

        foreach($details as $idx => $data){
            // Variable para guardar el costo del producto (para reportes de utilidad)
            $product_cost = 0;

            //Si es un PRODUCTO y NO es un cotizacion: Actualizamos el Stock Inventario
            if(!$es_cotizacion && $data->type=='product'){
                $qty=$data->qty;
                $product = Product::find($data->id);
                if ($product) {
                    $new_stock = $product->stock - $qty;
                    $product->stock = $new_stock;
                    $product->save();
                    // Guardamos el costo actual del producto
                    $product_cost = $product->cost ?? 0;
                }
            }//.if(!$es_cotizacion && $data->type=='product')

            // Si es cotización pero es producto, igual obtenemos el costo
            if($es_cotizacion && $data->type=='product'){
                $product = Product::find($data->id);
                if ($product) {
                    $product_cost = $product->cost ?? 0;
                }
            }

            //Si es un EQUIPO de venta y NO es un cotizacion: Actualizamos el estatus del equipo
            if(!$es_cotizacion && $data->type=='equipment'){
                $equipo = RentDetail::find($data->id);
                $equipo->active = 0;
                $equipo->save();
            }//.if(!$es_cotizacion && $data->type=='equipment')

            $isComplimentary = !empty($data->is_complimentary);

            $detail = new ReceiptDetail();
            $detail->receipt_id  = $receipt->id;
            $detail->product_id  = $data->id;
            $detail->type        = $data->type;
            $detail->descripcion = $data->name;
            $detail->qty         = $data->qty;
            $detail->price       = $data->cost;
            $detail->cost        = $product_cost; // Costo del producto al momento de la venta
            $detail->discount    = $data->discount;
            $detail->discount_concept = $data->discount_concept;
            $detail->is_complimentary = $isComplimentary;
            // Subtotal de línea recalculado por backend (cortesía=0, demás = qty*price - discount)
            $detail->subtotal    = $calc['detail_subtotals'][$idx] ?? 0;
            $detail->save();
        }//.foreach

        //Actualizamos ocontadores solo si es una renta y por si las moscas que no sea una cotizacion
        if(!$es_cotizacion && $rcp['type']=='renta'){
            $eq_new_counts = json_decode($request->eq_new_counts);
            foreach($eq_new_counts as $enc){
                $rent_equipo = RentDetail::findOrFail( $enc->equipo_id);
                if($rent_equipo->monochrome)
                    $rent_equipo->counter_mono  =  $enc->equipo_new_count_monochrome;
                if($rent_equipo->color)
                    $rent_equipo->counter_color =  $enc->equipo_new_count_color;
                $rent_equipo->save();
            }//. foreach($eq_new_counts as $enc)
        }//.if(renta)

        //Obtenemos el recibo recien guardado para obtener la relacion de de pagos parciales
        $rr = Receipt::with('partialPayments')
                    ->with('infoExtra')
                    ->with('shop')
                    ->with('client')
                    ->findOrFail($receipt->id);

        return response()->json([
            'ok'=>true,
            'receipt' => $rr,
        ]);
    }//store()

    public function updateReceiptVentas(Request $request){
        $rcp = $request->receipt;
        $date_today     = Carbon::now();
        //Obtenemos el valor que nos dira si es una cotizacion, si no existe ponemos en 0 el valor
        $es_cotizacion  = isset($rcp['quotation'])?$rcp['quotation']:0;

        //Obtenemos el valor que nos dira si es una credito, si no existe ponemos en 0 el valor
        $es_credito  = isset($rcp['credit'])?$rcp['credit']:0;

        //ACtualizamos todos los datos de la NOTA,
        //deben de venir desde la APP con algun valor
        $receipt = Receipt::findOrFail($rcp['receipt_id']);
        $shop = $receipt->shop;

        $detailList = is_string($request->detail) ? json_decode($request->detail) : $request->detail;
        $itemsValidar = is_array($detailList) ? $detailList : (is_object($detailList) ? (array) $detailList : []);

        // Recálculo de IVA/subtotal/total en backend (fuente de verdad: Shop.tax_rate).
        $itemsCalc = array_map(function($it) {
            $o = is_array($it) ? $it : (array) $it;
            return [
                'qty'              => $o['qty'] ?? 0,
                'price'            => $o['cost'] ?? 0,
                'discount'         => $o['discount'] ?? 0,
                'is_complimentary' => !empty($o['is_complimentary']),
            ];
        }, $itemsValidar);

        $aplicarIva = floatval($rcp['iva'] ?? 0) > 0;
        $calc = ReceiptTaxCalculator::calcular(
            $shop,
            $itemsCalc,
            floatval($rcp['discount'] ?? 0),
            $aplicarIva
        );

        $cmp = ReceiptTaxCalculator::compararConCliente(
            $calc,
            floatval($rcp['subtotal'] ?? 0),
            floatval($rcp['iva'] ?? 0),
            floatval($rcp['total'] ?? 0)
        );
        if ($cmp['discrepancia']) {
            Log::warning('[ReceiptTaxRecalc] updateReceiptVentas() discrepancia cliente vs backend', [
                'receipt_id'    => $receipt->id,
                'shop_id'       => $shop->id,
                'tax_rate_shop' => $shop->getTaxRate(),
                'cliente'       => [
                    'subtotal' => $rcp['subtotal'] ?? null,
                    'iva'      => $rcp['iva'] ?? null,
                    'total'    => $rcp['total'] ?? null,
                    'discount' => $rcp['discount'] ?? null,
                ],
                'backend'       => [
                    'subtotal' => $calc['subtotal'],
                    'iva'      => $calc['iva'],
                    'total'    => $calc['total'],
                    'discount' => $calc['descuento_global'],
                ],
                'deltas'        => $cmp['deltas'],
            ]);
        }

        //La nota no sera finalizada por default (en caso que sea cotizacion se quedara como 0)
        $finished=0;
        //Solo si no es cotizacion avaluaremos si estara finalizada o no
        if(!$es_cotizacion){
            //calculamos si el pago recibido es mayor o igual que el total RECALCULADO
            $finished = (floatval($rcp['received'] ?? 0) >= $calc['total'])?1:0;
        }

        // T9: PAGADA exige received >= total (escenario B: update, fuente real $receipt->received de BD)
        // Se valida contra el total recalculado en backend.
        $statusEnviado = $rcp['status'] ?? $receipt->status;
        if ($statusEnviado === Receipt::STATUS_PAGADA && Receipt::montoMenor($receipt->received, $calc['total'])) {
            return response()->json(['ok' => false, 'message' => 'No se puede marcar como PAGADA si el monto recibido es menor al total.'], 422);
        }

        // T8: total >= 0 + regla cortesías (contra total recalculado)
        if ($calc['total'] < 0) {
            return response()->json(['ok' => false, 'message' => 'No se puede guardar una nota con total negativo.'], 422);
        }
        if (count($itemsValidar) > 0) {
            $hayNoCortesia = false;
            foreach ($itemsValidar as $d) {
                if (empty($d->is_complimentary)) { $hayNoCortesia = true; break; }
            }
            if ($calc['total'] == 0 && $hayNoCortesia) {
                return response()->json(['ok' => false, 'message' => 'Una nota con total $0 requiere que todos los ítems sean cortesía.'], 422);
            }
            if ($calc['total'] > 0 && !$hayNoCortesia) {
                return response()->json(['ok' => false, 'message' => 'Una nota con total mayor a $0 debe incluir al menos un ítem que no sea cortesía.'], 422);
            }
        }

        $receipt->client_id   = $rcp['client_id'];

        // PROTECCIÓN: Si el recibo es de tipo renta, preservar campos críticos
        if ($receipt->type === 'renta') {
            // No permitir cambiar type, rent_id ni observation en rentas
            // Estos valores se generan automáticamente al crear la renta
        } else {
            $receipt->rent_id     = $rcp['rent_id'];
            $receipt->type        = $rcp['type'];
            $receipt->observation = $rcp['observation'];
        }

        $receipt->description = $rcp['description'];
        $receipt->discount_concept = $rcp['discount_concept'];
        //--- Valores recalculados en backend (fuente de verdad: Shop.tax_rate)
        $receipt->subtotal    = $calc['subtotal'];
        $receipt->iva         = $calc['iva'];
        $receipt->total       = $calc['total'];
        $receipt->discount    = $calc['descuento_global'];
        $receipt->finished    = $finished;

        //Campos para ventas (Renta o Ventas)
        $receipt->status      = $rcp['status'];
        $receipt->payment     = $rcp['payment'];
        //$receipt->received    = $rcp['received'];
        //Campos para cotizaciones
        $receipt->quotation   = $es_cotizacion;
        //Si es una cotizacion guardemos la fecha, si no debe ser NULL
        if($es_cotizacion){
            $ff = Carbon::parse($rcp['quotation_expiration'], 'America/Mexico_City');
            $exp = $ff->format('Y-m-d');
            $receipt->quotation_expiration = $exp;
        }else{
            $receipt->quotation_expiration = null;
        }

        //Solo si es una credito guardemos la fecha, si no se guarda por default NULL en el insert BD
        $receipt->credit = $es_credito;
        if($es_credito){
            // Asegúrate de que la fecha enviada esté en la zona horaria correcta
            $c_ff = Carbon::parse($rcp['credit_date_notification'], 'America/Mexico_City');
            $c_exp = $c_ff->format('Y-m-d');

            $receipt->credit_date_notification = $c_exp;
            $receipt->credit_type = $rcp['credit_type'];
        }else{
            $receipt->credit_date_notification = null;
            $receipt->credit_type = null;
            $receipt->credit_completed = 0;
        }

        // T12: Si pasa a PAGADA, forzar credit=0 + credit_completed=1
        if ($receipt->status === Receipt::STATUS_PAGADA && $receipt->credit) {
            $receipt->credit = 0;
            $receipt->credit_completed = 1;
        }

        $receipt->save();


        /*-----------------------------------------*/
        //ELIMINAR EL DETAIL Y VOLVER A GUARDAR
        //Si no es cotizacion devolveremos el stock temnporalmente el stock
        //y en el caso de los equipos lo volvemos a activar
        //Esto es por si los eliminan d ela nota y ya no los vuelven a meter
        if(!$es_cotizacion){
            //obtenemos el detalle actua de la BD
            $detail_bd=ReceiptDetail::where('receipt_id', $receipt->id)->get();
            foreach ($detail_bd as $dt_bd){
                //solo si item del detalle es producto
                if($dt_bd->type=='product'){
                    $qty = $dt_bd->qty;
                    $product = Product::find($dt_bd->product_id);
                    $new_stock = $product->stock + $qty;
                    $product->stock = $new_stock;
                    $product->save();
                }//.if(product)
                if($dt_bd->type=='equipment'){
                    $equipo = RentDetail::find($dt_bd->product_id);
                    $equipo->active = 1;
                    $equipo->save();
                }//.if(equipment)
            }
        }
        /*-----------------------------------------*/

        /************************************************
        *NUEVO BLOQUE PARA ACTUALIZAR LA INFO EXTRA DEL RECEIPT
        */
        //Eliminamos la info extra actual para luego agregar la nueva
        ReceiptInfoExtra::where('receipt_id', $receipt->id)->delete();
        $info_extra = json_decode($request->info_extra, true);
        if (!empty($info_extra)) {
            foreach ($info_extra as $extra) {
                $receiptInfoExtra = new ReceiptInfoExtra();
                $receiptInfoExtra->receipt_id = $receipt->id; // Asignar el ID del recibo
                $receiptInfoExtra->field_name = $extra['field_name'];
                $receiptInfoExtra->value = $extra['value'];
                $receiptInfoExtra->save(); // Guardar el modelo en la base de datos
            }
        }
        /************************************************/


        //Eliminamos el detail actual para luego agregar el nuevo
        ReceiptDetail::where('receipt_id', $receipt->id)->delete();
        //Guardaremos el detalle de la nota
        $details = json_decode($request->detail);
        foreach($details as $idx => $data){
            // Variable para guardar el costo del producto (para reportes de utilidad)
            $product_cost = 0;

            //Solo alteraremos el Stock si el item es un producto y la nota NO es un cotizacion
            if(!$es_cotizacion && $data->type=='product'){
                $qty=$data->qty;
                $product = Product::find($data->id);
                if ($product) {
                    $new_stock = $product->stock - $qty;
                    $product->stock = $new_stock;
                    $product->save();
                    // Guardamos el costo actual del producto
                    $product_cost = $product->cost ?? 0;
                }
            }//.if(!$es_cotizacion && $data->type=='product')

            // Si es cotización pero es producto, igual obtenemos el costo
            if($es_cotizacion && $data->type=='product'){
                $product = Product::find($data->id);
                if ($product) {
                    $product_cost = $product->cost ?? 0;
                }
            }

            //Si es un EQUIPO de venta y NO es un cotizacion: Actualizamos el estatus del equipo
            if(!$es_cotizacion && $data->type=='equipment'){
                $equipo = RentDetail::find($data->id);
                $equipo->active = 0;
                $equipo->save();
            }//.if(!$es_cotizacion && $data->type=='equipment')

            $isComplimentary = !empty($data->is_complimentary);

            $detail = new ReceiptDetail();
            $detail->receipt_id  = $receipt->id;
            $detail->product_id  = $data->id;
            $detail->type        = $data->type;
            $detail->descripcion = $data->name;
            $detail->qty         = $data->qty;
            $detail->price       = $data->cost;
            $detail->cost        = $product_cost; // Costo del producto al momento de la venta
            $detail->discount    = $data->discount;
            $detail->discount_concept = $data->discount_concept;
            $detail->is_complimentary = $isComplimentary;
            // Subtotal de línea recalculado por backend
            $detail->subtotal    = $calc['detail_subtotals'][$idx] ?? 0;
            $detail->save();
        }//.foreach

        //Actualizamos ocntadores solo si es una renta y por si las moscas que no sea una cotizacion
        /*
        if(!$es_cotizacion && $rcp['type']=='renta'){
            $eq_new_counts = json_decode($request->eq_new_counts);
            foreach($eq_new_counts as $enc){
                $rent_equipo = RentDetail::findOrFail( $enc->equipo_id);
                if($rent_equipo->monochrome)
                    $rent_equipo->counter_mono  =  $enc->equipo_new_count_monochrome;
                if($rent_equipo->color)
                    $rent_equipo->counter_color =  $enc->equipo_new_count_color;
                $rent_equipo->save();
            }//. foreach($eq_new_counts as $enc)
        }//.if(renta)
        */

        //Obtenemos el recibo recien guardado para obtener la relacion de de pagos parciales
        $rr = Receipt::with('partialPayments')
                    ->with('infoExtra')
                    ->with('shop')
                    ->with('client')
                    ->findOrFail($receipt->id);

        return response()->json([
                'ok'=>true,
                'receipt' => $rr,
        ]);
    }//updateReceiptVentas()

    public function updateStatus(Request $request){
        $receipt = Receipt::with('detail')->findOrFail($request->receipt_id);
        $status_actual = $receipt->status;
        $new_status    = $request->new_status;

        // Guard: status válido
        if(!in_array($new_status, Receipt::statusesValidos())){
            return response()->json([
                'ok'=>false,
                'message'=>'Status no válido. Válidos: '.implode(', ', Receipt::statusesValidos())
            ], 422);
        }

        // Guard: nota timbrada no puede regresar a estados pre-facturación
        if($receipt->is_tax_invoiced && in_array($new_status, [Receipt::STATUS_POR_FACTURAR, Receipt::STATUS_NUEVA_COMPRA])){
            return response()->json([
                'ok'=>false,
                'message'=>'No se puede regresar una nota facturada (CFDI vigente) a "'.$new_status.'".'
            ], 422);
        }

        // Guard: coherencia status PAGADA ↔ pagos
        if($new_status === Receipt::STATUS_PAGADA && Receipt::montoMenor($receipt->received, $receipt->total)){
            return response()->json([
                'ok'=>false,
                'message'=>'No se puede marcar como PAGADA si el monto recibido es menor al total.'
            ], 422);
        }

        if($status_actual==Receipt::STATUS_NUEVA_COMPRA){ $this->descontarInventario($receipt); }

        $receipt->status = $new_status;

        // Regla credit: al pasar a PAGADA forzar credit=0 + credit_completed=1
        if($new_status === Receipt::STATUS_PAGADA && $receipt->credit){
            $receipt->credit = 0;
            $receipt->credit_completed = 1;
        }

        $receipt->save();

        return response()->json([
                'ok'=>true,
                'receipt' => $receipt,
        ]);
    }//updateStatus()

    public function updateQuotationToSale(Request $request){
        $receipt = Receipt::findOrFail($request->receipt_id);
        $receipt->quotation = 0;
        $receipt->quotation_expiration = null;
        $receipt->save();

        //Al pasar la nota de Cotizacion a venta, descontamos el stock
        $detail = ReceiptDetail::where('receipt_id',$receipt->id)->get();
        foreach($detail as $data){
            //solo con los items que sean productos
            if($data->type=='product'){
                $product   = Product::find($data->product_id);
                $new_stock = $product->stock - $data->qty;
                //solo en caso que la resta de numeros negativos
                if($new_stock<0) $new_stock=0;
                $product->stock = $new_stock;
                $product->save();
            }
        }

        return response()->json([
                'ok'=>true,
                'receipt' => $receipt,
        ]);
    }//updateQuotationToSale()

    public function updateInfo(Request $request){
        $rcp = $request->receipt;

        $receipt = Receipt::findOrFail($rcp['id']);
        $shop = $receipt->shop;

        // updateInfo recibe detail PARCIAL (solo items modificados). Para recalcular el
        // total correctamente: (1) aplicamos subtotal recalculado a cada item enviado,
        // (2) reagregamos todos los items del detail en BD, (3) recalculamos el receipt
        // completo. Todo dentro de transacción para mantener consistencia.
        $details = json_decode($request->detail);

        DB::beginTransaction();
        try {
            // 1. Actualizar cada item enviado con subtotal recalculado
            if (is_array($details) || is_object($details)) {
                foreach($details as $data){
                    $detail = ReceiptDetail::findOrFail($data->id);
                    $detail->qty   = $data->qty;
                    $detail->price = $data->price;

                    $isComp = $detail->is_complimentary || !empty($data->is_complimentary);
                    $qty    = max(0.0, (float) $data->qty);
                    $price  = max(0.0, (float) $data->price);
                    $disc   = max(0.0, (float) ($detail->discount ?? 0));
                    $neto   = max(0.0, $qty * $price - $disc);
                    $detail->subtotal = $isComp ? 0.0 : round($neto, 2);
                    $detail->save();
                }
            }

            // 2. Recalcular receipt completo usando TODOS los items en BD
            $itemsBd = ReceiptDetail::where('receipt_id', $receipt->id)->get();
            $itemsCalc = $itemsBd->map(function($d){
                return [
                    'qty'              => $d->qty,
                    'price'            => $d->price,
                    'discount'         => $d->discount ?? 0,
                    'is_complimentary' => (bool) $d->is_complimentary,
                ];
            })->toArray();

            // aplicarIva: preserva el estado actual del receipt (updateInfo no lo toca explícitamente)
            $aplicarIva = floatval($receipt->iva ?? 0) > 0;
            $calc = ReceiptTaxCalculator::calcular(
                $shop,
                $itemsCalc,
                floatval($rcp['discount'] ?? 0),
                $aplicarIva
            );

            $cmp = ReceiptTaxCalculator::compararConCliente(
                $calc,
                floatval($rcp['subtotal'] ?? 0),
                floatval($receipt->iva ?? 0),
                floatval($rcp['total'] ?? 0)
            );
            if ($cmp['discrepancia']) {
                Log::warning('[ReceiptTaxRecalc] updateInfo() discrepancia cliente vs backend', [
                    'receipt_id'    => $receipt->id,
                    'shop_id'       => $shop->id,
                    'tax_rate_shop' => $shop->getTaxRate(),
                    'cliente'       => [
                        'subtotal' => $rcp['subtotal'] ?? null,
                        'total'    => $rcp['total'] ?? null,
                        'discount' => $rcp['discount'] ?? null,
                    ],
                    'backend'       => [
                        'subtotal' => $calc['subtotal'],
                        'iva'      => $calc['iva'],
                        'total'    => $calc['total'],
                        'discount' => $calc['descuento_global'],
                    ],
                    'deltas'        => $cmp['deltas'],
                ]);
            }

            // 3. Validaciones T8/T9 contra total recalculado. Si fallan: rollback.
            $statusEnviado = $rcp['status'] ?? $receipt->status;
            $errorMsg = null;
            if ($statusEnviado === Receipt::STATUS_PAGADA && Receipt::montoMenor($receipt->received, $calc['total'])) {
                $errorMsg = 'No se puede marcar como PAGADA si el monto recibido es menor al total.';
            } elseif ($calc['total'] < 0) {
                $errorMsg = 'No se puede guardar una nota con total negativo.';
            } elseif ($itemsBd->count() > 0) {
                $hayNoCortesia = $itemsBd->where('is_complimentary', false)->count() > 0;
                if ($calc['total'] == 0 && $hayNoCortesia) {
                    $errorMsg = 'Una nota con total $0 requiere que todos los ítems sean cortesía.';
                } elseif ($calc['total'] > 0 && !$hayNoCortesia) {
                    $errorMsg = 'Una nota con total mayor a $0 debe incluir al menos un ítem que no sea cortesía.';
                }
            }
            if ($errorMsg !== null) {
                DB::rollBack();
                return response()->json(['ok' => false, 'message' => $errorMsg], 422);
            }

            // 4. Aplicar al receipt
            $receipt->status      = $rcp['status'];
            $receipt->payment     = $rcp['payment'];
            $receipt->subtotal    = $calc['subtotal'];
            $receipt->iva         = $calc['iva'];
            $receipt->total       = $calc['total'];
            $receipt->discount    = $calc['descuento_global'];
            $receipt->received    = $rcp['received'];
            $receipt->description = $rcp['description'];
            $receipt->observation = $rcp['observation'];

            // T12: Si pasa a PAGADA, forzar credit=0 + credit_completed=1
            if ($receipt->status === Receipt::STATUS_PAGADA && $receipt->credit) {
                $receipt->credit = 0;
                $receipt->credit_completed = 1;
            }

            $receipt->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[ReceiptTaxRecalc] updateInfo() excepción en transacción', [
                'receipt_id' => $receipt->id,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['ok' => false, 'message' => 'Error al actualizar la nota.'], 500);
        }

        return response()->json([
                'ok'=>true,
                'receipt' => $receipt,
        ]);
    }//updateInfo()

    public function test(Request $reques){
        /*
        //$var = "[{"cost":52,"qty":1,"name":"Luis"},{"cost":51,"qty":1,"name":"Luigi Bros"}]";
        //$var = json_decode($reques->detail);
        $var = json_decode($reques->detail);
        foreach($var as $detail){
            echo $detail->name.', '.$detail->qty.'<br>';
        }*/
    }//test()

    public function delete(Request $request){
        //ANTES DE ELIMNAR DE LA BD HAY QUE CORROBORAR QUE NO QUEDE STOCK EN EL LIMBO
        $receipt = Receipt::findOrFail($request->id);

        // Guard: no se puede eliminar una nota con CFDI vigente
        if($receipt->is_tax_invoiced){
            return response()->json([
                'ok'=>false,
                'message'=>'No se puede eliminar una nota facturada (CFDI vigente). Cancele primero el CFDI.'
            ], 422);
        }

        //SOLO HAREMOS EL AJUSTE DE STOCK SI SE ELIMINA UNA NOTA QUE NO ESTE CANCELADA O EN DEVOLUCION
        //SE SUPONE QUE SI YA ESTA CANCELADA O DEVUELTA YA HIZO EL EBIDO AJUSTE EN EL STOCK
        if($receipt->status!=Receipt::STATUS_CANCELADA && $receipt->status!=Receipt::STATUS_DEVOLUCION){

            $es_cotizacion=$receipt->quotation;
            $detail = ReceiptDetail::where('receipt_id',$receipt->id)->get();
            foreach($detail as $data){
                if(!$es_cotizacion){                
                    //solo con los items que sean productos
                    if($data->type=='product'){
                        $qty = $data->qty;
                        $product   = Product::find($data->product_id);
                        $new_stock = $product->stock + $qty;
                        $product->stock = $new_stock;
                        $product->save();
                    }
                    //solo con los items que sean Equipos se activara 
                    //de nuevo es el quivalente a regresarlo al inventario
                    if($data->type=='equipment'){
                        $equipo = RentDetail::find($data->product_id);
                        $equipo->active = 1;
                        $equipo->save();
                    }
                }//si no es cotizacion
            }
        }//.if($receipt->status!='CANCELADA' || $receipt->status!='DEVOLUCION') 

        //Eliminamos los datos relacionados 
        ReceiptDetail::where('receipt_id', $request->id)->delete();
        PartialPayments::where('receipt_id', $request->id)->delete();
        ReceiptInfoExtra::where('receipt_id', $request->id)->delete();

        $receipt=Receipt::destroy($request->id);
        return response()->json([
            'ok'=>true
        ]);
    }//delete()

    public function cancel(Request $request){
        $receipt = Receipt::findOrFail($request->receipt_id);
        $receipt->status=Receipt::STATUS_CANCELADA;
        $receipt->save();
        
        //Solo Si es nota de VENTA y ademas que no sea una COTIZACIÓN debemos ver si hay ajuste de stock
        if($receipt->type == 'venta' && !$receipt->quotation){
            $detail = ReceiptDetail::where('receipt_id',$receipt->id)->get();
            foreach($detail as $data){
                //solo con los items que sean productos
                if($data->type=='product'){
                    $qty = $data->qty;
                    $product   = Product::find($data->product_id);
                    $new_stock = $product->stock + $qty;
                    $product->stock = $new_stock;
                    $product->save();
                }
                //solo con los items que sean Equipos se activara
                //de nuevo es el quivalente a regresarlo al inventario
                if($data->type=='equipment'){
                    $equipo = RentDetail::find($data->product_id);
                    $equipo->active = 1;
                    $equipo->save();
                }
            }//foreach
        }//.if($receipt->type == 'venta' && !$receipt->quotation)

        return response()->json([
                'ok'=>true,
                'receipt' => $receipt,
                'alt'=>' funcion cancel'
        ]);
    }//cancel()

    public function devolucion(Request $request){
        $receipt = Receipt::findOrFail($request->receipt_id);
        $receipt->status=Receipt::STATUS_DEVOLUCION;
        $receipt->save();

        //Solo Si es nota de VENTA y ademas que no sea una COTIZACIÓN debemos ver si hay ajuste de stock
        if($receipt->type == 'venta' && !$receipt->quotation){

            $detail = ReceiptDetail::where('receipt_id',$receipt->id)->get();
            foreach($detail as $data){
                //solo con los items que sean productos
                if($data->type=='product'){
                    $qty = $data->qty;
                    $product   = Product::find($data->product_id);
                    $new_stock = $product->stock + $qty;
                    $product->stock = $new_stock;
                    $product->save();
                }
                //solo con los items que sean Equipos se activara
                //de nuevo es el quivalente a regresarlo al inventario
                if($data->type=='equipment'){
                    $equipo = RentDetail::find($data->product_id);
                    $equipo->active = 1;
                    $equipo->save();
                }
            }
        }//.if($receipt->type == 'venta' && !$receipt->quotation)

        return response()->json([
                'ok'=>true,
                'receipt' => $receipt,
        ]);
    }//devolucion()


    public function updateInvoiced(Request $request, $id){
        $receipt = Receipt::with('detail')->findOrFail($id);
        $receipt->is_tax_invoiced = $request->is_facturado;
        $receipt->save();

        return response()->json([
                'ok'=>true,
                'receipt' => $receipt,
        ]);
    }//updateInvoiced()


    public function createPDFReceiptRent(Request $request, $id){

        $receipt = Receipt::with('partialPayments')
                            ->with('shop')
                            ->with('detail')
                            ->with('client')
                            ->findOrFail($id);

        $name_file = $receipt->folio;

        // Configuración de recibos PDF
        $receiptSettings = ShopReceiptSetting::where('shop_id', $receipt->shop_id)->first();

        // Generar QR dinámico si está habilitado
        $qrImage = null;
        if ($receiptSettings === null || $receiptSettings->show_qr) {
            $qrUrlSource = $receiptSettings->qr_url_source ?? 'web';
            $qrUrl = $receipt->shop->$qrUrlSource ?? '';

            if (!empty(trim($qrUrl))) {
                $qrImage = 'data:image/svg+xml;base64,' . base64_encode(
                    QrCode::size(150)->generate($qrUrl)
                );
            }
        }

        $randomPhrase = PdfPhrase::getRandom();

        $tienePendiente = Receipt::montoMenor($receipt->received, $receipt->total)
            || ((bool) ($receipt->credit ?? false) && !($receipt->credit_completed ?? false));
        $bankAccounts = $tienePendiente
            ? \App\Models\ShopBankAccount::where('shop_id', $receipt->shop_id)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('alias')
                ->get()
            : collect();

        $pdf = PDF::loadView($receipt->shop->pdfView('receipt'),[
            'receipt'=>$receipt,
            'receiptSettings' => $receiptSettings,
            'qrImage' => $qrImage,
            'pdfPhrase' => $randomPhrase['phrase'],
            'pdfPhraseUrl' => $randomPhrase['link_url'],
            'bankAccounts' => $bankAccounts,
        ]);
        return $pdf->stream($name_file.'.pdf',array("Attachment" => false));
    }//printReceiptRent()



}
