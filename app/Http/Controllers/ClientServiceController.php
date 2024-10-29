<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientService;
use App\Models\ClientServiceLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class ClientServiceController extends Controller
{
    private function storeLog($task_id, $user, $description){
        $log = new ClientServiceLog();
        $log->task_id    = $task_id;
        $log->user       = $user;
        $log->description= $description;
        $log->save();
    }

    public function index(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        $ordenar = $request->filtro_ordenar;

        $query = ClientService::with('logs')
                        ->where('shop_id', $shop->id);

        if ($buscar != '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('title', 'like', '%' . $buscar . '%')
                  ->orWhere('description', 'like', '%' . $buscar . '%');
            });
        }

        switch ($ordenar) {
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
        }

        $client_services = $query->paginate(10);

        return $client_services;
    }//index()

    public function store(Request $request){

        $user = $request->user();
        $shop = $user->shop;

        $client_services = new ClientService();
        $client_services->shop_id   = $shop->id;
        $client_services->client_id = $request->client_id;
        $client_services->active    = 1;
        $client_services->status    = 'NUEVO';
        $client_services->priority  = 0;
        $client_services->title     = $request->title;
        $client_services->description = $request->description;
        $client_services->save();

        //Una ves creado el client_services, insertamos un log-history
        $this->storeLog($client_services->id,$user->name,'Creación del registro.');


        $client_services->load('client');
        $client_services->load('images');
        $client_services->load('logs');

        return response()->json([
            'ok'=>true,
            'client_services' => $client_services,
        ]);
    }//.store

    public function update(Request $request){
        $user = $request->user();
        $client_services = ClientService::findOrFail($request->id);
        $client_services->priority = $request->priority;
        $client_services->title    = $request->title;
        $client_services->description = $request->description;
        $client_services->solution = $request->solution;
        $client_services->save();

        //Una ves guardado el client_services, insertamos un log-history
        $this->storeLog($client_services->id,$user->name,'Edición del registro.');

        $client_services->load('client');
        $client_services->load('images');
        $client_services->load('logs');

        return response()->json([
            'ok' => true,
            'client_services' => $client_services
        ]);
    }//.update

    public function active(Request $request){
        $user = $request->user();
        $client_services = ClientService::findOrFail($request->id);
        $client_services->active = 1;
        $client_services->save();


        //Una ves guardado el client_services, insertamos un log-history
        $this->storeLog($client_services->id,$user->name,'Activado.');

        $client_services->load('client');
        $client_services->load('images');
        $client_services->load('logs');

        return response()->json([
            'ok'=>true,
            'client_services' => $client_services,
        ]);
    }//.active
    public function inactive(Request $request){
        $user = $request->user();
        $client_services = ClientService::findOrFail($request->id);
        $client_services->active = 0;
        $client_services->save();
        //Una ves guardado el client_services, insertamos un log-history
        $this->storeLog($client_services->id,$user->name,'Desactivado.');

        $client_services->load('client');
        $client_services->load('images');
        $client_services->load('logs');
        return response()->json([
            'ok'=>true,
            'client_services' => $client_services,
        ]);
    }//.inactive

    public function destroy(Request $request){
        $client_services = ClientService::findOrFail($request->id);
        // Verificar si la tarea tiene una imagen asociada
        if ($client_services->image) {
            // Obtener el nombre de archivo de la imagen
            $filename = basename($client_services->image);

            // Eliminar la imagen del almacenamiento
            Storage::delete('public/' . $client_services->image);
        }

        // Eliminar las imágenes asociadas al modelo TaskImage del almacenamiento
        $taskImages = TaskImage::where('task_id', $client_services->id)->get();
        foreach ($taskImages as $taskImage) {
            Storage::delete('public/' . $taskImage->image);
        }

        // Eliminar las imágenes asociadas al modelo TaskImage de la base de datos
        TaskImage::where('task_id', $client_services->id)->delete();

        // Eliminar el history asociadas al modelo TaskLog de la base de datos
        TaskLog::where('task_id', $client_services->id)->delete();

        // Eliminar la tarea
        $client_services->delete();

        return response()->json([
            'ok'=>true
        ]);
    }//.destroy



    public function updateEstatus(Request $request){
        $user = $request->user();

        $client_services = ClientService::findOrFail($request->input('client_services')['id']);
        $new_status = $request->input('estatus');
        $client_services->status = $new_status;
        $client_services->save();
        //Una ves guardado el client_services, insertamos un log-history
        $log_desc = 'Actualización de estatus: '.$new_status;
        $this->storeLog($client_services->id,$user->name,$log_desc);

        // Disparar el evento
         //event(new TaskUpdated($client_services, $user));
        if($new_status == 'ATENDIDO'){ $this->storeNotificationsTaskForShop($client_services); }



        $client_services->load('client');
        $client_services->load('images');
        $client_services->load('logs');
        return response()->json([
            'ok' => true,
            'client_services' => $client_services
        ]);
    }//.updateEstatus

    public function updateResena(Request $request){
        $user = $request->user();
        $client_services = ClientService::findOrFail($request->input('client_services')['id']);
        $new_resena = $request->input('resena');
        $client_services->review = $new_resena;
        $client_services->save();
        //Una ves guardado el client_services, insertamos un log-history
        $log_desc = 'Actualización reseña.';
        $this->storeLog($client_services->id,$user->name,$log_desc);

        $client_services->load('client');
        $client_services->load('images');
        $client_services->load('logs');

        return response()->json([
            'ok' => true,
            'client_services' => $client_services
        ]);
    }//.updateEstatus

}
