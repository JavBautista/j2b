<?php

namespace App\Http\Controllers\Chatbot;

use App\Models\Receipt;
use App\Models\ReceiptDetail;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Client;
use App\Models\User;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ChatbotController extends Controller
{
    public function storeNotificationsReceiptForShop(Receipt $receipt){
        //crearemos notificaiones para los distintos admins de la tienda
        $shop_id     = $receipt->shop_id;
        $receipt_id  = $receipt->id;
        $client_name = $receipt->client->name;
        
        // Generar notification_group_id único para esta compra (chatbot)
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

    public function getProducts(Request $request){

        $products = Product::select('id', 'name','retail', 'wholesale', 'wholesale_premium', 'stock', 'reserve', 'description', 'image')
                    ->with('category')
                    ->where('shop_id',1)
                    ->where('active',1)
                    ->orderBy('id','desc')
                    ->get();
        return $products;
    }

    public function getClients(Request $request){

        $products = Client::where('shop_id',1)
                    ->where('active',1)
                    ->orderBy('id','desc')
                    ->get();
        return $products;
    }

    public function clientStore(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $client = new Client;
        $client->shop_id =$shop->id;
        $client->active  =1;
        $client->name    =$request->name;
        $client->company =$request->company;
        $client->email   =$request->email;
        $client->movil   =$request->movil;
        $client->address =$request->address;
        $client->level   =1;
        $client->origin_chatbot =1;
        $client->save();

        return response()->json([
                'ok'=>true,
                'client' => $client,
        ]);
    }

    public function receiptStore(Request $request){
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
        $receipt->origin      = 'CHATBOT';
        $receipt->save();

        //Guardaremos el detalle de la nota
        //$details = json_decode($request->detail);

        $details = $request->detail;

        if (!is_array($details)) {
            return response()->json([
                'ok' => false,
                'message' => 'El campo "detail" debe ser un array válido.',
            ], 400);
        }

        foreach ($details as $data) {
            if (!is_object((object)$data) || !isset($data['id'])) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Cada detalle debe ser un objeto con las propiedades necesarias.',
                ], 400);
            }

            $detail = new ReceiptDetail();
            $detail->receipt_id  = $receipt->id;
            $detail->product_id  = $data['id'];
            $detail->type        = $data['type'] ?? 'desconocido';
            $detail->descripcion = $data['name'] ?? '';
            $detail->qty         = $data['qty'] ?? 0;
            $detail->price       = $data['cost'] ?? 0;
            $detail->subtotal    = $data['subtotal'] ?? 0;
            $detail->image       = $data['image'] ?? null;
            $detail->save();
        }

        /*$details = is_string($request->detail) ? json_decode($request->detail) : $request->detail;

        return response()->json([
                'object'=>$request->detail
            ],400);

        foreach($details as $data){
            if (!is_object($data) || !isset($data->id)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Cada detalle debe ser un objeto con las propiedades necesarias.',
                ], 400);
            }

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
        */

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
    }//receiptStore()
}
