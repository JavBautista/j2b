<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\TaskTrackingService;
use Illuminate\Http\Request;

class TaskMonitoringController extends Controller
{
    protected TaskTrackingService $trackingService;

    public function __construct(TaskTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    public function index()
    {
        return view('admin.monitoreo.index');
    }

    public function get(Request $request)
    {
        $user = auth()->user();
        $shopId = $user->shop_id;
        $buscar = trim((string) $request->query('buscar', ''));
        $perPage = (int) $request->query('per_page', 15);

        $query = Task::where('shop_id', $shopId)
            ->where(function ($q) {
                $q->where('tracking_active', true)
                  ->orWhereNotNull('tracking_started_at');
            })
            ->with([
                'assignedUser:id,name',
                'client:id,name',
                'trackingHistory' => function ($q) {
                    $q->orderByDesc('start_timestamp');
                },
            ]);

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('title', 'like', "%{$buscar}%")
                  ->orWhere('id', $buscar)
                  ->orWhereHas('assignedUser', fn($qq) => $qq->where('name', 'like', "%{$buscar}%"))
                  ->orWhereHas('client', fn($qq) => $qq->where('name', 'like', "%{$buscar}%"));
            });
        }

        $tasks = $query->orderByDesc('tracking_active')
            ->orderByDesc('tracking_started_at')
            ->paginate($perPage);

        return response()->json($tasks);
    }

    public function counters(Request $request)
    {
        $shopId = auth()->user()->shop_id;

        $base = Task::where('shop_id', $shopId);

        $activos = (clone $base)->where('tracking_active', true)->count();

        $finalizados = (clone $base)
            ->where('tracking_active', false)
            ->whereNotNull('tracking_started_at')
            ->whereNotNull('tracking_finished_at')
            ->count();

        $sospechosos = (clone $base)
            ->where('tracking_active', false)
            ->whereNotNull('tracking_finished_at')
            ->where('tracking_distance_km', 0)
            ->count();

        return response()->json([
            'activos' => $activos,
            'finalizados' => $finalizados,
            'sospechosos' => $sospechosos,
        ]);
    }

    public function history(Request $request, int $id)
    {
        $shopId = auth()->user()->shop_id;
        $task = Task::where('shop_id', $shopId)->findOrFail($id);
        $history = $this->trackingService->getTaskHistory($task->id);

        return response()->json([
            'task' => $task,
            'history' => $history,
        ]);
    }
}
