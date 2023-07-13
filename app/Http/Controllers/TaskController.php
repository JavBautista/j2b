<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskImage;
use App\Models\TaskLog;
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

    public function updateEstatus(Request $request){
        $user = $request->user();
        $task = Task::findOrFail($request->input('task')['id']);
        $new_status = $request->input('estatus');
        $task->status = $new_status;
        $task->save();
        //Una ves guardado el task, insertamos un log-history
        $log_desc = 'Actualización de estatus: '.$new_status;
        $this->storeTaskLog($task->id,$user->name,$log_desc);
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

}
