<?php

namespace App\Http\Controllers;

use App\Events\TaskUpdated;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskImage;
use App\Models\TaskLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        $ordenar = $request->filtro_ordenar;

        $query = Task::with('client')
                        ->with('images')
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

        $task = new Task();
        $task->shop_id = $shop->id;
        $task->client_id = $request->client_id;
        $task->active    = 1;
        $task->status    = 'NUEVO';
        $task->priority  = $request->priority;
        $task->title     = $request->title;
        $task->description = $request->description;
        $task->solution = $request->solution;
        $task->expiration = $date_expiration;

        if ($request->hasFile('image')) {
            $task->image = $request->file('image')->store('tasks', 'public');
        }

        $task->save();

        //Una ves creado el task, insertamos un log-history
        $this->storeTaskLog($task->id,$user->name,'Creación del registro.');


        $task->load('client');
        $task->load('images');
        $task->load('logs');

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

        //Una ves guardado el task, insertamos un log-history
        $this->storeTaskLog($task->id,$user->name,'Edición del registro.');

        $task->load('client');
        $task->load('images');
        $task->load('logs');

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

        $task->load('client');
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

        $task->load('client');
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

        //Obtenemos los usuarios tipo admin o superadmin dela tienda
        $shop_users_admin = User::where('shop_id', $shop_id)
                                ->whereHas('roles', function($query) {
                                    $query->whereIn('role_user.role_id', [1, 2]);
                                })
                                ->where('active', 1)
                                ->get();

        foreach($shop_users_admin as $user){
            $new_ntf = new Notification();
            $new_ntf->user_id     = $user->id;
            $new_ntf->description = 'F# '.$task->id.' '.$task->title;
            $new_ntf->type        = 'task';
            $new_ntf->action      = 'task_id';
            $new_ntf->data        = $task_id;
            $new_ntf->read        = 0;
            $new_ntf->save();
        }
    }//storeNotificationsTaskForShop()

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
        if($new_status == 'ATENDIDO'){ $this->storeNotificationsTaskForShop($task); }



        $task->load('client');
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

        $task->load('client');
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

            // Guardar la imagen en la ubicación 'public'
            $imagePath = $image->store('tasks', 'public');

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

        $task->load('client');
        $task->load('images');
        $task->load('logs');
        return response()->json([
            'ok'=>true,
            'task' => $task,
        ]);
    }

    private function storeTaskLog($task_id, $user, $description){
        $log = new TaskLog();
        $log->task_id    = $task_id;
        $log->user       = $user;
        $log->description= $description;
        $log->save();
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

        $task->load('client');
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
            $task = Task::with('client', 'images', 'logs')->findOrFail($taskId);

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

}
