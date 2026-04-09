<?php

namespace App\Http\Controllers;

use App\Events\TaskUpdated;
use App\Events\ClientServiceNotification;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskImage;
use App\Models\TaskLog;
use App\Models\TaskChecklistItem;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationFcmService;
use App\Services\FirebaseService;
use App\Services\ImageService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\PdfPhrase;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceTrackingStep;
use App\Models\TaskServiceTracking;
use App\Models\ServiceTrackingEvidence;
use App\Models\TaskInfoExtra;
use App\Models\ExtraFieldShop;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        $buscar_folio = $request->buscar_folio ? trim($request->buscar_folio) : '';
        $buscar_cliente = $request->buscar_cliente ? trim($request->buscar_cliente) : '';
        $ordenar = $request->filtro_ordenar;
        $status = $request->filtro_status;
        $extra_filters = $request->extra_filters ? json_decode($request->extra_filters, true) : [];

        $query = Task::with('client.addresses')
                        ->with('images')
                        ->with('logs')
                        ->with('assignedUser')
                        ->with('trackingHistory')
                        ->with('checklistItems')
                        ->with('currentServiceStep')
                        ->with('serviceTrackingHistory.step')
                        ->with('serviceTrackingHistory.changedBy')
                        ->with('infoExtra')
                        ->where('shop_id', $shop->id);

        if ($buscar != '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('title', 'like', '%' . $buscar . '%')
                  ->orWhere('description', 'like', '%' . $buscar . '%');
            });
        }

        if ($buscar_folio != '') {
            $query->where('folio', $buscar_folio);
        }

        if ($buscar_cliente != '') {
            $query->whereHas('client', function ($q) use ($buscar_cliente) {
                $q->where('name', 'like', '%' . $buscar_cliente . '%');
            });
        }

        if (!empty($status) && $status !== 'TODOS') {
            $query->where('status', $status);
        }

        if (!empty($extra_filters) && is_array($extra_filters)) {
            foreach ($extra_filters as $filter) {
                $fieldName = $filter['field_name'] ?? null;
                $value = $filter['value'] ?? null;
                if ($fieldName && $value) {
                    $query->whereHas('infoExtra', function ($q) use ($fieldName, $value) {
                        $q->where('field_name', $fieldName)
                          ->where('value', 'like', '%' . $value . '%');
                    });
                }
            }
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

        $tasks = $query->paginate(10);

        return $tasks;
    }//index()

    public function getNumPorEstatus(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $numNuevo = Task::where('shop_id', $shop->id)->where('status', 'NUEVO')->count();
        $numPendiente = Task::where('shop_id', $shop->id)->where('status', 'PENDIENTE')->count();
        $numAtendido = Task::where('shop_id', $shop->id)->where('status', 'ATENDIDO')->count();

        return response()->json([
            'numNuevo' => $numNuevo,
            'numPendiente' => $numPendiente,
            'numAtendido' => $numAtendido
        ]);
    }//getNumPorEstatus()

    public function store(Request $request){

        $user = $request->user();
        $shop = $user->shop;

        $ff = Carbon::parse($request->expiration);
        //$ff = $request->expiration;
        //$date_expiration = Carbon::createFromFormat('Y-m-d\TH:i:s.uP', $ff)->format('Y-m-d');
        $date_expiration = Carbon::createFromFormat('Y-m-d H:i:s', $ff )->format('Y-m-d');

        $now = now();

        // Generar folio consecutivo por tienda
        $ultimo_folio = Task::where('shop_id', $shop->id)->max('folio');
        $nuevo_folio = $ultimo_folio ? $ultimo_folio + 1 : 1;

        $task = new Task();
        $task->shop_id = $shop->id;
        $task->folio = $nuevo_folio;
        $task->client_id = $request->client_id;
        $task->active    = 1;
        $task->status    = 'NUEVO';
        $task->priority  = $request->priority;
        $task->title     = $request->title;
        $task->description = $request->description;
        $task->solution = $request->solution;
        $task->expiration = $date_expiration;

        if ($request->hasFile('image')) {
            // Procesar y optimizar la imagen
            $imageService = new ImageService();
            $task->image = $imageService->processAndStore($request->file('image'), 'tasks');
        }

        // Asignar paso inicial de service tracking si la tienda tiene pasos configurados
        $pasoInicial = ServiceTrackingStep::where('shop_id', $shop->id)
            ->where('is_initial', true)
            ->where('active', true)
            ->first();

        if ($pasoInicial) {
            $task->current_service_step_id = $pasoInicial->id;
            $task->tracking_code = $this->generateTrackingCode();
        }

        $task->save();

        // Registrar entrada en historial de tracking
        if ($pasoInicial) {
            TaskServiceTracking::create([
                'task_id' => $task->id,
                'step_id' => $pasoInicial->id,
                'changed_by_user_id' => $user->id,
                'notes' => 'Paso inicial asignado automáticamente.',
            ]);
        }

        // Guardar campos extra
        if ($request->info_extra) {
            $infoExtra = is_string($request->info_extra) ? json_decode($request->info_extra, true) : $request->info_extra;
            if (is_array($infoExtra)) {
                foreach ($infoExtra as $fieldName => $value) {
                    if ($value !== null && $value !== '') {
                        TaskInfoExtra::create([
                            'task_id' => $task->id,
                            'field_name' => $fieldName,
                            'value' => $value,
                        ]);
                    }
                }
            }
        }

        //Una ves creado el task, insertamos un log-history
        $this->storeTaskLog($task->id,$user->name,'Creación del registro.');


        $task->load('client.addresses');
        $task->load('images');
        $task->load('logs');
        $task->load('currentServiceStep');
        $task->load('infoExtra');

        return response()->json([
            'ok'=>true,
            'task' => $task,
        ]);
    }//.store

    public function update(Request $request){
        $user = $request->user();
        $task = Task::findOrFail($request->id);
        $task->priority = $request->priority;
        $task->title    = $request->title;
        $task->description = $request->description;
        $task->solution = $request->solution;
        $task->save();

        // Actualizar campos extra
        if ($request->has('info_extra')) {
            TaskInfoExtra::where('task_id', $task->id)->delete();
            $infoExtra = is_string($request->info_extra) ? json_decode($request->info_extra, true) : $request->info_extra;
            if (is_array($infoExtra)) {
                foreach ($infoExtra as $fieldName => $value) {
                    if ($value !== null && $value !== '') {
                        TaskInfoExtra::create([
                            'task_id' => $task->id,
                            'field_name' => $fieldName,
                            'value' => $value,
                        ]);
                    }
                }
            }
        }

        //Una ves guardado el task, insertamos un log-history
        $this->storeTaskLog($task->id,$user->name,'Edición del registro.');

        $task->load('client.addresses');
        $task->load('images');
        $task->load('logs');
        $task->load('infoExtra');

        return response()->json([
            'ok' => true,
            'task' => $task
        ]);
    }//.update

    public function active(Request $request){
        $user = $request->user();
        $task = Task::findOrFail($request->id);
        $task->active = 1;
        $task->save();


        //Una ves guardado el task, insertamos un log-history
        $this->storeTaskLog($task->id,$user->name,'Activado.');

        $task->load('client.addresses');
        $task->load('images');
        $task->load('logs');

        return response()->json([
            'ok'=>true,
            'task' => $task,
        ]);
    }//.active
    public function inactive(Request $request){
        $user = $request->user();
        $task = Task::findOrFail($request->id);
        $task->active = 0;
        $task->save();
        //Una ves guardado el task, insertamos un log-history
        $this->storeTaskLog($task->id,$user->name,'Desactivado.');

        $task->load('client.addresses');
        $task->load('images');
        $task->load('logs');
        return response()->json([
            'ok'=>true,
            'task' => $task,
        ]);
    }//.inactive

    public function destroy(Request $request){
        $task = Task::findOrFail($request->id);
        // Verificar si la tarea tiene una imagen asociada
        if ($task->image) {
            // Obtener el nombre de archivo de la imagen
            $filename = basename($task->image);

            // Eliminar la imagen del almacenamiento
            Storage::delete('public/' . $task->image);
        }

        // Eliminar las imágenes asociadas al modelo TaskImage del almacenamiento
        $taskImages = TaskImage::where('task_id', $task->id)->get();
        foreach ($taskImages as $taskImage) {
            Storage::delete('public/' . $taskImage->image);
        }

        // Eliminar las imágenes asociadas al modelo TaskImage de la base de datos
        TaskImage::where('task_id', $task->id)->delete();

        // Eliminar el history asociadas al modelo TaskLog de la base de datos
        TaskLog::where('task_id', $task->id)->delete();

        // Eliminar la tarea
        $task->delete();

        return response()->json([
            'ok'=>true
        ]);
    }//.destroy

    public function storeNotificationsTaskForShop(Task $task){
        //crearemos notificaiones para los distintos admins de la tienda
        $shop_id = $task->shop_id;
        $task_id = $task->id;

        // Generar notification_group_id único para esta tarea
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
            $new_ntf->description = 'Tarea #'.$task->id.' '.$task->title.' - '.$task->status;
            $new_ntf->type        = 'task';
            $new_ntf->action      = 'task_id';
            $new_ntf->data        = $task_id;
            $new_ntf->read        = 0;
            $new_ntf->visible     = 1;
            $new_ntf->save();
        }
    }//storeNotificationsTaskForShop()

    /**
     * Envía notificación FCM cuando una tarea se completa
     */
    private function sendTaskCompletedFcm(Task $task, User $user)
    {
        try {
            $fcmService = app(NotificationFcmService::class);

            $result = $fcmService->taskCompleted(
                $task->shop_id,
                $task->title,
                $user->name,
                $task->id
            );

            \Log::info("📱 FCM: Tarea completada enviada", [
                'task_id' => $task->id,
                'shop_id' => $task->shop_id,
                'user' => $user->name,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            \Log::error("❌ FCM: Error enviando tarea completada", [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateEstatus(Request $request){
        $user = $request->user();

        $task = Task::findOrFail($request->input('task')['id']);
        $new_status = $request->input('estatus');
        $task->status = $new_status;
        $task->save();
        //Una ves guardado el task, insertamos un log-history
        $log_desc = 'Actualización de estatus: '.$new_status;
        $this->storeTaskLog($task->id,$user->name,$log_desc);

        // Disparar el evento
         //event(new TaskUpdated($task, $user));
        if($new_status == 'ATENDIDO'){
            $this->storeNotificationsTaskForShop($task);
            // 🔔 FCM: Notificar cuando tarea se marca como atendida
            $this->sendTaskCompletedFcm($task, $user);
        }



        $task->load('client.addresses');
        $task->load('images');
        $task->load('logs');
        return response()->json([
            'ok' => true,
            'task' => $task
        ]);
    }//.updateEstatus

    public function updateResena(Request $request){
        $user = $request->user();
        $task = Task::findOrFail($request->input('task')['id']);
        $new_resena = $request->input('resena');
        $task->review = $new_resena;
        $task->save();
        //Una ves guardado el task, insertamos un log-history
        $log_desc = 'Actualización reseña.';
        $this->storeTaskLog($task->id,$user->name,$log_desc);

        $task->load('client.addresses');
        $task->load('images');
        $task->load('logs');

        return response()->json([
            'ok' => true,
            'task' => $task
        ]);
    }//.updateEstatus

    public function uploadImageTask(Request $request){
        $user = $request->user();

        $taskId = $request->task_id;
        $task = Task::findOrFail($taskId);

        // Validar la existencia del archivo de imagen
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // Procesar y optimizar la imagen (redimensiona y comprime)
            $imageService = new ImageService();
            $imagePath = $imageService->processAndStore($image, 'tasks');

            // Si ya existe una imagen principal, guardar en la relación TaskImage
            if ($task->image) {
                $taskImage = new TaskImage();
                $taskImage->task_id = $taskId;
                $taskImage->image = $imagePath;
                $taskImage->save();
                //Una ves guardado el task, insertamos un log-history
                $log_desc = 'Subida de imágen.';
                $this->storeTaskLog($task->id,$user->name,$log_desc);
            } else {
                // Si no existe una imagen principal, guardarla en el registro del task
                $task->image = $imagePath;
                $task->save();
                //Una ves guardado el task, insertamos un log-history
                $log_desc = 'Subida de imágen.';
                $this->storeTaskLog($task->id,$user->name,$log_desc);
            }
        }

        $task->load('client.addresses');
        $task->load('images');
        $task->load('logs');
        return response()->json([
            'ok'=>true,
            'task' => $task,
        ]);
    }

    private function generateTrackingCode()
    {
        do {
            $code = 'TRK-' . strtoupper(Str::random(6));
        } while (Task::where('tracking_code', $code)->exists());

        return $code;
    }

    private function storeTaskLog($task_id, $user, $description){
        $log = new TaskLog();
        $log->task_id    = $task_id;
        $log->user       = $user;
        $log->description= $description;
        $log->save();
    }

    private function processBase64ToImage($base64Data, $taskId){
        try {
            // Remover el prefijo data:image/...;base64, si existe
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
                $imageType = $matches[1]; // png, jpeg, etc.
                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
            } else {
                $imageType = 'png'; // Tipo por defecto
            }

            // Validar que sea PNG o JPEG
            if (!in_array(strtolower($imageType), ['png', 'jpeg', 'jpg'])) {
                throw new \Exception('Formato de imagen no válido. Solo se permiten PNG y JPEG.');
            }

            // Decodificar base64
            $imageData = base64_decode($base64Data);
            if ($imageData === false) {
                throw new \Exception('Error al decodificar la imagen base64.');
            }

            // Generar nombre único para el archivo
            $timestamp = now()->format('YmdHis');
            $filename = "task_{$taskId}_{$timestamp}.png";
            $relativePath = "signatures/{$filename}";

            // Crear directorio si no existe
            $fullPath = storage_path('app/public/signatures');
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Guardar el archivo
            $fullFilePath = "{$fullPath}/{$filename}";
            if (file_put_contents($fullFilePath, $imageData) === false) {
                throw new \Exception('Error al guardar el archivo de imagen.');
            }

            return $relativePath;

        } catch (\Exception $e) {
            throw new \Exception("Error procesando la imagen: " . $e->getMessage());
        }
    }

    private function deleteSignatureFile($signaturePath){
        if ($signaturePath && Storage::disk('public')->exists($signaturePath)) {
            Storage::disk('public')->delete($signaturePath);
        }
    }

    private function getSignaturePublicUrl($signaturePath){
        if ($signaturePath) {
            return asset('storage/' . $signaturePath);
        }
        return null;
    }

    public function deleteMainImage(Request $request){
        $user = $request->user();
        $task_id = $request->id;
        $task = Task::findOrFail($task_id);
        // Obtener la ruta de la imagen actual
        $imagePath = $task->image;
        // Verificar si hay una imagen almacenada y eliminarla
        if ($imagePath) {
            // Eliminar la imagen del almacenamiento
            Storage::disk('public')->delete($imagePath);
            // Limpiar el atributo de la imagen en el modelo
            $task->image = null;
            $task->save();

            $log_desc = 'Eliminación de imágen principal.';
            $this->storeTaskLog($task->id,$user->name,$log_desc);
        }

        $task->load('client.addresses');
        $task->load('images');
        $task->load('logs');
        return response()->json([
            'ok' => true,
            'task' => $task,
        ]);
    }//.deleteMainImage()

    public function deleteAltImage(Request $request){
        $user = $request->user();
        $taskId = $request->input('task.id'); // Obtener el 'id' del task del request
        $imgAltId = $request->input('img_alt_id'); // Obtener el 'img_alt_id' del request


        try {
            // Buscar la imagen alternativa por su ID
            $taskImage = TaskImage::findOrFail($imgAltId);

            // Verificar si la imagen alternativa pertenece al task indicado
            if ($taskImage->task_id != $taskId) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La imagen alternativa no pertenece al task indicado.',
                ], 400);
            }

            // Eliminar la imagen alternativa del almacenamiento
            Storage::disk('public')->delete($taskImage->image);

            // Eliminar el registro de la imagen alternativa de la base de datos
            $taskImage->delete();

            // Registrar la acción en el log del task
            $logDesc = 'Eliminación de imagen secundaria.';
            $this->storeTaskLog($taskId, $user->name, $logDesc);

            // Cargar el task con las relaciones actualizadas
            $task = Task::with('client.addresses', 'images', 'logs')->findOrFail($taskId);

            return response()->json([
                'ok' => true,
                'task' => $task,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al eliminar la imagen alternativa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//.deleteAltImage()

    public function saveSignature(Request $request){
        $user = $request->user();
        
        try {
            $task = Task::findOrFail($request->task_id);
            $signature_base64 = $request->signature;
            
            if (!$signature_base64) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La firma es requerida.',
                ], 400);
            }
            
            // Procesar base64 y guardar como archivo
            $signaturePath = $this->processBase64ToImage($signature_base64, $task->id);
            
            // Guardar la ruta en la base de datos
            $task->signature_path = $signaturePath;
            $task->save();
            
            $log_desc = 'Firma guardada.';
            $this->storeTaskLog($task->id, $user->name, $log_desc);
            
            $task->load('client');
            $task->load('images');
            $task->load('logs');
            
            return response()->json([
                'ok' => true,
                'task' => $task,
                'signature_url' => $this->getSignaturePublicUrl($signaturePath),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al guardar la firma.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//.saveSignature()

    public function updateSignature(Request $request){
        $user = $request->user();
        
        try {
            $task = Task::findOrFail($request->task_id);
            $signature_base64 = $request->signature;
            
            if (!$signature_base64) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La firma es requerida.',
                ], 400);
            }
            
            // Eliminar la firma anterior si existe
            if ($task->signature_path) {
                $this->deleteSignatureFile($task->signature_path);
            }
            
            // Procesar la nueva firma y guardar como archivo
            $signaturePath = $this->processBase64ToImage($signature_base64, $task->id);
            
            // Actualizar la ruta en la base de datos
            $task->signature_path = $signaturePath;
            $task->save();
            
            $log_desc = 'Firma actualizada.';
            $this->storeTaskLog($task->id, $user->name, $log_desc);
            
            $task->load('client');
            $task->load('images');
            $task->load('logs');
            
            return response()->json([
                'ok' => true,
                'task' => $task,
                'signature_url' => $this->getSignaturePublicUrl($signaturePath),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al actualizar la firma.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//.updateSignature()

    public function deleteSignature(Request $request){
        $user = $request->user();

        try {
            $task = Task::findOrFail($request->task_id);

            // Eliminar el archivo físico si existe
            if ($task->signature_path) {
                $this->deleteSignatureFile($task->signature_path);
            }

            // Limpiar el campo en la base de datos
            $task->signature_path = null;
            $task->save();

            $log_desc = 'Firma eliminada.';
            $this->storeTaskLog($task->id, $user->name, $log_desc);

            $task->load('client');
            $task->load('images');
            $task->load('logs');

            return response()->json([
                'ok' => true,
                'task' => $task,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al eliminar la firma.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//.deleteSignature()

    /**
     * POST /api/auth/tasks/{id}/assign
     * Asigna un colaborador a una tarea
     */
    public function assignUser(Request $request){
        $user = $request->user();

        try {
            // Validar que user_id venga en el request
            $request->validate([
                'user_id' => 'required|integer|exists:users,id'
            ]);

            $task = Task::where('shop_id', $user->shop_id)->findOrFail($request->id);

            // Validar que el colaborador pertenezca al mismo shop
            $collaborator = User::where('id', $request->user_id)
                                ->where('shop_id', $user->shop_id)
                                ->first();

            if (!$collaborator) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El colaborador no pertenece a tu tienda.',
                ], 403);
            }

            // Asignar el colaborador
            $task->assigned_user_id = $request->user_id;
            $task->save();

            // Registrar log
            $log_desc = 'Tarea asignada a: ' . $collaborator->name;
            $this->storeTaskLog($task->id, $user->name, $log_desc);

            // Cargar relaciones
            $task->load('client.addresses');
            $task->load('assignedUser');
            $task->load('images');
            $task->load('logs');

            return response()->json([
                'ok' => true,
                'task' => $task,
                'message' => 'Tarea asignada correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al asignar la tarea.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//.assignUser()

    /**
     * POST /api/auth/tasks/{id}/unassign
     * Desasigna al colaborador de una tarea
     */
    public function unassignUser(Request $request){
        $user = $request->user();

        try {
            $task = Task::where('shop_id', $user->shop_id)->findOrFail($request->id);

            // Guardar nombre del colaborador anterior para el log
            $previousAssignedName = $task->assignedUser ? $task->assignedUser->name : 'Sin asignar';

            // Desasignar
            $task->assigned_user_id = null;
            $task->save();

            // Registrar log
            $log_desc = 'Tarea desasignada (anteriormente: ' . $previousAssignedName . ')';
            $this->storeTaskLog($task->id, $user->name, $log_desc);

            // Cargar relaciones
            $task->load('client.addresses');
            $task->load('assignedUser');
            $task->load('images');
            $task->load('logs');

            return response()->json([
                'ok' => true,
                'task' => $task,
                'message' => 'Tarea desasignada correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al desasignar la tarea.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//.unassignUser()

    /**
     * GET /api/auth/tasks/collaborators
     * Obtiene lista de admins y colaboradores del shop para asignacion de tareas
     */
    public function getCollaborators(Request $request){
        $user = $request->user();

        try {
            // Obtener admins y colaboradores activos del mismo shop
            $collaborators = User::where('shop_id', $user->shop_id)
                ->where('active', 1)
                ->whereHas('roles', function($query) {
                    $query->whereIn('roles.id', [1, 2, 4]); // admin, admin, collaborator
                })
                ->select('id', 'name', 'email')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'ok' => true,
                'collaborators' => $collaborators,
                'count' => $collaborators->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al obtener colaboradores.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//.getCollaborators()

    /**
     * POST /api/auth/tasks/store-from-client
     * Crea una tarea desde la app del cliente (solicitud de servicio)
     * Reemplaza ClientServiceController@store
     */
    public function storeFromClient(Request $request){
        try {
            $user = $request->user();
            $shop = $user->shop;
            $client = $user->client;

            // Validar que el usuario tenga un cliente asociado
            if (!$client) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Usuario no tiene cliente asociado.',
                ], 403);
            }

            // Generar folio consecutivo por tienda
            $ultimo_folio = Task::where('shop_id', $shop->id)->max('folio');
            $nuevo_folio = $ultimo_folio ? $ultimo_folio + 1 : 1;

            $task = new Task();
            $task->shop_id = $shop->id;
            $task->folio = $nuevo_folio;
            $task->client_id = $client->id;
            $task->origin = 'client';
            $task->requested_by_user_id = $user->id;
            $task->active = 1;
            $task->status = 'NUEVO';
            $task->priority = 0;
            $task->title = $request->title;
            $task->description = $request->description;

            if ($request->hasFile('image')) {
                // Procesar y optimizar la imagen
                $imageService = new ImageService();
                $task->image = $imageService->processAndStore($request->file('image'), 'tasks');
            }

            $task->save();

            // Registrar log
            $this->storeTaskLog($task->id, $user->name, 'Solicitud de servicio creada por cliente.');

            // Cargar relaciones
            $task->load('client.addresses');
            $task->load('images');
            $task->load('logs');
            $task->load('requestedBy');

            // Enviar notificaciones a admins de la tienda
            $this->storeNotificationsForClientRequest($task, $client->name);

            return response()->json([
                'ok' => true,
                'task' => $task,
                'message' => 'Solicitud de servicio creada correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al crear la solicitud.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//.storeFromClient()

    /**
     * Crea notificaciones para admins cuando un cliente solicita servicio
     * Envía Pusher (tiempo real) + FCM (push notification)
     */
    private function storeNotificationsForClientRequest(Task $task, string $clientName){
        $shop_id = $task->shop_id;
        $task_id = $task->id;
        $task_title = $task->title;

        // Generar notification_group_id único
        $notification_group_id = Notification::generateGroupId();

        // Obtener usuarios admin/superadmin de la tienda
        $shop_users_admin = User::where('shop_id', $shop_id)
                                ->whereHas('roles', function($query) {
                                    $query->whereIn('role_user.role_id', [1, 2]);
                                })
                                ->where('active', 1)
                                ->get();

        $ntf_description = 'Solicitud de Servicio: ' . $clientName . ': ' . $task_title;
        $first_notification = null;

        foreach ($shop_users_admin as $user) {
            $new_ntf = new Notification();
            $new_ntf->notification_group_id = $notification_group_id;
            $new_ntf->user_id = $user->id;
            $new_ntf->description = $ntf_description;
            $new_ntf->type = 'task';
            $new_ntf->action = 'task_id';
            $new_ntf->data = $task_id;
            $new_ntf->read = 0;
            $new_ntf->visible = 1;
            $new_ntf->save();

            if ($first_notification === null) {
                $first_notification = $new_ntf;
            }
        }

        // Pusher: notificación en tiempo real (una sola vez)
        if ($first_notification) {
            event(new ClientServiceNotification($first_notification, $shop_id));
        }

        // FCM: push notification para app cerrada
        try {
            \Log::info('🚀 FCM: Enviando push de solicitud de servicio (Task)', [
                'shop_id' => $shop_id,
                'task_id' => $task_id,
                'client_name' => $clientName
            ]);

            $firebaseService = app(FirebaseService::class);
            $result = $firebaseService->sendToShopAdmins(
                $shop_id,
                'Nueva Solicitud de Servicio',
                "Solicitud de Servicio: {$clientName}",
                [
                    'type' => 'task',
                    'task_id' => (string)$task_id,
                    'origin' => 'client',
                    'shop_id' => (string)$shop_id
                ]
            );

            \Log::info('✅ FCM: Push notification procesada', ['result' => $result]);
        } catch (\Exception $e) {
            \Log::error('❌ FCM: Error enviando push notification', [
                'error' => $e->getMessage()
            ]);
        }
    }//.storeNotificationsForClientRequest()

    /**
     * GET /api/auth/tasks/client
     * Lista tareas del cliente autenticado (origin='client')
     * Reemplaza ClientServiceController@index
     */
    public function indexFromClient(Request $request){
        $user = $request->user();
        $shop = $user->shop;
        $client = $user->client;

        if (!$client) {
            return response()->json([
                'ok' => false,
                'message' => 'Usuario no tiene cliente asociado.',
            ], 403);
        }

        $buscar = $request->buscar;

        $query = Task::with('client.addresses')
                    ->with('images')
                    ->with('logs')
                    ->with('assignedUser')
                    ->with('checklistItems')
                    ->with('currentServiceStep')
                    ->with('serviceTrackingHistory.step')
                    ->with('serviceTrackingHistory.changedBy')
                    ->where('shop_id', $shop->id)
                    ->where('client_id', $client->id)
                    ->where('origin', 'client');

        if ($buscar != '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('title', 'like', '%' . $buscar . '%')
                  ->orWhere('description', 'like', '%' . $buscar . '%');
            });
        }

        $query->orderBy('id', 'desc');

        $tasks = $query->paginate(15);

        return $tasks;
    }//.indexFromClient()

    /**
     * POST /api/auth/tasks/client/upload-image
     * Subir imagen a tarea desde cliente
     */
    public function uploadImageFromClient(Request $request){
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            return response()->json([
                'ok' => false,
                'message' => 'Usuario no tiene cliente asociado.',
            ], 403);
        }

        $taskId = $request->task_id;
        $task = Task::where('client_id', $client->id)
                    ->where('origin', 'client')
                    ->findOrFail($taskId);

        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // Procesar y optimizar la imagen (redimensiona y comprime)
            $imageService = new ImageService();
            $imagePath = $imageService->processAndStore($image, 'tasks');

            if ($task->image) {
                $taskImage = new TaskImage();
                $taskImage->task_id = $taskId;
                $taskImage->image = $imagePath;
                $taskImage->save();
            } else {
                $task->image = $imagePath;
                $task->save();
            }

            $this->storeTaskLog($task->id, $user->name, 'Subida de imagen por cliente.');
        }

        $task->load('client.addresses');
        $task->load('images');
        $task->load('logs');

        return response()->json([
            'ok' => true,
            'task' => $task,
        ]);
    }//.uploadImageFromClient()

    /**
     * POST /api/auth/tasks/client/delete-main-image
     * Eliminar imagen principal desde cliente
     */
    public function deleteMainImageFromClient(Request $request){
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            return response()->json([
                'ok' => false,
                'message' => 'Usuario no tiene cliente asociado.',
            ], 403);
        }

        $task_id = $request->id;
        $task = Task::where('client_id', $client->id)
                    ->where('origin', 'client')
                    ->findOrFail($task_id);

        if ($task->image) {
            Storage::disk('public')->delete($task->image);
            $task->image = null;
            $task->save();

            $this->storeTaskLog($task->id, $user->name, 'Eliminación de imagen principal por cliente.');
        }

        $task->load('client.addresses');
        $task->load('images');
        $task->load('logs');

        return response()->json([
            'ok' => true,
            'task' => $task,
        ]);
    }//.deleteMainImageFromClient()

    /**
     * GET /api/auth/tasks/{id}/products
     * Obtiene los productos asignados a una tarea
     */
    public function getProducts(Request $request, $id){
        $user = $request->user();

        try {
            $task = Task::where('shop_id', $user->shop_id)->findOrFail($id);

            // Cargar productos con relación al producto base
            $task->load(['products.product']);

            return response()->json([
                'ok' => true,
                'products' => $task->products,
                'count' => $task->products->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al obtener productos de la tarea.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//.getProducts()

    /**
     * POST /api/auth/tasks/client/update
     * Actualizar tarea desde cliente
     */
    public function updateFromClient(Request $request){
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            return response()->json([
                'ok' => false,
                'message' => 'Usuario no tiene cliente asociado.',
            ], 403);
        }

        $task = Task::where('client_id', $client->id)
                    ->where('origin', 'client')
                    ->findOrFail($request->id);

        $task->title = $request->title;
        $task->description = $request->description;
        $task->save();

        $this->storeTaskLog($task->id, $user->name, 'Edición del registro por cliente.');

        $task->load('client.addresses');
        $task->load('images');
        $task->load('logs');

        return response()->json([
            'ok' => true,
            'task' => $task
        ]);
    }//.updateFromClient()

    /**
     * PUT /api/auth/tasks/{id}/checklist/{itemId}/toggle
     * Toggle completado de un item del checklist
     */
    public function toggleChecklistItem(Request $request, $id, $itemId){
        $user = $request->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($id);
        $item = TaskChecklistItem::where('task_id', $task->id)->findOrFail($itemId);

        $item->is_completed = !$item->is_completed;
        $item->save();

        return response()->json([
            'ok' => true,
            'item' => $item,
        ]);
    }//.toggleChecklistItem()

    /**
     * GET /print-task-checklist?id={id}
     * Ruta pública para exportar PDF del checklist (Browser.open)
     */
    public function printChecklistPdf(Request $request){
        $id = $request->id;
        $task = Task::with(['client', 'assignedUser', 'checklistItems'])->findOrFail($id);
        $task->shop = $task->shop_id ? \App\Models\Shop::find($task->shop_id) : null;

        $pdfPhraseData = PdfPhrase::getRandom();

        $pdf = Pdf::loadView('task_checklist_pdf', [
            'task' => $task,
            'pdfPhrase' => $pdfPhraseData['phrase'],
            'pdfPhraseUrl' => $pdfPhraseData['link_url'],
        ]);

        $name_file = $request->name_file ?? "checklist_tarea_{$task->id}";
        return $pdf->stream("{$name_file}.pdf", array("Attachment" => false));
    }//.printChecklistPdf()

    /**
     * GET /print-task-reception?id={id}
     * Ruta pública para imprimir comprobante de recepción (Browser.open)
     */
    public function printReceptionPdf(Request $request){
        $id = $request->id;
        $task = Task::with(['client.addresses', 'assignedUser', 'currentServiceStep'])->findOrFail($id);
        $task->shop = $task->shop_id ? \App\Models\Shop::find($task->shop_id) : null;

        $steps = ServiceTrackingStep::where('shop_id', $task->shop_id)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();

        $qrImage = null;
        if ($task->tracking_code) {
            $trackingUrl = url('/service-tracking/' . $task->tracking_code);
            $qrImage = 'data:image/svg+xml;base64,' . base64_encode(
                \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->generate($trackingUrl)
            );
        }

        $pdfPhraseData = PdfPhrase::getRandom();

        $pdf = Pdf::loadView('task_reception_pdf', [
            'task' => $task,
            'steps' => $steps,
            'qrImage' => $qrImage,
            'pdfPhrase' => $pdfPhraseData['phrase'],
            'pdfPhraseUrl' => $pdfPhraseData['link_url'],
        ]);

        $name_file = $request->name_file ?? "comprobante_recepcion_{$task->folio}";
        return $pdf->stream("{$name_file}.pdf", array("Attachment" => false));
    }//.printReceptionPdf()

    /**
     * GET /api/auth/tasks/{id}/checklist-pdf
     * Ruta API autenticada para descargar PDF del checklist (compartir)
     */
    public function downloadChecklistPdf(Request $request, $id){
        $user = $request->user();
        $shop = $user->shop;

        $task = Task::with(['client', 'assignedUser', 'checklistItems'])
            ->where('shop_id', $shop->id)
            ->findOrFail($id);

        $task->shop = $shop;

        $pdfPhraseData = PdfPhrase::getRandom();

        $pdf = Pdf::loadView('task_checklist_pdf', [
            'task' => $task,
            'pdfPhrase' => $pdfPhraseData['phrase'],
            'pdfPhraseUrl' => $pdfPhraseData['link_url'],
        ]);

        return $pdf->stream("checklist_tarea_{$task->id}.pdf", array("Attachment" => false));
    }//.downloadChecklistPdf()

    /**
     * POST /api/auth/tasks/{id}/checklist
     * Agregar item al checklist
     */
    public function addChecklistItem(Request $request, $id){
        $request->validate([
            'text' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($id);

        $maxOrder = $task->checklistItems()->max('sort_order') ?? -1;

        $item = TaskChecklistItem::create([
            'task_id' => $task->id,
            'text' => $request->text,
            'is_completed' => false,
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'ok' => true,
            'item' => $item,
            'message' => 'Item agregado.'
        ]);
    }//.addChecklistItem()

    /**
     * DELETE /api/auth/tasks/{id}/checklist/{itemId}
     * Eliminar item del checklist
     */
    public function deleteChecklistItem(Request $request, $id, $itemId){
        $user = $request->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($id);
        $item = TaskChecklistItem::where('task_id', $task->id)->findOrFail($itemId);

        $item->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Item eliminado.'
        ]);
    }//.deleteChecklistItem()

    /**
     * GET /api/auth/tasks/checklist/search-catalog?q=texto
     * Buscar productos y servicios para agregar al checklist
     */
    public function searchChecklistCatalog(Request $request){
        $user = $request->user();
        $shop = $user->shop;
        $q = $request->q ?? '';

        if (strlen($q) < 2) {
            return response()->json(['ok' => true, 'results' => []]);
        }

        $products = Product::where('shop_id', $shop->id)
            ->where('active', 1)
            ->where(function($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('key', 'like', "%{$q}%");
            })
            ->select('id', 'name', 'key as code', 'retail as price')
            ->limit(10)
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'type' => 'producto', 'name' => $p->name, 'code' => $p->code, 'price' => $p->price]);

        $services = Service::where('shop_id', $shop->id)
            ->where('active', 1)
            ->where('name', 'like', "%{$q}%")
            ->select('id', 'name', 'price')
            ->limit(10)
            ->get()
            ->map(fn($s) => ['id' => $s->id, 'type' => 'servicio', 'name' => $s->name, 'code' => null, 'price' => $s->price]);

        $results = $products->concat($services)->sortBy('name')->values();

        return response()->json(['ok' => true, 'results' => $results]);
    }//.searchChecklistCatalog()

    // ==========================================
    // SERVICE TRACKING (API)
    // ==========================================

    /**
     * Obtener pasos de tracking de la tienda
     */
    public function getServiceTrackingSteps(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $steps = ServiceTrackingStep::where('shop_id', $shop->id)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'ok' => true,
            'steps' => $steps,
        ]);
    }

    /**
     * Obtener historial de tracking de una tarea
     */
    public function getServiceTracking(Request $request, $id)
    {
        $user = $request->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($id);

        $steps = ServiceTrackingStep::where('shop_id', $shop->id)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();

        $history = TaskServiceTracking::where('task_id', $task->id)
            ->with(['step', 'changedBy', 'evidence'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'ok' => true,
            'current_step_id' => $task->current_service_step_id,
            'tracking_code' => $task->tracking_code,
            'steps' => $steps,
            'history' => $history,
        ]);
    }

    /**
     * Descargar PDF del comprobante de recepcion
     */
    public function receptionPdf(Request $request, $id)
    {
        $user = $request->user();
        $shop = $user->shop;

        $task = Task::with(['client.addresses', 'assignedUser', 'currentServiceStep'])
            ->where('shop_id', $shop->id)
            ->findOrFail($id);

        $task->shop = $shop;

        $steps = ServiceTrackingStep::where('shop_id', $shop->id)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();

        $qrImage = null;
        if ($task->tracking_code) {
            $trackingUrl = url('/service-tracking/' . $task->tracking_code);
            $qrImage = 'data:image/svg+xml;base64,' . base64_encode(
                \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->generate($trackingUrl)
            );
        }

        $pdfPhraseData = PdfPhrase::getRandom();

        $pdf = Pdf::loadView('task_reception_pdf', [
            'task' => $task,
            'steps' => $steps,
            'qrImage' => $qrImage,
            'pdfPhrase' => $pdfPhraseData['phrase'],
            'pdfPhraseUrl' => $pdfPhraseData['link_url'],
        ]);

        return $pdf->stream("comprobante_recepcion_{$task->folio}.pdf");
    }

    /**
     * Cambiar el paso actual de tracking de una tarea
     */
    public function updateServiceStep(Request $request, $id)
    {
        $request->validate([
            'step_id' => 'required|exists:service_tracking_steps,id',
            'notes' => 'nullable|string|max:500',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($id);

        $step = ServiceTrackingStep::where('shop_id', $shop->id)
            ->where('id', $request->step_id)
            ->firstOrFail();

        $task->current_service_step_id = $step->id;

        if (!$task->tracking_code) {
            $task->tracking_code = $this->generateTrackingCode();
        }

        $task->save();

        $trackingEntry = TaskServiceTracking::create([
            'task_id' => $task->id,
            'step_id' => $step->id,
            'changed_by_user_id' => $user->id,
            'notes' => $request->notes,
        ]);

        // Procesar imágenes de evidencia si vienen
        if ($request->hasFile('images')) {
            $imageService = new ImageService();
            foreach ($request->file('images') as $file) {
                $imagePath = $imageService->processAndStore($file, 'tracking-evidence');
                ServiceTrackingEvidence::create([
                    'tracking_id' => $trackingEntry->id,
                    'image' => $imagePath,
                ]);
            }
        }

        $this->storeTaskLog($task->id, $user->name, 'Seguimiento actualizado a: ' . $step->name);

        $task->load('currentServiceStep');

        return response()->json([
            'ok' => true,
            'task' => $task,
            'message' => 'Paso de seguimiento actualizado.',
        ]);
    }

    /**
     * Subir evidencia a un registro de tracking existente
     */
    public function uploadTrackingEvidence(Request $request, $taskId, $trackingId)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'caption' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($taskId);

        $tracking = TaskServiceTracking::where('id', $trackingId)
            ->where('task_id', $task->id)
            ->firstOrFail();

        if ($tracking->evidence()->count() >= 5) {
            return response()->json([
                'ok' => false,
                'message' => 'Máximo 5 fotos por paso.',
            ], 422);
        }

        $imageService = new ImageService();
        $imagePath = $imageService->processAndStore($request->file('image'), 'tracking-evidence');

        $evidence = ServiceTrackingEvidence::create([
            'tracking_id' => $tracking->id,
            'image' => $imagePath,
            'caption' => $request->caption,
        ]);

        $stepName = $tracking->step->name ?? 'paso';
        $this->storeTaskLog($task->id, $user->name, "Evidencia agregada al paso: {$stepName}");

        return response()->json([
            'ok' => true,
            'evidence' => $evidence,
            'message' => 'Evidencia subida correctamente.',
        ]);
    }

    /**
     * Eliminar una evidencia de tracking
     */
    public function deleteTrackingEvidence(Request $request, $taskId, $evidenceId)
    {
        $user = $request->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($taskId);

        $evidence = ServiceTrackingEvidence::where('id', $evidenceId)
            ->whereHas('tracking', function ($q) use ($task) {
                $q->where('task_id', $task->id);
            })
            ->firstOrFail();

        $fullPath = storage_path("app/public/{$evidence->image}");
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $evidence->delete();

        $this->storeTaskLog($task->id, $user->name, 'Evidencia eliminada.');

        return response()->json([
            'ok' => true,
            'message' => 'Evidencia eliminada.',
        ]);
    }

}
