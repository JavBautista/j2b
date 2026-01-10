<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\ProductStockAlert;
use App\Models\Product;
use Illuminate\Http\Request;

class StockAlertController extends Controller
{
    /**
     * Suscribirse a alerta de stock
     * POST /api/auth/app-client/stock-alert/subscribe
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró el cliente asociado'
            ], 400);
        }

        $product = Product::find($request->product_id);

        // Validar que el producto sea de la misma tienda
        if ($product->shop_id !== $client->shop_id) {
            return response()->json([
                'ok' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        // Validar que el producto esté sin stock
        if ($product->stock > 0) {
            return response()->json([
                'ok' => false,
                'message' => 'El producto ya tiene stock disponible'
            ], 400);
        }

        // Verificar si ya existe una alerta activa
        $existingAlert = ProductStockAlert::where('product_id', $product->id)
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->first();

        if ($existingAlert) {
            return response()->json([
                'ok' => true,
                'message' => 'Ya tienes una alerta activa para este producto',
                'alert' => $existingAlert
            ]);
        }

        // Crear nueva alerta
        $alert = ProductStockAlert::create([
            'product_id' => $product->id,
            'client_id' => $client->id,
            'user_id' => $user->id,
            'shop_id' => $client->shop_id,
            'status' => 'active',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Te notificaremos cuando el producto esté disponible',
            'alert' => $alert
        ]);
    }

    /**
     * Obtener mis alertas activas
     * GET /api/auth/app-client/stock-alert/my
     */
    public function myAlerts(Request $request)
    {
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró el cliente asociado'
            ], 400);
        }

        $alerts = ProductStockAlert::where('client_id', $client->id)
            ->where('status', 'active')
            ->with(['product:id,name,image,retail,stock'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'ok' => true,
            'alerts' => $alerts
        ]);
    }

    /**
     * Cancelar suscripción a alerta
     * POST /api/auth/app-client/stock-alert/unsubscribe
     */
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró el cliente asociado'
            ], 400);
        }

        $alert = ProductStockAlert::where('product_id', $request->product_id)
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->first();

        if (!$alert) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes una alerta activa para este producto'
            ], 404);
        }

        $alert->status = 'cancelled';
        $alert->save();

        return response()->json([
            'ok' => true,
            'message' => 'Alerta cancelada correctamente'
        ]);
    }

    /**
     * Verificar si tengo alerta activa para un producto
     * GET /api/auth/app-client/stock-alert/check/{product_id}
     */
    public function check(Request $request, $productId)
    {
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            return response()->json([
                'ok' => true,
                'has_alert' => false
            ]);
        }

        $hasAlert = ProductStockAlert::where('product_id', $productId)
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->exists();

        return response()->json([
            'ok' => true,
            'has_alert' => $hasAlert
        ]);
    }

    // ========================================
    // MÉTODOS PARA ADMIN
    // ========================================

    /**
     * Obtener alertas agrupadas por producto (para admin)
     * GET /api/auth/stock-alerts/grouped
     */
    public function getGroupedAlerts(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $alerts = ProductStockAlert::where('product_stock_alerts.shop_id', $shop->id)
            ->where('product_stock_alerts.status', 'active')
            ->join('products', 'product_stock_alerts.product_id', '=', 'products.id')
            ->selectRaw('
                product_stock_alerts.product_id,
                products.name as product_name,
                products.image as product_image,
                products.retail as product_retail,
                products.stock as product_stock,
                COUNT(product_stock_alerts.id) as total_requests,
                MIN(product_stock_alerts.created_at) as first_request,
                MAX(product_stock_alerts.created_at) as last_request
            ')
            ->groupBy('product_stock_alerts.product_id', 'products.name', 'products.image', 'products.retail', 'products.stock')
            ->orderByDesc('total_requests')
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $alerts
        ]);
    }

    /**
     * Obtener detalle de clientes que solicitaron un producto (para admin)
     * GET /api/auth/stock-alerts/product/{product_id}
     */
    public function getProductAlertDetails(Request $request, $productId)
    {
        $user = $request->user();
        $shop = $user->shop;

        $product = Product::where('id', $productId)
            ->where('shop_id', $shop->id)
            ->first();

        if (!$product) {
            return response()->json([
                'ok' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $alerts = ProductStockAlert::where('product_id', $productId)
            ->where('shop_id', $shop->id)
            ->where('status', 'active')
            ->with(['client:id,name,phone,email'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'ok' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'image' => $product->image,
                'retail' => $product->retail,
                'stock' => $product->stock
            ],
            'alerts' => $alerts
        ]);
    }
}
