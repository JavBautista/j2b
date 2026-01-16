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
    public function subscriptionManagement(Request $request)
    {
        $query = Shop::with(['plan', 'owner']);

        // Filtro por búsqueda de nombre
        if ($request->filled('buscar')) {
            $query->where('name', 'like', '%' . $request->buscar . '%');
        }

        // Filtro por plan
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        // Filtro por estado de suscripción
        if ($request->filled('estado')) {
            $query->where('subscription_status', $request->estado);
        }

        // Filtro por activo/inactivo
        if ($request->filled('activo')) {
            $query->where('active', $request->activo);
        }

        $shops = $query->orderBy('subscription_status')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString(); // Mantener filtros en paginación

        $plans = Plan::where('active', true)->get();
        $basicPlan = Plan::find(2); // Plan BASIC para valores por defecto

        return view('superadmin.subscription_management', compact('shops', 'plans', 'basicPlan'));
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
            'include_iva' => 'nullable|boolean',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $durationMonths = (int) $request->duration_months;
        $includeIva = $request->boolean('include_iva');

        // Determinar precio a usar
        $useCustomPrice = $request->has('custom_price') && $request->custom_price > 0;

        if ($useCustomPrice) {
            // Usar precio personalizado
            $priceBase = (float) $request->custom_price;
            if ($includeIva) {
                $ivaPercentage = $plan->iva_percentage ?? 16;
                $ivaAmount = round($priceBase * ($ivaPercentage / 100), 2);
                $totalAmount = $priceBase + $ivaAmount;
            } else {
                $ivaAmount = 0;
                $totalAmount = $priceBase;
            }
            $priceWithoutIva = $priceBase;
        } else {
            // Usar precio del plan
            if ($includeIva) {
                $priceWithoutIva = $plan->price_without_iva ?? $plan->price;
                $ivaAmount = $plan->price - ($plan->price_without_iva ?? $plan->price);
                $totalAmount = $plan->price;
            } else {
                $priceWithoutIva = $plan->price;
                $ivaAmount = 0;
                $totalAmount = $plan->price;
            }
        }

        // Multiplicar por meses
        $priceWithoutIvaTotal = $priceWithoutIva * $durationMonths;
        $ivaAmountTotal = $ivaAmount * $durationMonths;
        $totalAmountFinal = $totalAmount * $durationMonths;

        // Actualizar shop
        $shop->update([
            'plan_id' => $plan->id,
            'monthly_price' => $totalAmount, // Guardar precio mensual de esta tienda
            'is_trial' => false,
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addMonths($durationMonths),
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
            'billing_period' => $durationMonths == 1 ? 'monthly' : 'yearly',
            'starts_at' => now(),
            'ends_at' => now()->addMonths($durationMonths),
            'status' => 'active',
            'admin_notes' => $useCustomPrice
                ? "Cambio manual por superadmin: " . auth()->user()->name . " - Precio personalizado: {$plan->currency} \${$totalAmount}/mes" . ($includeIva ? ' +IVA' : ' sin IVA')
                : "Cambio manual por superadmin: " . auth()->user()->name . ($includeIva ? ' +IVA' : ' sin IVA'),
        ]);

        // Crear notificación de pago recibido
        $this->createPaymentNotification($shop, $plan, $durationMonths, $totalAmountFinal);

        $priceInfo = $useCustomPrice ? " (precio personalizado: {$plan->currency} \${$totalAmount}/mes)" : "";
        return redirect()->back()->with('success', "Plan de {$shop->name} cambiado a {$plan->name} por {$durationMonths} meses{$priceInfo}");
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
     * Asignar admin principal a un shop
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

        // Verificar que el usuario no es limitado (debe ser admin full)
        if ($user->limited) {
            return redirect()->back()->with('error', 'El usuario seleccionado es limitado. Solo usuarios Admin full pueden ser asignados.');
        }

        $shop->update([
            'owner_user_id' => $request->owner_user_id,
        ]);

        return redirect()->back()->with('success', "Admin principal asignado correctamente a {$shop->name}");
    }

    /**
     * Obtener usuarios admin full (no limitados) de un shop (AJAX)
     */
    public function getShopUsers($id)
    {
        $shop = Shop::findOrFail($id);
        $users = \App\Models\User::where('shop_id', $shop->id)
            ->where('limited', 0) // Solo usuarios full, no limitados
            ->whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    /**
     * Desactivar/Reactivar shop
     * - Desactivar: desactiva tienda + TODOS los usuarios
     * - Reactivar: activa tienda + SOLO el owner
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

        if ($newStatus) {
            // REACTIVAR: Solo activar el owner de la tienda
            if ($shop->owner_user_id) {
                \App\Models\User::where('id', $shop->owner_user_id)
                    ->update(['active' => true]);

                $ownerName = $shop->owner ? $shop->owner->name : 'Owner';
                return redirect()->back()->with('success', "Tienda {$shop->name} reactivada. Usuario owner ({$ownerName}) reactivado. Los demás usuarios deben reactivarse manualmente.");
            } else {
                return redirect()->back()->with('success', "Tienda {$shop->name} reactivada. No tiene owner asignado, debes reactivar usuarios manualmente.");
            }
        } else {
            // DESACTIVAR: Desactivar TODOS los usuarios de esta tienda
            $userCount = \App\Models\User::where('shop_id', $shop->id)->count();
            \App\Models\User::where('shop_id', $shop->id)
                ->update(['active' => false]);

            return redirect()->back()->with('success', "Tienda {$shop->name} desactivada. {$userCount} usuarios fueron desactivados.");
        }
    }

    /**
     * Actualizar configuración personalizada de una tienda
     */
    public function updateShopConfig(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $request->validate([
            'monthly_price' => 'required|numeric|min:0',
            'trial_days' => 'required|integer|min:0|max:365',
            'grace_period_days' => 'required|integer|min:0|max:30',
        ]);

        $shop->update([
            'monthly_price' => $request->monthly_price,
            'trial_days' => $request->trial_days,
            'grace_period_days' => $request->grace_period_days,
        ]);

        return redirect()->back()->with('success', "Configuración de {$shop->name} actualizada correctamente.");
    }

    // ============================================
    // MÉTODOS JSON PARA VUE.JS
    // ============================================

    /**
     * Obtener lista de tiendas paginada (JSON para Vue)
     */
    public function get(Request $request)
    {
        $query = Shop::with(['plan', 'owner']);

        if ($request->filled('buscar')) {
            $query->where('name', 'like', '%' . $request->buscar . '%');
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        if ($request->filled('estado')) {
            $query->where('subscription_status', $request->estado);
        }

        if ($request->filled('activo')) {
            $query->where('active', $request->activo);
        }

        $shops = $query->orderBy('subscription_status')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return response()->json([
            'shops' => $shops->items(),
            'pagination' => [
                'total' => $shops->total(),
                'current_page' => $shops->currentPage(),
                'per_page' => $shops->perPage(),
                'last_page' => $shops->lastPage(),
                'from' => $shops->firstItem(),
                'to' => $shops->lastItem(),
            ]
        ]);
    }

    /**
     * Obtener contadores de estado (JSON para Vue)
     */
    public function getNumStatus()
    {
        return response()->json([
            'trial' => Shop::where('subscription_status', 'trial')->count(),
            'active' => Shop::where('subscription_status', 'active')->count(),
            'grace_period' => Shop::where('subscription_status', 'grace_period')->count(),
            'expired' => Shop::where('subscription_status', 'expired')->count(),
        ]);
    }

    /**
     * Obtener lista de planes activos (JSON para Vue)
     */
    public function getPlans()
    {
        return response()->json(Plan::where('active', true)->get());
    }

    /**
     * Obtener estadísticas de una tienda (JSON para Vue)
     */
    public function getShopStats($id)
    {
        $shop = Shop::findOrFail($id);

        // Usuarios por rol
        $users = User::where('shop_id', $shop->id)->with('roles')->get();
        $admins_full = $users->filter(fn($u) => strtolower($u->roles->first()?->name ?? '') === 'admin' && !$u->limited)->count();
        $admins_limitados = $users->filter(fn($u) => strtolower($u->roles->first()?->name ?? '') === 'admin' && $u->limited)->count();
        $colaboradores = $users->filter(fn($u) => strtolower($u->roles->first()?->name ?? '') === 'colaborador')->count();
        $clientes_user = $users->filter(fn($u) => strtolower($u->roles->first()?->name ?? '') === 'cliente')->count();
        $users_activos = $users->where('active', 1)->count();
        $users_inactivos = $users->where('active', 0)->count();

        // Clientes registrados
        $clientes_total = $shop->clients()->count();
        $clientes_activos = $shop->clients()->where('active', 1)->count();

        // Ventas
        $ventas_total = $shop->receipts()->count();
        $ventas_30dias = $shop->receipts()->where('created_at', '>=', now()->subDays(30))->count();
        $ultima_venta = $shop->receipts()->orderBy('created_at', 'desc')->first();

        // Tareas
        $tareas_total = $shop->tasks()->count();
        $tareas_pendientes = $shop->tasks()->where('status', 'PENDIENTE')->count();
        $tareas_30dias = $shop->tasks()->where('created_at', '>=', now()->subDays(30))->count();

        // Nivel de actividad
        $score = 0;
        if ($clientes_total > 0) $score++;
        if ($ventas_total > 0) $score++;
        if ($ventas_30dias > 0) $score += 2;
        if ($tareas_total > 0) $score++;

        $nivel = 'sin_actividad';
        if ($score >= 4) $nivel = 'alta';
        elseif ($score >= 2) $nivel = 'media';
        elseif ($score >= 1) $nivel = 'baja';

        return response()->json([
            'usuarios' => [
                'total' => $users->count(),
                'admins_full' => $admins_full,
                'admins_limitados' => $admins_limitados,
                'colaboradores' => $colaboradores,
                'clientes' => $clientes_user,
                'activos' => $users_activos,
                'inactivos' => $users_inactivos,
            ],
            'clientes' => [
                'total' => $clientes_total,
                'activos' => $clientes_activos,
            ],
            'ventas' => [
                'total' => $ventas_total,
                'ultimos_30_dias' => $ventas_30dias,
                'ultima_venta' => $ultima_venta ? $ultima_venta->created_at->format('d/m/Y H:i') : null,
            ],
            'tareas' => [
                'total' => $tareas_total,
                'pendientes' => $tareas_pendientes,
                'ultimos_30_dias' => $tareas_30dias,
            ],
            'nivel_actividad' => $nivel,
        ]);
    }

    /**
     * Extender suscripción (JSON para Vue)
     */
    public function extendTrialJson(Request $request, $id)
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

        return response()->json([
            'success' => true,
            'message' => "Suscripción de {$shop->name} extendida por {$request->days} días"
        ]);
    }

    /**
     * Cambiar plan (JSON para Vue)
     */
    public function changePlanJson(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'duration_months' => 'required|integer|min:1|max:12',
            'custom_price' => 'nullable|numeric|min:0',
            'include_iva' => 'nullable|boolean',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $durationMonths = (int) $request->duration_months;
        $includeIva = $request->boolean('include_iva');

        $useCustomPrice = $request->filled('custom_price') && $request->custom_price > 0;

        if ($useCustomPrice) {
            $priceBase = (float) $request->custom_price;
            if ($includeIva) {
                $ivaPercentage = $plan->iva_percentage ?? 16;
                $ivaAmount = round($priceBase * ($ivaPercentage / 100), 2);
                $totalAmount = $priceBase + $ivaAmount;
            } else {
                $ivaAmount = 0;
                $totalAmount = $priceBase;
            }
            $priceWithoutIva = $priceBase;
        } else {
            if ($includeIva) {
                $priceWithoutIva = $plan->price_without_iva ?? $plan->price;
                $ivaAmount = $plan->price - ($plan->price_without_iva ?? $plan->price);
                $totalAmount = $plan->price;
            } else {
                $priceWithoutIva = $plan->price;
                $ivaAmount = 0;
                $totalAmount = $plan->price;
            }
        }

        $priceWithoutIvaTotal = $priceWithoutIva * $durationMonths;
        $ivaAmountTotal = $ivaAmount * $durationMonths;
        $totalAmountFinal = $totalAmount * $durationMonths;

        $shop->update([
            'plan_id' => $plan->id,
            'monthly_price' => $totalAmount,
            'is_trial' => false,
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addMonths($durationMonths),
            'last_payment_at' => now(),
            'active' => true,
        ]);

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
            'billing_period' => $durationMonths == 1 ? 'monthly' : 'yearly',
            'starts_at' => now(),
            'ends_at' => now()->addMonths($durationMonths),
            'status' => 'active',
            'admin_notes' => $useCustomPrice
                ? "Cambio manual por superadmin: " . auth()->user()->name . " - Precio personalizado: {$plan->currency} \${$totalAmount}/mes" . ($includeIva ? ' +IVA' : ' sin IVA')
                : "Cambio manual por superadmin: " . auth()->user()->name . ($includeIva ? ' +IVA' : ' sin IVA'),
        ]);

        $this->createPaymentNotification($shop, $plan, $durationMonths, $totalAmountFinal);

        $priceInfo = $useCustomPrice ? " (precio personalizado: {$plan->currency} \${$totalAmount}/mes)" : "";
        return response()->json([
            'success' => true,
            'message' => "Plan de {$shop->name} cambiado a {$plan->name} por {$durationMonths} meses{$priceInfo}"
        ]);
    }

    /**
     * Toggle activar/desactivar tienda (JSON para Vue)
     */
    public function toggleShopActiveJson($id)
    {
        $shop = Shop::findOrFail($id);

        $newStatus = !$shop->active;

        $shop->update([
            'active' => $newStatus,
            'subscription_status' => $newStatus ? 'active' : 'cancelled',
        ]);

        if ($newStatus) {
            if ($shop->owner_user_id) {
                User::where('id', $shop->owner_user_id)->update(['active' => true]);
                $ownerName = $shop->owner ? $shop->owner->name : 'Owner';
                return response()->json([
                    'success' => true,
                    'message' => "Tienda {$shop->name} reactivada. Usuario owner ({$ownerName}) reactivado."
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => "Tienda {$shop->name} reactivada. No tiene owner, reactiva usuarios manualmente."
                ]);
            }
        } else {
            $userCount = User::where('shop_id', $shop->id)->count();
            User::where('shop_id', $shop->id)->update(['active' => false]);

            return response()->json([
                'success' => true,
                'message' => "Tienda {$shop->name} desactivada. {$userCount} usuarios desactivados."
            ]);
        }
    }

    /**
     * Actualizar configuración de tienda (JSON para Vue)
     */
    public function updateShopConfigJson(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $request->validate([
            'monthly_price' => 'required|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'trial_days' => 'required|integer|min:0|max:365',
            'grace_period_days' => 'required|integer|min:0|max:30',
        ]);

        $shop->update([
            'monthly_price' => $request->monthly_price,
            'yearly_price' => $request->yearly_price,
            'trial_days' => $request->trial_days,
            'grace_period_days' => $request->grace_period_days,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Configuración de {$shop->name} actualizada correctamente."
        ]);
    }

    /**
     * Asignar admin principal (JSON para Vue)
     */
    public function assignOwnerJson(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $request->validate([
            'owner_user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->owner_user_id);

        if ($user->shop_id != $shop->id) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario seleccionado no pertenece a esta tienda'
            ], 422);
        }

        if (!$user->roles()->where('name', 'admin')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario seleccionado debe tener rol Admin'
            ], 422);
        }

        if ($user->limited) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario seleccionado es limitado. Solo usuarios Admin full pueden ser asignados.'
            ], 422);
        }

        $shop->update([
            'owner_user_id' => $request->owner_user_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Admin principal ({$user->name}) asignado correctamente a {$shop->name}"
        ]);
    }

    /**
     * Registrar pago de suscripcion (JSON para Vue)
     * Accion principal: cuando un cliente paga su mensualidad/anualidad
     */
    public function registerPaymentJson(Request $request, $id)
    {
        $shop = Shop::with('plan')->findOrFail($id);

        $request->validate([
            'billing_cycle' => 'required|in:monthly,yearly',
            'amount' => 'required|numeric|min:0',
            'include_iva' => 'nullable|boolean',
            'payment_method' => 'required|in:transfer,cash,card,other',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $billingCycle = $request->billing_cycle;
        $amount = (float) $request->amount;
        $includeIva = $request->boolean('include_iva');
        $paymentMethod = $request->payment_method;

        // Calcular IVA si aplica
        $ivaRate = SubscriptionSetting::get('iva_rate', 16);
        if ($includeIva) {
            $priceWithoutIva = round($amount / (1 + ($ivaRate / 100)), 2);
            $ivaAmount = round($amount - $priceWithoutIva, 2);
            $totalAmount = $amount;
        } else {
            $priceWithoutIva = $amount;
            $ivaAmount = 0;
            $totalAmount = $amount;
        }

        // Calcular fecha de vencimiento segun ciclo
        // Si tiene fecha de corte, sumar desde ella (mantiene fecha fija)
        // Esto aplica aunque esté en gracia (pagó tarde pero su fecha no cambia)
        // Solo si NO tiene fecha de corte (primer pago) → sumar desde HOY
        $baseDate = $shop->subscription_ends_at
            ? $shop->subscription_ends_at
            : now();

        $startsAt = now();
        if ($billingCycle === 'yearly') {
            $endsAt = $baseDate->copy()->addYear();
            $periodLabel = '12 meses';
        } else {
            $endsAt = $baseDate->copy()->addDays(30);
            $periodLabel = '1 mes';
        }

        // Crear registro de pago en subscriptions
        $subscription = Subscription::create([
            'shop_id' => $shop->id,
            'plan_id' => $shop->plan_id,
            'user_id' => auth()->id(),
            'price_without_iva' => $priceWithoutIva,
            'iva_amount' => $ivaAmount,
            'total_amount' => $totalAmount,
            'currency' => $shop->plan->currency ?? 'MXN',
            'payment_method' => $paymentMethod,
            'transaction_id' => $request->reference ?: 'MANUAL-' . now()->timestamp,
            'billing_period' => $billingCycle,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'active',
            'admin_notes' => $request->notes ?: "Pago registrado por: " . auth()->user()->name,
        ]);

        // Actualizar tienda
        $shop->update([
            'subscription_status' => 'active',
            'subscription_ends_at' => $endsAt,
            'billing_cycle' => $billingCycle,
            'is_trial' => false,
            'last_payment_at' => now(),
            'active' => true,
        ]);

        // Reactivar owner si la tienda estaba desactivada
        if ($shop->owner_user_id) {
            User::where('id', $shop->owner_user_id)->update(['active' => true]);
        }

        // Crear notificacion de pago recibido
        $this->createPaymentNotification($shop, $shop->plan, $billingCycle === 'yearly' ? 12 : 1, $totalAmount);

        $currency = $shop->plan->currency ?? 'MXN';
        return response()->json([
            'success' => true,
            'message' => "Pago de {$currency} \${$totalAmount} registrado para {$shop->name}. Suscripcion activa por {$periodLabel} hasta " . $endsAt->format('d/m/Y')
        ]);
    }

    /**
     * Obtener historial de pagos de una tienda (JSON para Vue)
     */
    public function getPaymentHistoryJson($id)
    {
        $shop = Shop::findOrFail($id);

        $payments = Subscription::where('shop_id', $id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'date' => $payment->created_at->format('d/m/Y H:i'),
                    'billing_period' => $payment->billing_period,
                    'total_amount' => $payment->total_amount,
                    'currency' => $payment->currency,
                    'payment_method' => $payment->payment_method,
                    'status' => $payment->status,
                    'registered_by' => $payment->user?->name ?? 'Sistema',
                    'notes' => $payment->admin_notes,
                    'starts_at' => $payment->starts_at?->format('d/m/Y'),
                    'ends_at' => $payment->ends_at?->format('d/m/Y'),
                ];
            });

        $totalPaid = Subscription::where('shop_id', $id)
            ->where('status', 'active')
            ->sum('total_amount');

        return response()->json([
            'success' => true,
            'shop_name' => $shop->name,
            'payments' => $payments,
            'total_payments' => $payments->count(),
            'total_paid' => $totalPaid,
        ]);
    }
}
