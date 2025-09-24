<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Notification;
use App\Models\NotificationUserToday;
use App\Models\Rent;
use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use App\Models\Receipt;
use App\Models\ClientService;

class NotificationController extends Controller
{
    /*//Metodo get Original v1: Este generaba las notificaciones al abrir la app
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
            //$this->storeNotificationsCredits($user_id);
        }

        //Traemos todas la notificaciones sin leer del usuario
        $all_notifications = Notification::where('user_id',$user_id)
                            ->where('read',0)
                            ->get();

        //Adicionalmente verificamos que las notificaciones no leidas aun sean de clientes activos
        //Puede haber notificaciones de dias pasados, y en ese lapso haberse dado de baja algun cliente
        foreach ($all_notifications as $ntf) {
            if($ntf->action == 'client_id'){
                $idcl = $ntf->data;
                $cl = Client::find($idcl);
                if($cl->active==0){
                    $act_ntf = Notification::find($ntf->id);
                    $act_ntf->read=1;
                    $act_ntf->save();
                }
            }
        }

        //Traemos las notoficaciones paginada sin leer del usuario
        $notifications = Notification::where('user_id',$user_id)
                            ->where('read',0)
                            ->orderBy('created_at','desc')
                            ->paginate(10);

        return $notifications;
    }//.get()
    */

    //Nuevo metodo get, solo obtenemos las notificaciones  ya que ya las genero el cron tab
    public function get(Request $request){

        $user_id=$request->user_id;

        //Traemos todas la notificaciones sin leer del usuario
        $all_notifications = Notification::where('user_id',$user_id)->get();

        //Adicionalmente verificamos que las notificaciones no leidas aun sean de clientes activos
        //Puede haber notificaciones de dias pasados, y en ese lapso haberse dado de baja algun cliente
        foreach ($all_notifications as $ntf) {
            if($ntf->action == 'client_id'){
                $idcl = $ntf->data;
                $cl = Client::find($idcl);
                if($cl->active==0){
                    $act_ntf = Notification::find($ntf->id);
                    $act_ntf->read=1;
                    $act_ntf->save();
                }
            }
        }

        //Traemos las notificaciones paginadas visibles del usuario
        $notifications = Notification::where('user_id',$user_id)
                            ->where('visible', 1)
                            ->orderBy('created_at','desc')
                            ->paginate(10);

        return $notifications;
    }//.get()

    public function storeNotificationsCredits($user_id){
    }//storeNotificationsCredits()

    public function storeNotificationsRentsByUser($user_id){
        /* ENERO 2025: Este metodo ya no deberia usarse, lo dejaremos hasta confirmar que funciona bien el nuevo cron que las gnerar ahora*/
        /*VERIFICAR SI ESTO FUNCIONA BIEN YA QUE
        SOLO DEBE GENERAR PARA LAS RENTAS DE LOS CLIENTES DE LA TIENDAS
        */
        $user = User::findOrFail($user_id);
        $shop = $user->shop;
        $shop_id = $shop->id;

        $date_today     = Carbon::now();
        //$date_tomorrow  = new Carbon('tomorrow');
        $dd_today       = $date_today->day;
        //$dd_tomorrow    = $date_tomorrow->day;

        $rents_today    = Rent::with('client')
                        ->whereHas('client', function($query) use ($shop_id) {
                            $query->where('shop_id', $shop_id);
                        })
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
        return $client;
    }//getClientxID()

    public function getTaskxID(Request $request){
        $task = Task::with('client')
                        ->with('images')
                        ->with('logs')
                        ->findOrFail($request->task_id);
        return $task;
    }//getTaskxID()

    public function getClientServicexID(Request $request){
        $client_service = ClientService::with('client')
                        ->with('logs')
                        ->findOrFail($request->client_service_id);
        return $client_service;
    }//getClientServicexID()

    public function getReceiptxID(Request $request){
        $receipt = Receipt::with('partialPayments')
                        ->with('shop')
                        ->with('detail')
                        ->with('client')
                        ->findOrFail($request->receipt_id);
        return $receipt;
    }//getReceiptxID()

    public function test(Request $request){

        /*
        VERIFICAR SI ESTO FUNCIONA BIEN YA QUE
        SOLO DEBE GENERAR PARA LAS RENTAS DE LOS CLIENTES DE LA TIENDAS
        */
        $user = User::findOrFail($request->user_id);
        $shop = $user->shop;
        dd($shop);
    }//test

