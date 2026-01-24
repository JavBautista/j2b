<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskImage;
use App\Models\TaskLog;
use App\Models\TaskProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageService;

class TasksController extends Controller
{
    /**
     * Página principal de tareas (vista Blade con componente Vue)
     */
    public function index()
    {
        $user = auth()->user();
        $shop = $user->shop;

        return view('admin.tasks.index', compact('shop'));
    }

    /**
     * Obtener tareas paginadas con filtros (JSON para Vue)
     */
    public function get(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        $ordenar = $request->filtro_ordenar ?? 'ID_DESC';
        $status = $request->filtro_status ?? 'TODOS';

        $query = Task::with(['client.addresses', 'images', 'logs', 'assignedUser', 'trackingHistory', 'products.product', 'products.deliveredBy'])
                    ->where('shop_id', $shop->id);

        // Búsqueda por título o descripción
        if (!empty($buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where('title', 'like', '%' . $buscar . '%')
                  ->orWhere('description', 'like', '%' . $buscar . '%')
                  ->orWhere('id', $buscar);
            });
        }

        // Filtro por estatus
        if (!empty($status) && $status !== 'TODOS') {
            $query->where('status', $status);
        }

        // Ordenamiento
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
                $query->orderBy('id', 'desc');
        }

        $tasks = $query->paginate(12);

        return response()->json($tasks);
    }

    /**
     * Obtener contadores por estatus
     */
    public function getNumStatus()
    {
        $user = auth()->user();
        $shop = $user->shop;

        $numNuevo = Task::where('shop_id', $shop->id)->where('status', 'NUEVO')->count();
        $numPendiente = Task::where('shop_id', $shop->id)->where('status', 'PENDIENTE')->count();
        $numAtendido = Task::where('shop_id', $shop->id)->where('status', 'ATENDIDO')->count();

        return response()->json([
            'numNuevo' => $numNuevo,
            'numPendiente' => $numPendiente,
            'numAtendido' => $numAtendido
        ]);
    }

    /**
     * Crear nueva tarea
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'required|integer|min:1|max:5',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $task = new Task();
        $task->shop_id = $shop->id;
        $task->client_id = $request->client_id;
        $task->active = 1;
        $task->status = 'NUEVO';
        $task->priority = $request->priority;
        $task->title = $request->title;
        $task->description = $request->description;
        $task->solution = $request->solution;

        if ($request->expiration) {
            $task->expiration = Carbon::parse($request->expiration)->format('Y-m-d');
        }

        $task->save();

        // Log de creación
        $this->storeTaskLog($task->id, $user->name, 'Creación del registro.');

        $task->load(['client.addresses', 'images', 'logs', 'assignedUser']);

        return response()->json([
            'ok' => true,
            'task' => $task,
            'message' => 'Tarea creada correctamente.'
        ]);
    }

    /**
     * Actualizar tarea existente
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:tasks,id',
            'title' => 'required|string|max:255',
            'priority' => 'required|integer|min:1|max:5',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($request->id);

        $task->client_id = $request->client_id;
        $task->priority = $request->priority;
        $task->title = $request->title;
        $task->description = $request->description;
        $task->solution = $request->solution;

        if ($request->expiration) {
            $task->expiration = Carbon::parse($request->expiration)->format('Y-m-d');
        }

        $task->save();

        // Log de edición
        $this->storeTaskLog($task->id, $user->name, 'Edición del registro.');

        $task->load(['client.addresses', 'images', 'logs', 'assignedUser', 'trackingHistory']);

        return response()->json([
            'ok' => true,
            'task' => $task,
            'message' => 'Tarea actualizada correctamente.'
        ]);
    }

    /**
     * Cambiar estatus de tarea
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:tasks,id',
            'status' => 'required|in:NUEVO,PENDIENTE,ATENDIDO',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($request->id);
        $oldStatus = $task->status;
        $task->status = $request->status;
        $task->save();

        // Log de cambio de estatus
        $this->storeTaskLog($task->id, $user->name, "Cambio de estatus: {$oldStatus} → {$request->status}");

        $task->load(['client.addresses', 'images', 'logs', 'assignedUser', 'trackingHistory']);

        return response()->json([
            'ok' => true,
            'task' => $task,
            'message' => 'Estatus actualizado correctamente.'
        ]);
    }

    /**
     * Agregar/actualizar reseña
     */
    public function updateReview(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:tasks,id',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($request->id);
        $task->review = $request->review;
        $task->save();

        // Log de reseña
        $this->storeTaskLog($task->id, $user->name, 'Reseña actualizada.');

        $task->load(['client.addresses', 'images', 'logs', 'assignedUser', 'trackingHistory']);

        return response()->json([
            'ok' => true,
            'task' => $task,
            'message' => 'Reseña guardada correctamente.'
        ]);
    }

    /**
     * Eliminar tarea
     */
    public function delete($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($id);

        // Eliminar imágenes asociadas
        if ($task->image) {
            Storage::disk('public')->delete($task->image);
        }

        foreach ($task->images as $img) {
            Storage::disk('public')->delete($img->image);
            $img->delete();
        }

        // Eliminar logs
        $task->logs()->delete();

        // Eliminar tarea
        $task->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Tarea eliminada correctamente.'
        ]);
    }

    /**
     * Obtener lista de colaboradores de la tienda
     * Usa role_id = 4 (collaborator) igual que el API
     */
    public function getCollaborators()
    {
        $user = auth()->user();
        $shop = $user->shop;

        try {
            $collaborators = User::where('shop_id', $shop->id)
                ->where('active', 1)
                ->whereHas('roles', function($query) {
                    $query->where('roles.id', 4); // role_id = 4 (collaborator)
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
    }

    /**
     * Asignar colaborador a tarea
     */
    public function assignUser(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($id);

        // Verificar que el colaborador pertenezca a la misma tienda
        $collaborator = User::where('shop_id', $shop->id)->findOrFail($request->user_id);

        $task->assigned_user_id = $collaborator->id;
        $task->save();

        // Log de asignación
        $this->storeTaskLog($task->id, $user->name, "Tarea asignada a: {$collaborator->name}");

        $task->load(['client.addresses', 'images', 'logs', 'assignedUser', 'trackingHistory']);

        return response()->json([
            'ok' => true,
            'task' => $task,
            'message' => 'Tarea asignada correctamente.'
        ]);
    }

    /**
     * Desasignar colaborador de tarea
     */
    public function unassignUser($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($id);

        $previousUser = $task->assignedUser ? $task->assignedUser->name : 'N/A';

        $task->assigned_user_id = null;
        $task->save();

        // Log de desasignación
        $this->storeTaskLog($task->id, $user->name, "Tarea desasignada (anteriormente: {$previousUser})");

        $task->load(['client.addresses', 'images', 'logs', 'assignedUser', 'trackingHistory']);

        return response()->json([
            'ok' => true,
            'task' => $task,
            'message' => 'Tarea desasignada correctamente.'
        ]);
    }

    /**
     * Activar tarea
     */
    public function activate($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($id);
        $task->active = 1;
        $task->save();

        $this->storeTaskLog($task->id, $user->name, 'Tarea activada.');

        $task->load(['client.addresses', 'images', 'logs', 'assignedUser', 'trackingHistory']);

        return response()->json([
            'ok' => true,
            'task' => $task,
            'message' => 'Tarea activada correctamente.'
        ]);
    }

    /**
     * Desactivar tarea
     */
    public function deactivate($id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($id);
        $task->active = 0;
        $task->save();

        $this->storeTaskLog($task->id, $user->name, 'Tarea desactivada.');

        $task->load(['client.addresses', 'images', 'logs', 'assignedUser', 'trackingHistory']);

        return response()->json([
            'ok' => true,
            'task' => $task,
            'message' => 'Tarea desactivada correctamente.'
        ]);
    }

    /**
     * Obtener clientes para select (búsqueda)
     */
    public function getClients(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $buscar = $request->q ?? '';

        $clients = \App\Models\Client::where('shop_id', $shop->id)
            ->where('active', 1)
            ->when($buscar, function($query) use ($buscar) {
                $query->where(function($q) use ($buscar) {
                    $q->where('name', 'like', "%{$buscar}%")
                      ->orWhere('email', 'like', "%{$buscar}%")
                      ->orWhere('company', 'like', "%{$buscar}%");
                });
            })
            ->select('id', 'name', 'email', 'company')
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'ok' => true,
            'clients' => $clients
        ]);
    }

    /**
     * Helper: Guardar log de tarea
     */
    private function storeTaskLog($taskId, $userName, $description)
    {
        TaskLog::create([
            'task_id' => $taskId,
            'user' => $userName,
            'description' => $description,
        ]);
    }

    // ==========================================
    // MÉTODOS PARA PRODUCTOS DE TAREA
    // ==========================================

    /**
     * Buscar productos para agregar a la tarea
     */
    public function getProducts(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $buscar = $request->q ?? '';

        $products = Product::where('shop_id', $shop->id)
            ->where('active', 1)
            ->when($buscar, function($query) use ($buscar) {
                $query->where(function($q) use ($buscar) {
                    $q->where('name', 'like', "%{$buscar}%")
                      ->orWhere('key', 'like', "%{$buscar}%")
                      ->orWhere('barcode', 'like', "%{$buscar}%");
                });
            })
            ->select('id', 'key', 'name', 'cost', 'retail', 'stock')
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'ok' => true,
            'products' => $products
        ]);
    }

    /**
     * Agregar producto a la tarea
     */
    public function addTaskProduct(Request $request, $taskId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty_delivered' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($taskId);
        $product = Product::where('shop_id', $shop->id)->findOrFail($request->product_id);

        // Verificar stock disponible
        if ($product->stock < $request->qty_delivered) {
            return response()->json([
                'ok' => false,
                'message' => "Stock insuficiente. Disponible: {$product->stock}"
            ], 422);
        }

        // Crear registro de producto en tarea
        $taskProduct = TaskProduct::create([
            'task_id' => $task->id,
            'product_id' => $product->id,
            'qty_delivered' => $request->qty_delivered,
            'qty_used' => 0,
            'qty_returned' => 0,
            'cost' => $product->cost,
            'price' => $product->retail,
            'notes' => $request->notes,
            'delivered_at' => now(),
            'delivered_by_user_id' => $user->id,
        ]);

        // Descontar del stock
        $product->stock -= $request->qty_delivered;
        $product->save();

        // Log
        $this->storeTaskLog($task->id, $user->name, "Producto entregado: {$product->name} x{$request->qty_delivered}");

        $taskProduct->load(['product', 'deliveredBy']);

        return response()->json([
            'ok' => true,
            'taskProduct' => $taskProduct,
            'message' => 'Producto agregado correctamente.'
        ]);
    }

    /**
     * Actualizar cantidades de producto en tarea (usado/devuelto)
     */
    public function updateTaskProduct(Request $request, $taskId, $taskProductId)
    {
        $request->validate([
            'qty_used' => 'required|integer|min:0',
            'qty_returned' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($taskId);
        $taskProduct = TaskProduct::where('task_id', $task->id)->findOrFail($taskProductId);

        // Verificar si ya fue facturado (bloqueado)
        if ($taskProduct->receipt_id) {
            return response()->json([
                'ok' => false,
                'message' => 'Este producto ya fue facturado en una nota de venta y no puede modificarse.'
            ], 422);
        }

        // Validar que qty_used + qty_returned <= qty_delivered
        $total = $request->qty_used + $request->qty_returned;
        if ($total > $taskProduct->qty_delivered) {
            return response()->json([
                'ok' => false,
                'message' => "La suma de usados ({$request->qty_used}) + devueltos ({$request->qty_returned}) no puede superar lo entregado ({$taskProduct->qty_delivered})."
            ], 422);
        }

        // Calcular diferencia de devolución para ajustar stock
        $previousReturned = $taskProduct->qty_returned;
        $newReturned = $request->qty_returned;
        $returnDiff = $newReturned - $previousReturned;

        // Actualizar registro
        $taskProduct->qty_used = $request->qty_used;
        $taskProduct->qty_returned = $request->qty_returned;
        if ($request->notes) {
            $taskProduct->notes = $request->notes;
        }
        if ($newReturned > 0 && !$taskProduct->returned_at) {
            $taskProduct->returned_at = now();
        }
        $taskProduct->save();

        // Ajustar stock si hay devolución
        if ($returnDiff != 0) {
            $product = Product::find($taskProduct->product_id);
            if ($product) {
                $product->stock += $returnDiff;
                $product->save();
            }
        }

        // Log
        $this->storeTaskLog($task->id, $user->name, "Producto actualizado: {$taskProduct->product->name} - Usados: {$request->qty_used}, Devueltos: {$request->qty_returned}");

        $taskProduct->load(['product', 'deliveredBy']);

        return response()->json([
            'ok' => true,
            'taskProduct' => $taskProduct,
            'message' => 'Producto actualizado correctamente.'
        ]);
    }

    /**
     * Eliminar producto de la tarea (devolver todo al stock)
     */
    public function removeTaskProduct($taskId, $taskProductId)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($taskId);
        $taskProduct = TaskProduct::where('task_id', $task->id)->findOrFail($taskProductId);

        // Verificar si ya fue facturado (bloqueado)
        if ($taskProduct->receipt_id) {
            return response()->json([
                'ok' => false,
                'message' => 'Este producto ya fue facturado y no puede eliminarse.'
            ], 422);
        }

        // Devolver al stock lo que no se haya usado
        $toReturn = $taskProduct->qty_delivered - $taskProduct->qty_used;
        if ($toReturn > 0) {
            $product = Product::find($taskProduct->product_id);
            if ($product) {
                $product->stock += $toReturn;
                $product->save();
            }
        }

        $productName = $taskProduct->product->name ?? 'Producto';

        // Log antes de eliminar
        $this->storeTaskLog($task->id, $user->name, "Producto removido: {$productName} (devueltos al stock: {$toReturn})");

        $taskProduct->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Producto removido correctamente.'
        ]);
    }

    /**
     * Obtener productos de una tarea específica
     */
    public function getTaskProducts($taskId)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($taskId);

        $products = TaskProduct::with(['product', 'deliveredBy'])
            ->where('task_id', $task->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'ok' => true,
            'products' => $products
        ]);
    }

    /**
     * Obtener productos USADOS de una tarea para generar nota de venta
     * Solo devuelve productos con qty_used > 0 y que NO han sido facturados
     */
    public function getUsedProductsForReceipt($taskId)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::with(['client', 'products.product'])
            ->where('shop_id', $shop->id)
            ->findOrFail($taskId);

        // Solo productos con qty_used > 0 Y sin facturar (receipt_id = null)
        $usedProducts = $task->products->filter(function($tp) {
            return $tp->qty_used > 0 && !$tp->receipt_id;
        })->map(function($tp) {
            return [
                'task_product_id' => $tp->id,
                'product_id' => $tp->product_id,
                'product' => $tp->product,
                'qty_used' => $tp->qty_used,
                'price' => $tp->price,
                'cost' => $tp->cost,
            ];
        })->values();

        if ($usedProducts->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay productos usados pendientes de facturar en esta tarea.'
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'client' => $task->client,
            ],
            'usedProducts' => $usedProducts
        ]);
    }

    /**
     * Obtener tareas que tienen productos usados pendientes de facturar
     * Para el selector en "Nueva Venta"
     */
    public function getTasksWithPendingProducts(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $buscar = $request->q ?? '';

        // Tareas que tienen al menos un producto con qty_used > 0 y sin facturar
        $tasks = Task::with(['client', 'products' => function($q) {
                $q->where('qty_used', '>', 0)->whereNull('receipt_id');
            }])
            ->where('shop_id', $shop->id)
            ->whereHas('products', function($q) {
                $q->where('qty_used', '>', 0)->whereNull('receipt_id');
            })
            ->when($buscar, function($query) use ($buscar) {
                $query->where(function($q) use ($buscar) {
                    $q->where('title', 'like', "%{$buscar}%")
                      ->orWhere('id', $buscar)
                      ->orWhereHas('client', function($cq) use ($buscar) {
                          $cq->where('name', 'like', "%{$buscar}%");
                      });
                });
            })
            ->orderBy('id', 'desc')
            ->take(20)
            ->get()
            ->map(function($task) {
                $pendingCount = $task->products->count();
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'client' => $task->client,
                    'pending_products_count' => $pendingCount,
                    'label' => "#{$task->id} - {$task->title}" . ($task->client ? " ({$task->client->name})" : '') . " - {$pendingCount} prod."
                ];
            });

        return response()->json([
            'ok' => true,
            'tasks' => $tasks
        ]);
    }

    // ==========================================
    // MÉTODOS PARA IMÁGENES DE TAREA
    // ==========================================

    /**
     * Subir imagen a una tarea
     * Primera imagen = principal (task.image)
     * Siguientes = alternativas (task_images)
     */
    public function uploadImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($id);

        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // Procesar y optimizar la imagen (redimensiona y comprime)
            $imageService = new ImageService();
            $imagePath = $imageService->processAndStore($image, 'tasks');

            // Si ya tiene imagen principal, guardar como alternativa
            if ($task->image) {
                TaskImage::create([
                    'task_id' => $task->id,
                    'image' => $imagePath
                ]);
                $this->storeTaskLog($task->id, $user->name, 'Imagen alternativa agregada.');
            } else {
                // Si no tiene imagen principal, guardarla ahí
                $task->image = $imagePath;
                $task->save();
                $this->storeTaskLog($task->id, $user->name, 'Imagen principal agregada.');
            }
        }

        $task->load(['client.addresses', 'images', 'logs', 'assignedUser', 'trackingHistory']);

        return response()->json([
            'ok' => true,
            'task' => $task,
            'message' => 'Imagen subida correctamente.'
        ]);
    }

    /**
     * Eliminar imagen principal de una tarea
     */
    public function deleteMainImage(Request $request, $id)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $task = Task::where('shop_id', $shop->id)->findOrFail($id);

        if ($task->image) {
            Storage::disk('public')->delete($task->image);
            $task->image = null;
            $task->save();

            $this->storeTaskLog($task->id, $user->name, 'Imagen principal eliminada.');
        }

        $task->load(['client.addresses', 'images', 'logs', 'assignedUser', 'trackingHistory']);

        return response()->json([
            'ok' => true,
            'task' => $task,
            'message' => 'Imagen principal eliminada.'
        ]);
    }

    /**
     * Eliminar imagen alternativa de una tarea
     */
    public function deleteAltImage(Request $request, $imageId)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $taskImage = TaskImage::findOrFail($imageId);
        $task = Task::where('shop_id', $shop->id)->findOrFail($taskImage->task_id);

        // Eliminar archivo
        Storage::disk('public')->delete($taskImage->image);

        // Eliminar registro
        $taskImage->delete();

        $this->storeTaskLog($task->id, $user->name, 'Imagen alternativa eliminada.');

        $task->load(['client.addresses', 'images', 'logs', 'assignedUser', 'trackingHistory']);

        return response()->json([
            'ok' => true,
            'task' => $task,
            'message' => 'Imagen alternativa eliminada.'
        ]);
    }
}
