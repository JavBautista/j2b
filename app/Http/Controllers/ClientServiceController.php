<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientService;
use App\Models\ClientServiceLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class ClientServiceController extends Controller
{
    private function storeLog($client_service_id, $user, $description){
        $log = new ClientServiceLog();
        $log->client_service_id    = $client_service_id;
        $log->user       = $user;
        $log->description= $description;
        $log->save();
    }

    public function index(Request $request){
        $user = $request->user();
        $shop = $user->shop;
        $client = $user->client;

        $buscar = $request->buscar;
        $ordenar = $request->filtro_ordenar;

        $query = ClientService::with('client')
                        ->with('logs')
                        ->where('shop_id', $shop->id)
                        ->where('client_id', $client->id);

       /* if ($buscar != '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('title', 'like', '%' . $buscar . '%')
                  ->orWhere('description', 'like', '%' . $buscar . '%');
            });
        }*/

        /*switch ($ordenar) {
            case 'ID_ASC':
                $query->orderBy('id', 'asc');
                break;
            case 'ID_DESC':
                $query->orderBy('id', 'desc');
                break;
            case 'PRD_ASC':
                $query->orderBy('priority', 'asc');
                break;
            case 'PRD_DESC':
                $query->orderBy('priority', 'desc');
                break;
            default:
                // Ordenar por defecto por ID DESC si no se especifica ningún filtro
                $query->orderBy('id', 'desc');
        }*/

        $client_services = $query->paginate(10);

        return $client_services;
    }//index()

    public function store(Request $request){

        $user   = $request->user();
        $shop   = $user->shop;
        $client = $user->client;

        $client_service = new ClientService();
        $client_service->shop_id   = $shop->id;
        $client_service->client_id = $client->id;
        $client_service->active    = 1;
        $client_service->status    = 'NUEVO';
        $client_service->priority  = 0;
        $client_service->title     = $request->title;
        $client_service->description = $request->description;
        $client_service->save();

        //Una ves creado el client_service, insertamos un log-history
        $this->storeLog($client_service->id,$user->name,'Creación del registro.');


        $client_service->load('logs');

        return response()->json([
            'ok'=>true,
            'client_service' => $client_service,
        ]);
    }//.store

    public function update(Request $request){
        $user = $request->user();
        $client_service = ClientService::findOrFail($request->id);
        $client_service->priority = $request->priority;
        $client_service->title    = $request->title;
        $client_service->description = $request->description;
        $client_service->solution = $request->solution;
        $client_service->save();

        //Una ves guardado el client_service, insertamos un log-history
        $this->storeLog($client_service->id,$user->name,'Edición del registro.');

        $client_service->load('logs');

        return response()->json([
            'ok' => true,
            'client_service' => $client_service
        ]);
    }//.update

    public function active(Request $request){
        $user = $request->user();
        $client_service = ClientService::findOrFail($request->id);
        $client_service->active = 1;
        $client_service->save();


        //Una ves guardado el client_service, insertamos un log-history
        $this->storeLog($client_service->id,$user->name,'Activado.');

        $client_service->load('logs');

        return response()->json([
            'ok'=>true,
            'client_service' => $client_service,
        ]);
    }//.active
    public function inactive(Request $request){
        $user = $request->user();
        $client_service = ClientService::findOrFail($request->id);
        $client_service->active = 0;
        $client_service->save();
        //Una ves guardado el client_service, insertamos un log-history
        $this->storeLog($client_service->id,$user->name,'Desactivado.');

        $client_service->load('logs');
        return response()->json([
            'ok'=>true,
            'client_service' => $client_service,
        ]);
    }//.inactive

    public function destroy(Request $request){
        /*$client_service = ClientService::findOrFail($request->id);
        // Verificar si la tarea tiene una imagen asociada
        if ($client_service->image) {
            // Obtener el nombre de archivo de la imagen
            $filename = basename($client_service->image);

            // Eliminar la imagen del almacenamiento
            Storage::delete('public/' . $client_service->image);
        }

        // Eliminar las imágenes asociadas al modelo TaskImage del almacenamiento
        $taskImages = TaskImage::where('task_id', $client_service->id)->get();
        foreach ($taskImages as $taskImage) {
            Storage::delete('public/' . $taskImage->image);
        }

        // Eliminar las imágenes asociadas al modelo TaskImage de la base de datos
        TaskImage::where('task_id', $client_service->id)->delete();

        // Eliminar el history asociadas al modelo TaskLog de la base de datos
        TaskLog::where('task_id', $client_service->id)->delete();

        // Eliminar la tarea
        $client_service->delete();
        */
        return response()->json([
            'ok'=>true
        ]);
    }//.destroy



    public function updateEstatus(Request $request){
        $user = $request->user();

        $client_service = ClientService::findOrFail($request->input('client_service')['id']);
        $new_status = $request->input('estatus');
        $client_service->status = $new_status;
        $client_service->save();
        //Una ves guardado el client_service, insertamos un log-history
        $log_desc = 'Actualización de estatus: '.$new_status;
        $this->storeLog($client_service->id,$user->name,$log_desc);

        // Disparar el evento
         //event(new TaskUpdated($client_service, $user));
        if($new_status == 'ATENDIDO'){ $this->storeNotificationsTaskForShop($client_service); }



        $client_service->load('client');
        $client_service->load('images');
        $client_service->load('logs');
        return response()->json([
            'ok' => true,
            'client_service' => $client_service
        ]);
    }//.updateEstatus

    public function updateResena(Request $request){
        $user = $request->user();
        $client_service = ClientService::findOrFail($request->input('client_service')['id']);
        $new_resena = $request->input('resena');
        $client_service->review = $new_resena;
        $client_service->save();
        //Una ves guardado el client_service, insertamos un log-history
        $log_desc = 'Actualización reseña.';
        $this->storeLog($client_service->id,$user->name,$log_desc);

        $client_service->load('client');
        $client_service->load('images');
        $client_service->load('logs');

        return response()->json([
            'ok' => true,
            'client_service' => $client_service
        ]);
    }//.updateEstatus

}
