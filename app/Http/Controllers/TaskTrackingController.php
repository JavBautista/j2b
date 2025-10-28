<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\TaskTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskTrackingController extends Controller
{
    protected TaskTrackingService $trackingService;

    public function __construct(TaskTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * POST /api/tasks/{id}/tracking/start
     * Iniciar tracking de una tarea
     */
    public function start(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        try {
            $user = $request->user();
            $task = Task::where('shop_id', $user->shop_id)->findOrFail($id);

            // Validar que el usuario sea el asignado a la tarea
            if ($task->assigned_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para iniciar el tracking de esta tarea'
                ], 403);
            }

            $result = $this->trackingService->startTracking(
                $task,
                $request->lat,
                $request->lng
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * POST /api/tasks/{id}/tracking/finish
     * Finalizar tracking de una tarea
     */
    public function finish(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        try {
            $user = $request->user();
            $task = Task::where('shop_id', $user->shop_id)->findOrFail($id);

            // Validar que el usuario sea el asignado a la tarea
            if ($task->assigned_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para finalizar el tracking de esta tarea'
                ], 403);
            }

            $result = $this->trackingService->finishTracking(
                $task,
                $request->lat,
                $request->lng
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/tasks/tracking/active
     * Obtener tareas activas con tracking
     */
    public function getActiveTasks(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $tasks = $this->trackingService->getActiveTasks($user->shop_id);

            return response()->json([
                'success' => true,
                'tasks' => $tasks
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/tasks/{id}/tracking/history
     * Obtener histórico de tracking de una tarea
     */
    public function getHistory(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $task = Task::where('shop_id', $user->shop_id)->findOrFail($id);

            $history = $this->trackingService->getTaskHistory($task->id);

            return response()->json([
                'success' => true,
                'task' => $task,
                'history' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }
}
