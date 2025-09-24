<?php

namespace App\Http\Controllers\Clients;

use Illuminate\Http\Request;
use App\Models\Receipt;
use App\Models\ReceiptDetail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Controller; // Importa el Controller base

class PurchaseController extends Controller
{
    public function storeNotificationsReceiptForShop(Receipt $receipt){
        //crearemos notificaiones para los distintos admins de la tienda
        $shop_id = $receipt->shop_id;
        $receipt_id = $receipt->id;
        $client_name = $receipt->client->name;
        
        // Generar notification_group_id único para esta compra
        $notification_group_id = Notification::generateGroupId();
        
        //Obtenemos los usuarios tipo admin o superadmin dela tienda
        $shop_users_admin = User::where('shop_id', $shop_id)
                                ->whereHas('roles', function($query) {
                                    $query->whereIn('role_user.role_id', [1, 2]);
                                })
                                ->where('active', 1)
                                ->get();

        foreach($shop_users_admin as $user){
            $new_ntf = new Notification();
            $new_ntf->notification_group_id = $notification_group_id;
            $new_ntf->user_id     = $user->id;
            $new_ntf->description = 'Compra: '.$client_name;
            $new_ntf->type        = 'client_purchase';
            $new_ntf->action      = 'receipt_id';
            $new_ntf->data        = $receipt_id;
            $new_ntf->read        = 0;
            $new_ntf->visible     = 1;
            $new_ntf->save();
        }
    }//storeNotificationsReceiptForShop()

    public function getPurchasesClient(Request $request){
        $user = $request->user();
        $shop = $user->shop;
        $client_id=$user->client->id;
        $receipts = Receipt::with('partialPayments')
                        ->with('infoExtra')
                        ->with('shop')
                        ->with('client')
                        ->where('shop_id',$shop->id)
                        ->where('client_id',$client_id)
                        ->orderBy('id','desc')
                        ->paginate(10);
        return $receipts;
    }//getPurchasesClient()

    public function store(Request $request){
        $user = $request->user();
        $shop = $user->shop;
        $rcp  = $request->purchase_order;
        $date_today     = Carbon::now();
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
        $receipt->shop_id     = $shop->id;
        $receipt->client_id   = $rcp['client_id'];
        $receipt->type        = 'venta';
        $receipt->observation = $rcp['observation'];
        $receipt->subtotal    = $rcp['total'];//en este caso especifico el total es le subtotal
        $receipt->total       = $rcp['total'];
        $receipt->finished    = 0;
        $receipt->status      = 'NUEVA COMPRA';
        $receipt->origin      = 'CLIENT';
        $receipt->save();

        //Guardaremos el detalle de la nota
        $details = json_decode($request->detail);

        foreach($details as $data){

            $detail = new ReceiptDetail();
            $detail->receipt_id  = $receipt->id;
            $detail->product_id  = $data->id;
            $detail->type        = $data->type;
            $detail->descripcion = $data->name;
            $detail->qty         = $data->qty;
            $detail->price       = $data->cost;
            $detail->subtotal    = $data->subtotal;
            $detail->image       = $data->image;
            $detail->save();
        }//.foreach


        //Obtenemos el recibo recien guardado para obtener la relacion de de pagos parciales
        $receipt->load('detail');
        $receipt->load('partialPayments');
        $receipt->load('infoExtra');
        $receipt->load('shop');
        $receipt->load('client');

        $this->storeNotificationsReceiptForShop($receipt);

        return response()->json([
            'ok'=>true,
            'receipt' => $receipt
        ]);
    }//store()


    public function update(Request $request){
        $rcp = $request->receipt;
        $date_today     = Carbon::now();

        //SOLO MODIFICAREMOS POCA INFO, YA QUER CLIENET; ESTATTUS, TIPO, ETC no deberian estar modificadas
        $receipt = Receipt::findOrFail($rcp['receipt_id']);
        $receipt->observation = $rcp['observation'];
        $receipt->subtotal    = $rcp['subtotal'];
        $receipt->total       = $rcp['total'];
        $receipt->save();


        //Eliminamos el detail actual para luego agregar el nuevo
        ReceiptDetail::where('receipt_id', $receipt->id)->delete();
        //Guardaremos el detalle de la nota
        $details = json_decode($request->detail);
        foreach($details as $data){
            $detail = new ReceiptDetail();
            $detail->receipt_id  = $receipt->id;
            $detail->product_id  = $data->id;
            $detail->type        = $data->type;
            $detail->descripcion = $data->name;
            $detail->qty         = $data->qty;
            $detail->price       = $data->cost;
            $detail->subtotal    = $data->subtotal;
            $detail->image       = $data->image;
            $detail->save();
        }//.foreach


        $receipt->load('detail');
        $receipt->load('partialPayments');
        $receipt->load('infoExtra');
        $receipt->load('shop');
        $receipt->load('client');

        return response()->json([
                'ok'=>true,
                'receipt' => $receipt,
        ]);
    }//update()
}
