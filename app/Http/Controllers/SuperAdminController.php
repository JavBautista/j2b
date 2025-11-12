<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionSetting;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\Subscription;
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
        ]);

        $plan = Plan::findOrFail($request->plan_id);

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
            'price_without_iva' => $plan->price_without_iva * $request->duration_months,
            'iva_amount' => ($plan->price - $plan->price_without_iva) * $request->duration_months,
            'total_amount' => $plan->price * $request->duration_months,
            'currency' => $plan->currency,
            'payment_method' => 'other',
            'transaction_id' => 'MANUAL-' . now()->timestamp,
            'billing_period' => $request->duration_months == 1 ? 'monthly' : 'yearly',
            'starts_at' => now(),
            'ends_at' => now()->addMonths($request->duration_months),
            'status' => 'active',
            'admin_notes' => 'Cambio manual por superadmin: ' . auth()->user()->name,
        ]);

        return redirect()->back()->with('success', "Plan de {$shop->name} cambiado a {$plan->name} por {$request->duration_months} meses");
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
}
