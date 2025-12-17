<?php

namespace App\Services;

use App\Models\ProductStockAlert;
use App\Models\Product;
use App\Models\Notification;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class StockAlertService
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Procesa alertas cuando el stock de un producto aumenta
     * Llamar este método cuando stock pase de 0 a >0
     *
     * @param Product $product
     * @param int $previousStock
     * @param int $newStock
     * @return int Número de clientes notificados
     */
    public function processStockIncrease(Product $product, int $previousStock, int $newStock): int
    {
        // Solo notificar si el stock pasó de 0 a mayor que 0
        if ($previousStock > 0 || $newStock <= 0) {
            return 0;
        }

        Log::info("StockAlertService: Producto {$product->id} ({$product->name}) pasó de stock 0 a {$newStock}");

        // Buscar alertas activas para este producto
        $alerts = ProductStockAlert::active()
            ->forProduct($product->id)
            ->with(['client', 'user'])
            ->get();

        if ($alerts->isEmpty()) {
            Log::info("StockAlertService: No hay alertas activas para producto {$product->id}");
            return 0;
        }

        Log::info("StockAlertService: Encontradas {$alerts->count()} alertas activas para producto {$product->id}");

        $notifiedCount = 0;

        foreach ($alerts as $alert) {
            try {
                $this->notifyClient($alert, $product);
                $notifiedCount++;
            } catch (\Exception $e) {
                Log::error("StockAlertService: Error notificando alerta {$alert->id}: " . $e->getMessage());
            }
        }

        Log::info("StockAlertService: {$notifiedCount} clientes notificados para producto {$product->id}");

        return $notifiedCount;
    }

    /**
     * Notifica a un cliente específico
     */
    protected function notifyClient(ProductStockAlert $alert, Product $product): void
    {
        $title = '¡Producto disponible!';
        $message = "El producto '{$product->name}' ya está disponible.";

        // 1. Crear notificación in-app (si tiene user_id)
        if ($alert->user_id) {
            $notification = new Notification();
            $notification->user_id = $alert->user_id;
            $notification->description = $message;
            $notification->type = 'stock_alert';
            $notification->action = 'product_available';
            $notification->data = json_encode([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'stock' => $product->stock,
            ]);
            $notification->read = false;
            $notification->save();

            Log::info("StockAlertService: Notificación in-app creada para user {$alert->user_id}");

            // 2. Enviar push notification
            try {
                $this->firebaseService->sendToUser(
                    $alert->user_id,
                    $title,
                    $message,
                    [
                        'type' => 'stock_alert',
                        'product_id' => (string) $product->id,
                        'action' => 'open_product',
                    ]
                );
                Log::info("StockAlertService: Push enviado a user {$alert->user_id}");
            } catch (\Exception $e) {
                Log::warning("StockAlertService: No se pudo enviar push a user {$alert->user_id}: " . $e->getMessage());
            }
        }

        // 3. Marcar alerta como notificada
        $alert->status = 'notified';
        $alert->notified_at = now();
        $alert->save();

        Log::info("StockAlertService: Alerta {$alert->id} marcada como notificada");
    }

    /**
     * Obtener productos con alertas pendientes para una tienda (para admin)
     */
    public function getProductsWithPendingAlerts(int $shopId): array
    {
        $alerts = ProductStockAlert::active()
            ->forShop($shopId)
            ->with(['product:id,name,image,stock', 'client:id,name'])
            ->get()
            ->groupBy('product_id');

        $result = [];

        foreach ($alerts as $productId => $productAlerts) {
            $product = $productAlerts->first()->product;
            $result[] = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'product_image' => $product->image,
                'current_stock' => $product->stock,
                'clients_waiting' => $productAlerts->count(),
                'clients' => $productAlerts->map(fn($a) => [
                    'id' => $a->client_id,
                    'name' => $a->client->name,
                    'requested_at' => $a->created_at->format('Y-m-d H:i'),
                ])->toArray(),
            ];
        }

        // Ordenar por cantidad de clientes esperando (mayor primero)
        usort($result, fn($a, $b) => $b['clients_waiting'] <=> $a['clients_waiting']);

        return $result;
    }
}
