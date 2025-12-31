<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\Plan;
use App\Models\PlanFeature;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Obtener estado completo de la suscripción del shop
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $shop_id = $user->shop_id;

            if (!$shop_id) {
                return response()->json([
                    'error' => 'No shop associated',
                    'message' => 'Usuario sin tienda asociada'
                ], 403);
            }

            // Obtener shop con relación de plan
            $shop = Shop::with(['plan.features', 'owner'])
                        ->find($shop_id);

            if (!$shop) {
                return response()->json([
                    'error' => 'Shop not found',
                    'message' => 'Tienda no encontrada'
                ], 404);
            }

            // Obtener plan
            $plan = $shop->plan;

            if (!$plan) {
                return response()->json([
                    'error' => 'No plan assigned',
                    'message' => 'No hay plan asignado a esta tienda'
                ], 404);
            }

            // Obtener features del plan
            $features = $plan->features;

            // Calcular días restantes
            $daysRemaining = $this->calculateDaysRemaining($shop);

            // Verificar si está activo
            $isActive = $this->isShopActive($shop);

            return response()->json([
                'shop' => $shop,
                'plan' => $plan,
                'features' => $features,
                'days_remaining' => $daysRemaining,
                'is_active' => $isActive
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error en SubscriptionController::getStatus: ' . $e->getMessage());

            return response()->json([
                'error' => 'Server error',
                'message' => 'Error al obtener información de suscripción'
            ], 500);
        }
    }

    /**
     * Calcular días restantes de la suscripción
     *
     * @param Shop $shop
     * @return int
     */
    private function calculateDaysRemaining(Shop $shop)
    {
        $now = Carbon::now();

        // Si está en trial
        if ($shop->is_trial && $shop->trial_ends_at) {
            $endDate = Carbon::parse($shop->trial_ends_at);
            $days = $now->diffInDays($endDate, false);
            return max(0, (int)ceil($days));
        }

        // Si está en periodo de gracia
        if ($shop->subscription_status === 'grace_period' && $shop->grace_period_ends_at) {
            $endDate = Carbon::parse($shop->grace_period_ends_at);
            $days = $now->diffInDays($endDate, false);
            return max(0, (int)ceil($days));
        }

        // Si tiene suscripción activa
        if ($shop->subscription_ends_at) {
            $endDate = Carbon::parse($shop->subscription_ends_at);
            $days = $now->diffInDays($endDate, false);
            return max(0, (int)ceil($days));
        }

        return 0;
    }

    /**
     * Verificar si el shop está activo
     *
     * @param Shop $shop
     * @return bool
     */
    private function isShopActive(Shop $shop)
    {
        $now = Carbon::now();

        // Si está en trial y no ha vencido
        if ($shop->is_trial && $shop->trial_ends_at) {
            return Carbon::parse($shop->trial_ends_at)->isFuture();
        }

        // Si tiene suscripción activa y no ha vencido
        if ($shop->subscription_status === 'active' && $shop->subscription_ends_at) {
            return Carbon::parse($shop->subscription_ends_at)->isFuture();
        }

        // Si está en grace period
        if ($shop->subscription_status === 'grace_period' && $shop->grace_period_ends_at) {
            return Carbon::parse($shop->grace_period_ends_at)->isFuture();
        }

        return false;
    }
}
