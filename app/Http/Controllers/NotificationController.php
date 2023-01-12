<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Notification;
use App\Models\NotificationUserToday;
use App\Models\Rent;
use App\Models\Client;

class NotificationController extends Controller
{
    public function get(Request $request){

        $user_id=$request->user_id;

        //buscamos si se crearon las notificaciones de hoy para el usuario
        //nota: habria que mostrar las notificaciones pasadas si no entra un día
        //y tomar en cuenta cuales se mostraria y cuales ya no
        $notificationUserToday = NotificationUserToday::where('user_id',$user_id)
                                    ->whereDate('created_at', Carbon::today())
                                    ->get();

        //si no se han creado las de hoy, debemos crearlas
        if($notificationUserToday->isEmpty()){
            $new_ntf_usr_today = new NotificationUserToday();
            $new_ntf_usr_today->user_id = $user_id;
            $new_ntf_usr_today->save();
            //Creamos las notificaciones de renta de este día
            $this->storeNotificationsRentsByUser($user_id);
        }
        //Traemos todas la notoficaciones sin leer del usuario
        $notifications = Notification::where('user_id',$user_id)->where('read',0)->orderBy('created_at','desc')->paginate(10);

        return response()->json([
            'ok'=>true,
            'data' => $notifications,
        ]);
    }//.get()

    public function storeNotificationsRentsByUser($user_id){
        $date_today     = Carbon::now();
        //$date_tomorrow  = new Carbon('tomorrow');
        $dd_today       = $date_today->day;
        //$dd_tomorrow    = $date_tomorrow->day;

        $rents_today    = Rent::with('client')
                        ->where('active',1)
                        ->where('cutoff',$dd_today)
                        ->get();
        //dd($rents_today);
        foreach($rents_today as $data){
            $new_ntf = new Notification();
            $new_ntf->user_id     = $user_id;
            $new_ntf->description = $data->client->name.' '.$data->client->company;
            $new_ntf->type        = 'renta';
            $new_ntf->action      = 'client_id';
            $new_ntf->data        = $data->client->id;
            $new_ntf->read        = 0;
            $new_ntf->save();
        }

    }//storeNotificationsRentsByUser()


    public function read(Request $request){

        $notification= Notification::findOrFail($request->id);
        $notification->read = 1;
        $notification->save();
        return response()->json([
            'ok'=>true
        ]);
    }//.read()


    public function getClientxID(Request $request){
        $client = Client::with('rents')->findOrFail($request->client_id);
        $client->save();
        return $client;
        /*return response()->json([
            'ok'=>true,
            'client'=>$client
        ]);*/
    }//getClientxID()
}