    /**
     * Marcar como leídas todas las notificaciones de un grupo
     * Solo usuarios limitados pueden usar esta función
     */
    public function markAllAsRead(Request $request)
    {
        $request->validate([
            'notification_group_id' => 'required|string'
        ]);

        $user = $request->user();
        $notification_group_id = $request->notification_group_id;

        // Verificar que el usuario tenga acceso a este grupo de notificaciones
        $userNotification = Notification::where('notification_group_id', $notification_group_id)
            ->where('user_id', $user->id)
            ->visible()
            ->first();

        if (!$userNotification) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes acceso a esta notificación'
            ], 403);
        }

        // Debug: Log información del request
        \Log::info("NotificationController::markAllAsRead - Usuario: {$user->id}, Grupo: $notification_group_id");

        // Marcar como leídas todas las notificaciones del grupo
        $updated = Notification::markGroupAsRead($notification_group_id);

        \Log::info("NotificationController::markAllAsRead - Resultado: $updated registros actualizados");

        return response()->json([
            'ok' => true,
            'message' => 'Notificaciones marcadas como leídas',
            'updated_count' => $updated
        ]);
    }

    /**
     * Eliminar (ocultar) todas las notificaciones de un grupo
     * Solo usuarios FULL (superadmin) pueden usar esta función
     */
    public function deleteForAll(Request $request)
    {
        $request->validate([
            'notification_group_id' => 'required|string'
        ]);

        $user = $request->user();
        $notification_group_id = $request->notification_group_id;

        // Debug: Log información de roles del usuario
        \Log::info("NotificationController::deleteForAll - Verificando permisos para usuario: {$user->id}");

        $userRoles = $user->roles()->get();
        \Log::info("NotificationController::deleteForAll - Roles del usuario: " . $userRoles->pluck('role_id')->implode(', '));

        // Verificar permisos - permitir usuarios que NO sean limitados
        // Lógica coincidente con el frontend: !usuarioLimitado

        // Verificar si es usuario limitado (role_id = 3 o similar)
        $isLimitedUser = $user->roles()->where('role_id', 3)->exists();

        // También verificar si tiene rol de superadmin (role_id = 1)
        $isSuperAdmin = $user->roles()->where('role_id', 1)->exists();

        // También verificar si tiene rol de admin (role_id = 2)
        $isAdmin = $user->roles()->where('role_id', 2)->exists();

        \Log::info("NotificationController::deleteForAll - Es limitado: " . ($isLimitedUser ? 'SÍ' : 'NO'));
        \Log::info("NotificationController::deleteForAll - Es superadmin: " . ($isSuperAdmin ? 'SÍ' : 'NO'));
        \Log::info("NotificationController::deleteForAll - Es admin: " . ($isAdmin ? 'SÍ' : 'NO'));

        // Permitir eliminar si NO es usuario limitado (si es admin o superadmin)
        $canDelete = !$isLimitedUser && ($isSuperAdmin || $isAdmin);

        if (!$canDelete) {
            \Log::warning("NotificationController::deleteForAll - Acceso denegado para usuario {$user->id}");
            return response()->json([
                'ok' => false,
                'message' => 'Solo usuarios con permisos completos pueden eliminar notificaciones'
            ], 403);
        }

        // Verificar que el usuario tenga acceso a este grupo de notificaciones
        $userNotification = Notification::where('notification_group_id', $notification_group_id)
            ->where('user_id', $user->id)
            ->visible()
            ->first();

        if (!$userNotification) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes acceso a esta notificación'
            ], 403);
        }

        // Ocultar todas las notificaciones del grupo (soft delete)
        $updated = Notification::hideGroup($notification_group_id);

        return response()->json([
            'ok' => true,
            'message' => 'Notificaciones eliminadas para todos los usuarios',
            'updated_count' => $updated
        ]);
    }

    /**
     * Obtener estadísticas de un grupo de notificaciones
     */
    public function getGroupStats(Request $request)
    {
        $request->validate([
            'notification_group_id' => 'required|string'
        ]);

        $notification_group_id = $request->notification_group_id;
        $stats = Notification::getGroupStats($notification_group_id);

        return response()->json([
            'ok' => true,
            'stats' => $stats
        ]);
    }
}
