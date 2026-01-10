<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionSetting;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    /**
     * Página de configuración de suscripciones
     */
    public function subscriptionSettings()
    {
        $settings = SubscriptionSetting::all();

        return view('superadmin.subscription_settings', compact('settings'));
    }

    /**
     * Actualizar configuración de suscripciones
     */
    public function updateSubscriptionSettings(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $key => $value) {
            SubscriptionSetting::set($key, $value);
        }

        return redirect()->back()->with('success', 'Configuración actualizada correctamente');
    }

    /**
     * Página de gestión de suscripciones de shops
     */
    public function subscriptionManagement()
    {
        $shops = Shop::with(['plan', 'owner'])
            ->orderBy('subscription_status')
            ->orderBy('id', 'desc')
            ->paginate(20);

        $plans = Plan::where('active', true)->get();

        return view('superadmin.subscription_management', compact('shops', 'plans'));
    }

    /**
     * Extender trial de un shop manualmente
     */
    public function extendTrial(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        if ($shop->is_trial) {
            $shop->update([
                'trial_ends_at' => $shop->trial_ends_at
                    ? $shop->trial_ends_at->addDays($request->days)
                    : now()->addDays($request->days),
            ]);
        } else {
            $shop->update([
                'subscription_ends_at' => $shop->subscription_ends_at
                    ? $shop->subscription_ends_at->addDays($request->days)
                    : now()->addDays($request->days),
            ]);
        }

        return redirect()->back()->with('success', "Suscripción de {$shop->name} extendida por {$request->days} días");
    }

    /**
     * Cambiar plan de un shop manualmente
     */
    public function changePlan(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'duration_months' => 'required|integer|min:1|max:12',
            'custom_price' => 'nullable|numeric|min:0',
        ]);

        $plan = Plan::findOrFail($request->plan_id);

        // Determinar precio a usar
        $useCustomPrice = $request->has('custom_price') && $request->custom_price > 0;

        if ($useCustomPrice) {
            // Usar precio personalizado (sin IVA)
            $priceWithoutIva = (float) $request->custom_price;
            $ivaPercentage = $plan->iva_percentage ?? 16;
            $ivaAmount = round($priceWithoutIva * ($ivaPercentage / 100), 2);
            $totalAmount = $priceWithoutIva + $ivaAmount;
        } else {
            // Usar precio del plan
            $priceWithoutIva = $plan->price_without_iva;
            $ivaAmount = $plan->price - $plan->price_without_iva;
            $totalAmount = $plan->price;
        }

        // Multiplicar por meses
        $priceWithoutIvaTotal = $priceWithoutIva * $request->duration_months;
        $ivaAmountTotal = $ivaAmount * $request->duration_months;
        $totalAmountFinal = $totalAmount * $request->duration_months;

        // Actualizar shop
        $shop->update([
            'plan_id' => $plan->id,
            'is_trial' => false,
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addMonths($request->duration_months),
            'last_payment_at' => now(),
            'active' => true,
        ]);

        // Registrar en historial de suscripciones
        Subscription::create([
            'shop_id' => $shop->id,
            'plan_id' => $plan->id,
            'user_id' => auth()->id(),
            'price_without_iva' => $priceWithoutIvaTotal,
            'iva_amount' => $ivaAmountTotal,
            'total_amount' => $totalAmountFinal,
            'currency' => $plan->currency,
            'payment_method' => 'other',
            'transaction_id' => 'MANUAL-' . now()->timestamp,
            'billing_period' => $request->duration_months == 1 ? 'monthly' : 'yearly',
            'starts_at' => now(),
            'ends_at' => now()->addMonths($request->duration_months),
            'status' => 'active',
            'admin_notes' => $useCustomPrice
                ? "Cambio manual por superadmin: " . auth()->user()->name . " - Precio personalizado: {$plan->currency} \${$totalAmount}/mes"
                : "Cambio manual por superadmin: " . auth()->user()->name,
        ]);

        // Crear notificación de pago recibido
        $this->createPaymentNotification($shop, $plan, $request->duration_months, $totalAmountFinal);

        $priceInfo = $useCustomPrice ? " (precio personalizado: {$plan->currency} \${$totalAmount}/mes)" : "";
        return redirect()->back()->with('success', "Plan de {$shop->name} cambiado a {$plan->name} por {$request->duration_months} meses{$priceInfo}");
    }

    /**
     * Crear notificación de pago recibido para todos los admins del shop
     */
    private function createPaymentNotification(Shop $shop, Plan $plan, int $months, float $totalAmount)
    {
        // Obtener admins del shop
        $admins = User::where('shop_id', $shop->id)
            ->whereHas('roles', function($query) {
                $query->whereIn('role_user.role_id', [1, 2]);
            })
            ->where('active', 1)
            ->get();

        $notificationGroupId = Notification::generateGroupId();

        foreach ($admins as $admin) {
            Notification::create([
                'notification_group_id' => $notificationGroupId,
                'user_id' => $admin->id,
                'type' => 'payment_received',
                'description' => '✅ Pago recibido - Suscripción activa',
                'action' => 'subscription',
                'data' => $shop->id,
                'read' => false,
                'visible' => true,
            ]);
        }
    }

    /**
     * Obtener información de suscripción de un shop (AJAX)
     */
    public function getSubscriptionInfo($id)
    {
        $shop = Shop::with(['plan', 'owner', 'subscriptions' => function($query) {
            $query->orderBy('created_at', 'desc')->limit(5);
        }])->findOrFail($id);

        return response()->json([
            'shop' => $shop,
            'days_remaining' => $shop->daysRemaining(),
            'is_active' => $shop->isActive(),
        ]);
    }

    /**
     * Asignar owner a un shop
     */
    public function assignOwner(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $request->validate([
            'owner_user_id' => 'required|exists:users,id',
        ]);

        // Verificar que el usuario pertenece a esta tienda
        $user = \App\Models\User::findOrFail($request->owner_user_id);
        if ($user->shop_id != $shop->id) {
            return redirect()->back()->with('error', 'El usuario seleccionado no pertenece a esta tienda');
        }

        // Verificar que el usuario tiene rol admin
        if (!$user->roles()->where('name', 'admin')->exists()) {
            return redirect()->back()->with('error', 'El usuario seleccionado debe tener rol Admin');
        }

        $shop->update([
            'owner_user_id' => $request->owner_user_id,
        ]);

        return redirect()->back()->with('success', "Owner asignado correctamente a {$shop->name}");
    }

    /**
     * Obtener usuarios admin de un shop (AJAX)
     */
    public function getShopUsers($id)
    {
        $shop = Shop::findOrFail($id);
        $users = \App\Models\User::where('shop_id', $shop->id)
            ->whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    /**
     * Desactivar/Reactivar shop (y todos sus usuarios)
     */
    public function toggleShopActive($id)
    {
        $shop = Shop::findOrFail($id);

        $newStatus = !$shop->active;

        // Actualizar shop
        $shop->update([
            'active' => $newStatus,
            'subscription_status' => $newStatus ? 'active' : 'cancelled',
        ]);

        // Desactivar/Reactivar TODOS los usuarios de esta tienda
        \App\Models\User::where('shop_id', $shop->id)
            ->update(['active' => $newStatus]);

        $action = $newStatus ? 'reactivada' : 'desactivada';
        $userCount = \App\Models\User::where('shop_id', $shop->id)->count();

        return redirect()->back()->with('success', "Tienda {$shop->name} {$action}. {$userCount} usuarios también fueron {$action}s.");
    }
}
