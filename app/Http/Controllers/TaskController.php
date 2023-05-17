<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Carbon;

class TaskController extends Controller
{
     public function index(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        if($buscar==''){
            $tasks = Task::with('client')->where('shop_id',$shop->id)
                    ->orderBy('id','desc')
                    ->paginate(10);
        }else{
            $tasks = Task::with('client')->where('shop_id',$shop->id)
                    ->where('title', 'like', '%'.$buscar.'%')
                    ->orWhere('description', 'like', '%'.$buscar.'%')
                    ->orderBy('id','desc')
                    ->paginate(10);
        }

        return $tasks;
    }//index()

    public function store(Request $request){

        $user = $request->user();
        $shop = $user->shop;

        $ff = Carbon::parse($request->expiration);
        $date_expiration = Carbon::createFromFormat('Y-m-d H:i:s', $ff )->format('Y-m-d');

        $now = now();

        $task = new Task();
        $task->shop_id = $shop->id;
        $task->client_id = $request->client_id;
        $task->active    = 1;
        $task->priority  = $request->priority;
        $task->title     = $request->title;
        $task->description = $request->description;
        $task->solution = $request->solution;
        $task->expiration = $date_expiration;
        $task->save();

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
        return response()->json([
            'ok'=>true,
            'task' => $task,
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
        $task->delete();
        return response()->json([
            'ok'=>true
        ]);
    }//.destroy
}
