<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientService;
use App\Models\ClientServiceLog;
use App\Models\ClientServiceImage;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use App\Events\ClientServiceNotification;
use App\Services\FirebaseService;

class ClientServiceController extends Controller
{
    public function storeNotificationsForShop(ClientService $client_service){
        //crearemos notificaiones para los distintos admins de la tienda
        $shop_id = $client_service->shop_id;
        $client_service_id = $client_service->id;
        $client_service_title = $client_service->title;
        $client_name = $client_service->client->name;
        //Obtenemos los usuarios tipo admin o superadmin dela tienda
        $shop_users_admin = User::where('shop_id', $shop_id)
                                ->whereHas('roles', function($query) {
                                    $query->whereIn('role_user.role_id', [1, 2]);
                                })
                                ->where('active', 1)
                                ->get();

        $ntf_description = 'Solicitud de Servicio: '.$client_name.': '.$client_service_title;
        foreach($shop_users_admin as $user){
            $new_ntf = new Notification();
            $new_ntf->user_id     = $user->id;
            $new_ntf->description = $ntf_description; 
            $new_ntf->type        = 'client_service';
            $new_ntf->action      = 'client_service_id';
            $new_ntf->data        = $client_service_id;
            $new_ntf->read        = 0;
            $new_ntf->save();
            
            // 🔥 NUEVO: Disparar evento en tiempo real (Pusher)
            event(new ClientServiceNotification($new_ntf, $shop_id));
        }

        // 🔥 NUEVO: Push notification para app cerrada (FCM) - FUERA del loop
        $firebaseService = app(FirebaseService::class);
        $firebaseService->sendToShopAdmins(
            $shop_id,
            'Nueva Solicitud de Servicio',
            "Solicitud de Servicio: {$client_name}",
            [
                'type' => 'client_service',
                'client_service_id' => (string)$client_service_id,
                'shop_id' => (string)$shop_id
            ]
        );
    }//storeNotificationsForShop()

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
                        ->with('images')
                        ->where('shop_id', $shop->id)
                        ->where('client_id', $client->id);

        if ($buscar != '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('title', 'like', '%' . $buscar . '%')
                  ->orWhere('description', 'like', '%' . $buscar . '%');
            });
        }

        $query->orderBy('id', 'desc');

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

        $client_services = $query->paginate(15);
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

        if ($request->hasFile('image')) {
            $client_service->image = $request->file('image')->store('clients_services', 'public');
        }

        $client_service->save();

        //Una ves creado el client_service, insertamos un log-history
        $this->storeLog($client_service->id,$user->name,'Creación del registro.');


        $client_service->load('logs');
        $client_service->load('images');
        $client_service->load('client');

        $this->storeNotificationsForShop($client_service);

        return response()->json([
            'ok'=>true,
            'client_service' => $client_service,
        ]);
    }//.store

    public function deleteMainImage(Request $request){
        $user = $request->user();
        $client_service_id = $request->id;
        $client_service = ClientService::findOrFail($client_service_id);
        // Obtener la ruta de la imagen actual
        $imagePath = $client_service->image;
        // Verificar si hay una imagen almacenada y eliminarla
        if ($imagePath) {
            // Eliminar la imagen del almacenamiento
            Storage::disk('public')->delete($imagePath);
            // Limpiar el atributo de la imagen en el modelo
            $client_service->image = null;
            $client_service->save();

            $log_desc = 'Eliminación de imágen principal.';
            $this->storeLog($client_service->id,$user->name,$log_desc);
        }

        $client_service->load('client');
        $client_service->load('images');
        $client_service->load('logs');
        return response()->json([
            'ok' => true,
            'client_service' => $client_service,
        ]);
    }//.deleteMainImage()

    public function uploadImage(Request $request){
        $user = $request->user();

        $client_service_id = $request->client_service_id;
        $client_service = ClientService::findOrFail($client_service_id);

        // Validar la existencia del archivo de imagen
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // Guardar la imagen en la ubicación 'public'
            $imagePath = $image->store('clients_services', 'public');

            // Si ya existe una imagen principal, guardar en la relación ClientServiceImage
            if ($client_service->image) {
                $client_serviceImage = new ClientServiceImage();
                $client_serviceImage->client_service_id = $client_service_id;
                $client_serviceImage->image = $imagePath;
                $client_serviceImage->save();
                //Una ves guardado el client_service, insertamos un log-history
                $log_desc = 'Subida de imágen.';
                $this->storeLog($client_service->id,$user->name,$log_desc);
            } else {
                // Si no existe una imagen principal, guardarla en el registro del client_service
                $client_service->image = $imagePath;
                $client_service->save();
                //Una ves guardado el client_service, insertamos un log-history
                $log_desc = 'Subida de imágen.';
                $this->storeLog($client_service->id,$user->name,$log_desc);
            }
        }

        $client_service->load('client');
        $client_service->load('images');
        $client_service->load('logs');
        return response()->json([
            'ok'=>true,
            'client_service' => $client_service,
        ]);
    }//uploadImage()


    public function update(Request $request){
        $user = $request->user();
        $client_service = ClientService::findOrFail($request->id);
        $client_service->title    = $request->title;
        $client_service->description = $request->description;
        $client_service->save();

        //Una ves guardado, insertamos un log-history
        $this->storeLog($client_service->id,$user->name,'Edición del registro.');

        $client_service->load('client');
        $client_service->load('images');
        $client_service->load('logs');

        return response()->json([
            'ok' => true,
            'client_service' => $client_service
        ]);
    }//.update



}
