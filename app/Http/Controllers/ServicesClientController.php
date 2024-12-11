<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientService;

class ServicesClientController extends Controller
{
    public function index(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        $ordenar = $request->filtro_ordenar;

        $query = ClientService::with('client')
                        ->with('logs')
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

        $services_clients = $query->paginate(10);

        return $services_clients;
    }//index()

    public function getNumPorEstatus(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $numNuevo = ClientService::where('shop_id', $shop->id)->where('status', 'NUEVO')->count();
        $numPendiente = ClientService::where('shop_id', $shop->id)->where('status', 'PENDIENTE')->count();
        $numAtendido = ClientService::where('shop_id', $shop->id)->where('status', 'ATENDIDO')->count();

        return response()->json([
            'numNuevo' => $numNuevo,
            'numPendiente' => $numPendiente,
            'numAtendido' => $numAtendido
        ]);
    }//getNumPorEstatus()

    private function storeLog($task_id, $user, $description){
        $log = new TaskLog();
        $log->task_id    = $task_id;
        $log->user       = $user;
        $log->description= $description;
        $log->save();
    }
    public function updateEstatus(Request $request){
        $user = $request->user();

        $client_service = ClientService::findOrFail($request->input('client_service')['id']);
        $new_status = $request->input('estatus');
        $client_service->status = $new_status;
        $client_service->save();

        //Una ves guardado el client_service, insertamos un log-history
        $log_desc = 'Actualización de estatus: '.$new_status;
        $this->storeLog($client_service->id,$user->name,$log_desc);

        $client_service->load('client');
        //$client_service->load('images');
        $client_service->load('logs');
        return response()->json([
            'ok' => true,
            'client_service' => $client_service
        ]);
    }//.updateEstatus
}
