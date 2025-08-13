<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientService;
use App\Models\ClientServiceImage;
use App\Models\ClientServiceLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ServicesClientController extends Controller
{
    public function index(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        $ordenar = $request->filtro_ordenar;
        $status = $request->filtro_status;

        $query = ClientService::with('client')
                        ->with('logs')
                        ->with('images')
                        ->where('shop_id', $shop->id);

        if ($buscar != '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('title', 'like', '%' . $buscar . '%')
                  ->orWhere('description', 'like', '%' . $buscar . '%');
            });
        }

        if (!empty($status) && $status !== 'TODOS') {
            $query->where('status', $status);
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

        $client_service->load('client');
        $client_service->load('images');
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

        $client_service->load('client');
        $client_service->load('images');
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

        $client_service->load('client');
        $client_service->load('images');
        $client_service->load('logs');
        return response()->json([
            'ok'=>true,
            'client_service' => $client_service,
        ]);
    }//.inactive

    public function destroy(Request $request){
        $client_service = ClientService::findOrFail($request->id);
        // Verificar si la servicio tiene una imagen asociada
        if ($client_service->image) {
            // Obtener el nombre de archivo de la imagen
            $filename = basename($client_service->image);

            // Eliminar la imagen del almacenamiento
            Storage::delete('public/' . $client_service->image);
        }

        // Eliminar las imágenes asociadas al modelo client_serviceImage del almacenamiento
        $clientServiceImages = ClientServiceImage::where('client_service_id', $client_service->id)->get();
        foreach ($clientServiceImages as $client_serviceImage) {
            Storage::delete('public/' . $client_serviceImage->image);
        }

        // Eliminar las imágenes asociadas al modelo ClientServiceImage de la base de datos
        ClientServiceImage::where('client_service_id', $client_service->id)->delete();

        // Eliminar el history asociadas al modelo ClientServiceLog de la base de datos
        ClientServiceLog::where('client_service_id', $client_service->id)->delete();

        // Eliminar la servicio
        $client_service->delete();

        return response()->json([
            'ok'=>true
        ]);
    }//.destroy


    private function storeLog($client_service_id, $user, $description){
        $log = new ClientServiceLog();
        $log->client_service_id    = $client_service_id;
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

    public function uploadImageClientService(Request $request){
        $user = $request->user();

        $client_serviceId = $request->client_service_id;

        $client_service = ClientService::findOrFail($client_serviceId);
        // Validar la existencia del archivo de imagen
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // Guardar la imagen en la ubicación 'public'
            $imagePath = $image->store('clients_services', 'public');

            // Si ya existe una imagen principal, guardar en la relación client_serviceImage
            if ($client_service->image) {
                $client_serviceImage = new ClientServiceImage();
                $client_serviceImage->client_service_id = $client_serviceId;
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
    }



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

    public function deleteAltImage(Request $request){
        $user = $request->user();
        $client_serviceId = $request->input('client_service.id'); // Obtener el 'id' del client_service del request
        $imgAltId = $request->input('img_alt_id'); // Obtener el 'img_alt_id' del request


        try {
            // Buscar la imagen alternativa por su ID
            $client_serviceImage = ClientServiceImage::findOrFail($imgAltId);

            // Verificar si la imagen alternativa pertenece al client_service indicado
            if ($client_serviceImage->client_service_id != $client_serviceId) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La imagen alternativa no pertenece al client_service indicado.',
                ], 400);
            }

            // Eliminar la imagen alternativa del almacenamiento
            Storage::disk('public')->delete($client_serviceImage->image);

            // Eliminar el registro de la imagen alternativa de la base de datos
            $client_serviceImage->delete();

            // Registrar la acción en el log del client_service
            $logDesc = 'Eliminación de imagen secundaria.';
            $this->storeLog($client_serviceId, $user->name, $logDesc);

            // Cargar el client_service con las relaciones actualizadas
            $client_service = ClientService::with('client', 'images', 'logs')->findOrFail($client_serviceId);

            return response()->json([
                'ok' => true,
                'client_service' => $client_service,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al eliminar la imagen alternativa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//.deleteAltImage()

    /**
     * Firmar un servicio de cliente con firma digital
     */
    public function signService(Request $request){
        try {
            $user = $request->user();
            $client_service = ClientService::findOrFail($request->client_service_id);
            
            // Validar que el servicio pertenece a la misma tienda del usuario
            if($client_service->shop_id != $user->shop->id){
                return response()->json([
                    'ok' => false,
                    'message' => 'No tiene permisos para firmar este servicio.'
                ], 403);
            }

            // Validar que se envió la firma
            if(empty($request->signature)){
                return response()->json([
                    'ok' => false,
                    'message' => 'La firma es requerida.'
                ], 400);
            }

            // Decodificar la imagen base64
            $imageData = $request->signature;
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageData = base64_decode($imageData);

            if ($imageData === false) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Error al decodificar la imagen base64.'
                ], 400);
            }

            // Generar nombre único para la imagen
            $timestamp = now()->format('YmdHis');
            $imageName = 'service_signature_' . $client_service->id . '_' . $timestamp . '.png';
            $imagePath = 'signatures/' . $imageName;

            // Crear directorio si no existe
            $fullPath = storage_path('app/public/signatures');
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Guardar la imagen en storage/app/public
            $fullFilePath = "{$fullPath}/{$imageName}";
            if (file_put_contents($fullFilePath, $imageData) === false) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Error al guardar el archivo de imagen.'
                ], 500);
            }

            // Actualizar el servicio con la ruta de la firma
            $client_service->signature_path = $imagePath;
            $client_service->save();

            // Crear log de la acción
            $log = new ClientServiceLog();
            $log->client_service_id = $client_service->id;
            $log->user = $user->name;
            $log->description = 'Servicio firmado digitalmente por ' . $user->name;
            $log->save();

            return response()->json([
                'ok' => true,
                'message' => 'Servicio firmado exitosamente.',
                'client_service' => $client_service->load(['client', 'logs', 'images']),
                'signature_url' => asset('storage/' . $imagePath)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al firmar el servicio.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//signService()

    /**
     * Eliminar la firma digital de un servicio
     */
    public function deleteSignature(Request $request){
        try {
            $user = $request->user();
            $client_service = ClientService::findOrFail($request->client_service_id);
            
            // Validar que el servicio pertenece a la misma tienda del usuario
            if($client_service->shop_id != $user->shop->id){
                return response()->json([
                    'ok' => false,
                    'message' => 'No tiene permisos para modificar este servicio.'
                ], 403);
            }

            // Eliminar archivo de firma si existe
            if($client_service->signature_path && Storage::disk('public')->exists($client_service->signature_path)){
                Storage::disk('public')->delete($client_service->signature_path);
            }

            // Limpiar el campo signature_path
            $client_service->signature_path = null;
            $client_service->save();

            // Crear log de la acción
            $log = new ClientServiceLog();
            $log->client_service_id = $client_service->id;
            $log->user = $user->name;
            $log->description = 'Firma digital eliminada por ' . $user->name;
            $log->save();

            return response()->json([
                'ok' => true,
                'message' => 'Firma eliminada exitosamente.',
                'client_service' => $client_service->load(['client', 'logs', 'images'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al eliminar la firma.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//deleteSignature()
}
