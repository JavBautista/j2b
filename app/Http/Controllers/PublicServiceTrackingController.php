<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\ServiceTrackingStep;
use App\Models\TaskServiceTracking;

class PublicServiceTrackingController extends Controller
{
    public function show($trackingCode)
    {
        $task = Task::where('tracking_code', $trackingCode)
            ->whereNotNull('current_service_step_id')
            ->firstOrFail();

        $shop = $task->shop;

        $steps = ServiceTrackingStep::where('shop_id', $shop->id)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();

        $history = TaskServiceTracking::where('task_id', $task->id)
            ->with(['step', 'changedBy'])
            ->orderBy('created_at', 'asc')
            ->get();

        $currentStepIndex = $steps->search(fn($s) => $s->id === $task->current_service_step_id);

        return view('public.service-tracking', compact('task', 'shop', 'steps', 'history', 'currentStepIndex'));
    }
}
