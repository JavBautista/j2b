<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskTrackingHistory;
use Carbon\Carbon;

class TaskTrackingService
{
    protected FirebaseRealtimeService $firebase;

    public function __construct(FirebaseRealtimeService $firebase)
    {
        $this->firebase = $firebase;
    }

    /**
     * Iniciar tracking de una tarea
     */
    public function startTracking(Task $task, float $lat, float $lng): array
    {
        // Validar que la tarea no tenga tracking activo
        if ($task->tracking_active) {
            throw new \Exception('La tarea ya tiene tracking activo');
        }

        // Actualizar tarea en MySQL
        $task->update([
            'tracking_active' => true,
            'tracking_started_at' => now(),
            'tracking_start_lat' => $lat,
            'tracking_start_lng' => $lng,
        ]);

        // Crear nodo en Firebase
        $firebasePath = "tracking/shop_{$task->shop_id}/tasks_active/task_{$task->id}";

        $this->firebase->set($firebasePath, [
            'metadata' => [
                'task_id' => $task->id,
                'assigned_user_id' => $task->assigned_user_id,
                'assigned_user_name' => $task->assignedUser->name ?? 'Sin asignar',
                'client_name' => $task->client->name ?? 'Sin cliente',
                'started_at' => $task->tracking_started_at->toIso8601String(),
                'active' => true,
            ],
            'last_position' => [
                'lat' => $lat,
                'lng' => $lng,
                'timestamp' => now()->toIso8601String(),
                'accuracy' => 0,
                'speed' => 0,
                'heading' => 0,
            ],
            'points' => [
                'point_' . now()->timestamp => [
                    'lat' => $lat,
                    'lng' => $lng,
                    'timestamp' => now()->toIso8601String(),
                    'accuracy' => 0,
                ]
            ],
        ]);

        // Actualizar estado online del usuario
        if ($task->assigned_user_id) {
            $userPath = "tracking/shop_{$task->shop_id}/users_online/user_{$task->assigned_user_id}";
            $this->firebase->set($userPath, [
                'name' => $task->assignedUser->name ?? 'Sin nombre',
                'last_activity' => now()->toIso8601String(),
                'active_task_id' => $task->id,
                'current_position' => [
                    'lat' => $lat,
                    'lng' => $lng,
                ],
            ]);
        }

        return [
            'success' => true,
            'firebase_path' => $firebasePath,
            'task' => $task->fresh()->load(['assignedUser', 'client']),
        ];
    }

    /**
     * Finalizar tracking y procesar datos
     */
    public function finishTracking(Task $task, float $lat, float $lng): array
    {
        // Validar que la tarea tenga tracking activo
        if (!$task->tracking_active) {
            throw new \Exception('La tarea no tiene tracking activo');
        }

        $firebasePath = "tracking/shop_{$task->shop_id}/tasks_active/task_{$task->id}";

        // Leer todos los puntos de Firebase antes de borrar
        $firebaseData = $this->firebase->get($firebasePath);

        if (!$firebaseData || !isset($firebaseData['points'])) {
            throw new \Exception('No se encontraron datos de tracking en Firebase');
        }

        // Procesar puntos GPS
        $points = $firebaseData['points'];
        $pointsArray = [];

        foreach ($points as $key => $point) {
            $pointsArray[] = [
                'lat' => $point['lat'],
                'lng' => $point['lng'],
                'timestamp' => $point['timestamp'],
                'accuracy' => $point['accuracy'] ?? 0,
            ];
        }

        // Ordenar por timestamp
        usort($pointsArray, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });

        // Calcular distancia total
        $distanceTotal = $this->calculateTotalDistance($pointsArray);

        // Calcular duración
        $startTime = Carbon::parse($task->tracking_started_at);
        $endTime = now();
        $durationMinutes = $startTime->diffInMinutes($endTime);

        // Calcular velocidad promedio
        $avgSpeed = $durationMinutes > 0 ? ($distanceTotal / $durationMinutes) * 60 : 0;

        // Simplificar ruta (guardar 1 punto cada 5 minutos aprox)
        $simplifiedRoute = $this->simplifyRoute($pointsArray, 5);

