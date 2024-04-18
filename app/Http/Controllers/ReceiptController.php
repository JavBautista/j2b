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
use PDF;
use Illuminate\Database\Eloquent\Builder;

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
        $receipt = Receipt::with('partialPayments')
                            ->with('shop')
                            ->with('detail')
                            ->with('client')
                            ->findOrFail($id);

        //$shop = Shop::findOrFail($shop_id);

        $pdf = PDF::loadView('receipt_rent_pdf',['receipt'=>$receipt]);
        return $pdf->stream($name_file.'.pdf',array("Attachment" => false));
    }//printReceiptRent()

    public function getAll(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $filtro_type_receipt = $request->filtro_type_receipt;
        $filtro_type_receipt = strtolower($filtro_type_receipt);

        $filtro_buscar = isset($request->buscar)?trim($request->buscar):'';
        $quotation     = (isset($request->type_cotizacion)&&$request->type_cotizacion=='true')?1:0;

        $where_type =($filtro_type_receipt=='todos')?null:$filtro_type_receipt;

        $receipts = Receipt::with('partialPayments')
                        ->with('infoExtra')
                        ->with('shop')
                        ->with('client')
                        ->whereHas('client', function (Builder $query) use($filtro_buscar) {
                            $query->where('name', 'like', '%'.$filtro_buscar.'%');
                        })
                        ->where('shop_id',$shop->id)
                        ->where('quotation',$quotation)
                        ->when( $request->status!='TODOS', function ($query) use($request) {
                            return $query->where('status',$request->status);
                        })
                        ->when( $where_type, function ($query, $where_type) {
                            return $query->where('type',$where_type);
                        })

                        ->orderBy('id','desc')
                        ->paginate(10);

        return $receipts;
    }//getAll()

    public function store(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $rcp = $request->receipt;
        $date_today     = Carbon::now();
        //Obtenemos el valor que nos dira si es una cotizacion, si no existe ponemos en 0 el valor
        $es_cotizacion  = isset($rcp['quotation'])?$rcp['quotation']:0;
        //La nota no sera finalizada por default (en caso que sea cotizacion se quedara como 0)
        $finished=0;
        //Solo si no es cotizacion avaluaremos si estara finalizada o no
        if(!$es_cotizacion){
            //calculamos si el pago recibido es mayor o igual que el total de la nota
            //finalizamos la nota, si no se tomara como un abono
            $finished = ($rcp['received'] >= $rcp['total'])?1:0;
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


            /*$fecha_corte_ini = $fecha_corte_ini->subMonth()->addDay();
            $fecha_corte_ini = $fecha_corte_ini->format('Y-m-d');
            $fecha_corte_ini = Carbon::createFromFormat('Y-m-d', $fecha_corte_ini);
            */

            setlocale(LC_TIME, 'es_ES.UTF-8');
            Carbon::setLocale('es');
            $fecha_corte_ini->locale('es');
            $fecha_corte_fin->locale('es');
            $desc1 = $fecha_corte_ini->formatLocalized('%d de %B del %Y');
            //$desc2 = $fecha_corte_fin->formatLocalized('%d de %B del %Y');
            $desc2 = $fecha_corte_fin->formatLocalized('%d de %B del %Y');


            $rent_periodo = 'Periodo del  '.$desc1.' al '.$desc2;

            $rent_periodo=strtoupper($rent_periodo);

            $rent_yy=$rcp['rent_yy'];
            $rent_mm=$rcp['rent_mm'];
        }

        //Este bloque es para obtener el ultimo folio de la tienda o inicializarlo en 1
        $ultimo_folio = Receipt::where('shop_id', $shop->id)->max('folio');
        $nuevo_folio = 0;
        if (!$ultimo_folio) {
            // Si no hay folios para esta tienda aún, asignamos el valor 1 al nuevo folio
            $nuevo_folio = 1;
        } else {
            // Si hay folios, asignamos el valor siguiente al último folio
            $nuevo_folio = $ultimo_folio + 1;
        }
        //Guardamos todos los datos de la NOTA, deben de venir desde la APP con algun valor
        $receipt = new Receipt();

        $receipt->folio = $nuevo_folio;

        $receipt->shop_id = $shop->id;
        $receipt->client_id   = $rcp['client_id'];
        $receipt->rent_id     = $rcp['rent_id'];
        $receipt->type        = $rcp['type'];

        $receipt->rent_yy     = $rent_yy;
        $receipt->rent_mm     = $rent_mm;

        $receipt->rent_periodo= $rent_periodo;

        $receipt->description = $rcp['description'];
        $receipt->observation = $rcp['observation'];
        $receipt->discount    = $rcp['discount'];
        $receipt->discount_concept = $rcp['discount_concept'];
        //---
        $receipt->subtotal    = $rcp['subtotal'];
        $receipt->total       = $rcp['total'];
        $receipt->iva         = $rcp['iva'];
        $receipt->finished    = $finished;

        //Campos para ventas (Renta o Ventas)
        $receipt->status      = $rcp['status'];
        $receipt->payment     = $rcp['payment'];
        $receipt->received    = $rcp['received'];
        //Campos para cotizaciones
        $receipt->quotation   = $es_cotizacion;
        //Solo si es una cotizacion guardemos la fecha, si no se guarda por default NULL en el insert BD
        if($es_cotizacion){
            $ff = Carbon::parse($rcp['quotation_expiration']);
            $exp = Carbon::createFromFormat('Y-m-d H:i:s', $ff )->format('Y-m-d');
            $receipt->quotation_expiration = $exp;
        }


        $receipt->save();

        //Guardaremos pagos parciales solo si:
        //1 NO es cotizacion
        //2 NO este finalizada (que lo recibido se menor que total de la nota)
        //3 El RECIBIDO sea mayor que 0 (recordar que la nota si se puede guadar con 0, pero 0 no es un pago parcial)
        if(!$es_cotizacion){
            if(!$finished){
                if($receipt->received>0){
                    $partial= new PartialPayments();
                    $partial->receipt_id = $receipt->id;
                    $partial->amount = $receipt->received;
                    $partial->payment_date = $date_today;
                    $partial->save();
                }
            }
        }//.ifs(Pagos Parciales)

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

        foreach($details as $data){
            //Si es un PRODUCTO y NO es un cotizacion: Actualizamos el Stock Inventario
            if(!$es_cotizacion && $data->type=='product'){
                $qty=$data->qty;
                $product = Product::find($data->id);
                $new_stock = $product->stock - $qty;
                $product->stock = $new_stock;
                $product->save();
            }//.if(!$es_cotizacion && $data->type=='product')

            //Si es un EQUIPO de venta y NO es un cotizacion: Actualizamos el estatus del equipo
            if(!$es_cotizacion && $data->type=='equipment'){
                $equipo = RentDetail::find($data->id);
                $equipo->active = 0;
                $equipo->save();
            }//.if(!$es_cotizacion && $data->type=='equipment')

            $detail = new ReceiptDetail();
            $detail->receipt_id  = $receipt->id;
            $detail->product_id  = $data->id;
            $detail->type        = $data->type;
            $detail->descripcion = $data->name;
            $detail->qty         = $data->qty;
            $detail->price       = $data->cost;
            $detail->discount    = $data->discount;
            $detail->discount_concept = $data->discount_concept;
            $detail->subtotal    = $data->subtotal;
            $detail->save();
        }//.foreach

        //Actualizamos ocntadores solo si es una renta y por si las moscas que no sea una cotizacion
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
        //La nota no sera finalizada por default (en caso que sea cotizacion se quedara como 0)
        $finished=0;
        //Solo si no es cotizacion avaluaremos si estara finalizada o no
        if(!$es_cotizacion){
            //calculamos si el pago recibido es mayor o igual que el total de la nota
            //finalizamos la nota, si no se tomara como un abono
            $finished = ($rcp['received'] >= $rcp['total'])?1:0;
        }

        //ACtualizamos todos los datos de la NOTA,
        //deben de venir desde la APP con algun valor
        $receipt = Receipt::findOrFail($rcp['receipt_id']);
        $receipt->client_id   = $rcp['client_id'];
        $receipt->rent_id     = $rcp['rent_id'];
        $receipt->type        = $rcp['type'];
        $receipt->description = $rcp['description'];
        $receipt->observation = $rcp['observation'];
        $receipt->discount    = $rcp['discount'];
        $receipt->discount_concept = $rcp['discount_concept'];
        //---
        $receipt->subtotal    = $rcp['subtotal'];
        $receipt->total       = $rcp['total'];
        $receipt->iva         = $rcp['iva'];
        $receipt->finished    = $finished;

        //Campos para ventas (Renta o Ventas)
        $receipt->status      = $rcp['status'];
        $receipt->payment     = $rcp['payment'];
        //$receipt->received    = $rcp['received'];
        //Campos para cotizaciones
        $receipt->quotation   = $es_cotizacion;
        //Si es una cotizacion guardemos la fecha, si no debe ser NULL
        if($es_cotizacion){
            $ff = Carbon::parse($rcp['quotation_expiration']);
            $exp = Carbon::createFromFormat('Y-m-d H:i:s', $ff )->format('Y-m-d');
            $receipt->quotation_expiration = $exp;
        }else{
            $receipt->quotation_expiration = null;
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
        foreach($details as $data){
            //Solo alteraremos el Stock si el item es un producto y la nota NO es un cotizacion
            if(!$es_cotizacion && $data->type=='product'){
                $qty=$data->qty;
                $product = Product::find($data->id);
                $new_stock = $product->stock - $qty;
                $product->stock = $new_stock;
                $product->save();
            }//.if(!$es_cotizacion && $data->type=='product')

            //Si es un EQUIPO de venta y NO es un cotizacion: Actualizamos el estatus del equipo
            if(!$es_cotizacion && $data->type=='equipment'){
                $equipo = RentDetail::find($data->id);
                $equipo->active = 0;
                $equipo->save();
            }//.if(!$es_cotizacion && $data->type=='equipment')

            $detail = new ReceiptDetail();
            $detail->receipt_id  = $receipt->id;
            $detail->product_id  = $data->id;
            $detail->type        = $data->type;
            $detail->descripcion = $data->name;
            $detail->qty         = $data->qty;
            $detail->price       = $data->cost;
            $detail->discount    = $data->discount;
            $detail->discount_concept = $data->discount_concept;
            $detail->subtotal    = $data->subtotal;
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
        $receipt = Receipt::findOrFail($request->receipt_id);
        $receipt->status=$request->new_status;
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
        ReceiptDetail::where('receipt_id', $request->id)->delete();
        PartialPayments::where('receipt_id', $request->id)->delete();

        $receipt=Receipt::destroy($request->id);
        return response()->json([
            'ok'=>true
        ]);
    }//delete()

    public function cancel(Request $request){
        $receipt = Receipt::findOrFail($request->receipt_id);
        $receipt->status='CANCELADA';
        $receipt->save();
        return response()->json([
                'ok'=>true,
                'receipt' => $receipt,
        ]);
    }//cancel()

    public function devolucion(Request $request){
        $receipt = Receipt::findOrFail($request->receipt_id);
        $receipt->status='DEVOLUCION';
        $receipt->save();

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
        }

        return response()->json([
                'ok'=>true,
                'receipt' => $receipt,
        ]);
    }//devolucion()
}
