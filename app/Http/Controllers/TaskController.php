<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
     public function index(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        $ordenar = $request->filtro_ordenar;

        $query = Task::with('client')->where('shop_id', $shop->id);

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

        /*if($buscar==''){
            $tasks = Task::with('client')->where('shop_id',$shop->id)
                    ->orderBy('id','desc')
                    ->paginate(10);
        }else{
            $tasks = Task::with('client')->where('shop_id',$shop->id)
                    ->where('title', 'like', '%'.$buscar.'%')
                    ->orWhere('description', 'like', '%'.$buscar.'%')
                    ->orderBy('id','desc')
                    ->paginate(10);
        }*/

        return $tasks;
    }//index()

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
        $directory_id =  $request->directory_id;
        //$task->image = $request->file('image')->store('tasks', 'public');
        $task->save();

        $task->load('client');

        //$task_new = Task::with('client')->find($task->id);

        return response()->json([
            'ok'=>true,
            'task' => $task,
        ]);
    }//.store

    public function update(Request $request){

        $task = Task::findOrFail($request->id);
        $task->priority = $request->priority;
        $task->title    = $request->title;
        $task->description = $request->description;
        $task->solution = $request->solution;
        $task->save();

        $task->load('client');

        return response()->json([
            'ok' => true,
            'task' => $task
        ]);
    }//.update

    public function active(Request $request){
        $task = Task::findOrFail($request->id);
        $task->active = 1;
        $task->save();
        return response()->json([
            'ok'=>true,
            'task' => $task,
        ]);
    }//.active
    public function inactive(Request $request){
        $task = Task::findOrFail($request->id);
        $task->active = 0;
        $task->save();
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

        // Eliminar la tarea
        $task->delete();

        return response()->json([
            'ok'=>true
        ]);
    }//.destroy

    public function updateEstatus(Request $request){
        $task = Task::findOrFail($request->input('task')['id']);
        $new_status = $request->input('estatus');
        $task->status = $new_status;
        $task->save();

        $task->load('client');

        return response()->json([
            'ok' => true,
            'task' => $task
        ]);
    }//.updateEstatus
}