        // Actualizar tarea en MySQL
        $task->update([
            'tracking_active' => false,
            'tracking_finished_at' => $endTime,
            'tracking_end_lat' => $lat,
            'tracking_end_lng' => $lng,
            'tracking_distance_km' => round($distanceTotal, 2),
            'tracking_points_count' => count($pointsArray),
            'tracking_duration_minutes' => $durationMinutes,
        ]);

        // Guardar en tabla histórico
        TaskTrackingHistory::create([
            'shop_id' => $task->shop_id,
            'task_id' => $task->id,
            'assigned_user_id' => $task->assigned_user_id,
            'tracking_date' => $startTime->toDateString(),
            'start_lat' => $task->tracking_start_lat,
            'start_lng' => $task->tracking_start_lng,
            'start_timestamp' => $task->tracking_started_at,
            'end_lat' => $lat,
            'end_lng' => $lng,
            'end_timestamp' => $endTime,
            'gps_points_count' => count($pointsArray),
            'distance_km' => round($distanceTotal, 2),
            'duration_minutes' => $durationMinutes,
            'avg_speed_kmh' => round($avgSpeed, 2),
            'route_points' => $simplifiedRoute,
            'firebase_path' => $firebasePath,
        ]);

        // Eliminar datos de Firebase (para ahorrar espacio)
        $this->firebase->remove($firebasePath);

        // Actualizar estado usuario
        if ($task->assigned_user_id) {
            $userPath = "tracking/shop_{$task->shop_id}/users_online/user_{$task->assigned_user_id}";
            $this->firebase->update($userPath, [
                'last_activity' => now()->toIso8601String(),
                'active_task_id' => null,
            ]);
        }

        return [
            'success' => true,
            'task' => $task->fresh()->load(['assignedUser', 'client']),
            'distance_km' => round($distanceTotal, 2),
            'duration_minutes' => $durationMinutes,
            'points_registered' => count($pointsArray),
        ];
    }

    /**
     * Calcular distancia total usando fórmula Haversine
     */
    protected function calculateTotalDistance(array $points): float
    {
        if (count($points) < 2) {
            return 0;
        }

        $totalDistance = 0;

        for ($i = 0; $i < count($points) - 1; $i++) {
            $p1 = $points[$i];
            $p2 = $points[$i + 1];

            $totalDistance += $this->haversine(
                $p1['lat'], $p1['lng'],
                $p2['lat'], $p2['lng']
            );
        }

        return $totalDistance;
    }

    /**
     * Fórmula de Haversine para calcular distancia entre dos puntos GPS
     */
    protected function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Simplificar ruta guardando 1 punto cada N minutos
     */
    protected function simplifyRoute(array $points, int $intervalMinutes): array
    {
        if (count($points) === 0) {
            return [];
        }

        $simplifiedRoute = [];
        $lastTimestamp = null;

        foreach ($points as $point) {
            $timestamp = strtotime($point['timestamp']);

            if ($lastTimestamp === null ||
                ($timestamp - $lastTimestamp) >= ($intervalMinutes * 60)) {

                $simplifiedRoute[] = [
                    'lat' => $point['lat'],
                    'lng' => $point['lng'],
                    'timestamp' => $point['timestamp'],
                ];

                $lastTimestamp = $timestamp;
            }
        }

        return $simplifiedRoute;
    }

    /**
     * Obtener tareas activas con tracking
     */
    public function getActiveTasks(int $shopId): array
    {
        $tasks = Task::where('shop_id', $shopId)
            ->where('tracking_active', true)
            ->with(['assignedUser', 'client'])
            ->get();

        return $tasks->map(function($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'assigned_user' => [
                    'id' => $task->assigned_user_id,
                    'name' => $task->assignedUser->name ?? 'Sin asignar',
                ],
                'client' => [
                    'id' => $task->client_id ?? null,
                    'name' => $task->client->name ?? 'Sin cliente',
                ],
                'description' => $task->description,
                'tracking_started_at' => $task->tracking_started_at?->toIso8601String(),
                'firebase_path' => "tracking/shop_{$task->shop_id}/tasks_active/task_{$task->id}",
            ];
        })->toArray();
    }

    /**
     * Obtener histórico de tracking de una tarea
     */
    public function getTaskHistory(int $taskId): array
    {
        $history = TaskTrackingHistory::where('task_id', $taskId)
            ->with(['assignedUser'])
            ->orderBy('start_timestamp', 'desc')
            ->get();

        return $history->toArray();
    }
}
