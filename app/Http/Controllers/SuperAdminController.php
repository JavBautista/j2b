<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionSetting;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

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
            'billing_cycle' => 'nullable|in:monthly,yearly',
            'cutoff' => 'nullable|integer|min:1|max:31',
            'recalcular_fecha' => 'nullable|boolean',
            'monthly_price' => 'nullable|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $cambios = [];

        // Actualizar plan si cambió
        if ($shop->plan_id != $plan->id) {
            $shop->plan_id = $plan->id;
            $cambios[] = "Plan: {$plan->name}";
        }

        // Actualizar precios (usar los del request, o mantener los actuales)
        $newMonthlyPrice = $request->monthly_price ?? $shop->monthly_price ?? $plan->price;
        $newYearlyPrice = $request->yearly_price ?? $shop->yearly_price ?? $plan->yearly_price;

        if ($shop->monthly_price != $newMonthlyPrice) {
            $shop->monthly_price = $newMonthlyPrice;
            $cambios[] = "Precio mensual: $" . number_format($newMonthlyPrice, 2);
        }
        if ($shop->yearly_price != $newYearlyPrice) {
            $shop->yearly_price = $newYearlyPrice;
            $cambios[] = "Precio anual: $" . number_format($newYearlyPrice, 2);
        }

        // Actualizar ciclo de facturación si cambió
        $billingCycle = $request->billing_cycle ?? $shop->billing_cycle ?? 'monthly';
        if ($shop->billing_cycle != $billingCycle) {
            $shop->billing_cycle = $billingCycle;
            $cicloLabel = $billingCycle === 'yearly' ? 'Anual' : 'Mensual';
            $cambios[] = "Ciclo: {$cicloLabel}";
        }

        // Actualizar día de corte si se proporcionó
        $cutoff = $request->cutoff ?? $shop->cutoff;
        if ($request->filled('cutoff') && $shop->cutoff != $request->cutoff) {
            $shop->cutoff = $request->cutoff;
            $cutoff = $request->cutoff;
            $cambios[] = "Día de corte: {$request->cutoff}";
        }

        // Recalcular fecha de vencimiento si se solicitó
        if ($request->boolean('recalcular_fecha') && $cutoff) {
            // Calcular próxima fecha de vencimiento basada en el cutoff
            if ($billingCycle === 'yearly') {
                $nuevaFecha = now()->addYear()->day($cutoff);
            } else {
                $nuevaFecha = now()->addMonth()->day($cutoff);
            }

            // Ajustar si el día no existe en el mes (ej: 31 en febrero)
            if ($nuevaFecha->day != $cutoff) {
                $nuevaFecha = $nuevaFecha->endOfMonth();
            }

            $shop->subscription_ends_at = $nuevaFecha;
            $shop->subscription_status = 'active';
            $shop->is_trial = false;
            $cambios[] = "Fecha recalculada: " . $nuevaFecha->format('d/m/Y');
        }

        // Guardar cambios
        $shop->save();

        $mensaje = count($cambios) > 0
            ? "Tienda {$shop->name} actualizada: " . implode(', ', $cambios)
            : "No se realizaron cambios";

        return response()->json([
            'success' => true,
            'message' => $mensaje
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
     * Toggle tienda exenta (no paga suscripción)
     * Las tiendas exentas son ignoradas por el cron de vencimientos
     */
    public function toggleExemptJson($id)
    {
        $shop = Shop::findOrFail($id);

        $newStatus = !$shop->is_exempt;

        $shop->update([
            'is_exempt' => $newStatus,
        ]);

        $accion = $newStatus ? 'marcada como exenta' : 'removida de exentas';

        return response()->json([
            'success' => true,
            'message' => "Tienda {$shop->name} {$accion}."
        ]);
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
            'payment_date' => 'nullable|date',
        ]);

        $billingCycle = $request->billing_cycle;
        $amount = (float) $request->amount;
        $includeIva = $request->boolean('include_iva');
        $paymentMethod = $request->payment_method;

        // Fecha del pago (puede ser distinta a hoy si el cliente pagó antes)
        $paymentDate = $request->filled('payment_date')
            ? \Carbon\Carbon::parse($request->payment_date)
            : now();

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

        // Determinar día de corte (cutoff)
        // REGLA: El cutoff se RECALCULA solo en estos casos:
        //   1. Primer pago (tienda no tiene cutoff)
        //   2. Reactivación (tienda estaba bloqueada/expired)
        // En pagos normales (antes, el día, o en gracia), el cutoff NO cambia
        $isReactivation = $shop->subscription_status === 'expired';
        $isFirstPayment = !$shop->cutoff;

        if ($isReactivation || $isFirstPayment) {
            // Reactivación o primer pago: el día del pago se vuelve el nuevo cutoff
            $cutoff = $paymentDate->day;
        } else {
            // Pago normal: mantener cutoff existente
            $cutoff = $shop->cutoff;
        }

        // =====================================================
        // CALCULAR EL PERÍODO QUE CORRESPONDE A ESTE PAGO
        // =====================================================
        // El período se calcula basándose en:
        // - Primer pago/Reactivación: desde la fecha del pago
        // - Pago normal: desde el subscription_ends_at actual (la fecha de corte)

        if ($isReactivation || $isFirstPayment) {
            // Primer pago o reactivación: período empieza desde el día del pago
            $periodStart = $paymentDate->copy()->startOfDay();
        } else {
            // Pago normal: el período empieza desde el subscription_ends_at
            // (que es la fecha de corte del período actual)
            $periodStart = $shop->subscription_ends_at
                ? $shop->subscription_ends_at->copy()->startOfDay()
                : $paymentDate->copy()->startOfDay();
        }

        // Calcular fin del período (period_end = ends_at)
        if ($billingCycle === 'yearly') {
            $periodEnd = $periodStart->copy()->addYear();
            $periodLabel = '12 meses';
        } else {
            $periodEnd = $periodStart->copy()->addMonth();
            $periodLabel = '1 mes';
        }

        // Ajustar al día de corte
        if ($periodEnd->day != $cutoff) {
            try {
                $periodEnd->day($cutoff);
            } catch (\Exception $e) {
                // Si el día no existe en el mes (ej: 31 en febrero), usar fin de mes
                $periodEnd = $periodEnd->endOfMonth();
            }
        }

        // =====================================================
        // VALIDAR QUE NO EXISTA PAGO DUPLICADO PARA ESTE PERÍODO
        // =====================================================
        $existingPayment = Subscription::where('shop_id', $shop->id)
            ->where('status', 'active')
            ->where('ends_at', '>=', $periodEnd)
            ->first();

        if ($existingPayment && !$isReactivation && !$isFirstPayment) {
            // Ya existe un pago que cubre este período o más allá
            return response()->json([
                'success' => false,
                'message' => "Ya existe un pago vigente hasta {$existingPayment->ends_at->format('d/m/Y')}. " .
                    "El período {$periodStart->format('d/m/Y')} - {$periodEnd->format('d/m/Y')} ya está cubierto. " .
                    "Si necesitas registrar un pago adelantado, primero debe vencer el período actual."
            ], 422);
        }

        // Variables para guardar en el registro
        $startsAt = $paymentDate->copy(); // Fecha real del pago (informativo)
        $endsAt = $periodEnd;              // Fin del período (vencimiento)

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
            'cutoff' => $cutoff, // Guardar el día de corte
            'is_trial' => false,
            'last_payment_at' => $paymentDate,
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
            'message' => "Pago de {$currency} \${$totalAmount} registrado para {$shop->name}. " .
                "Período: {$periodStart->format('d/m/Y')} - {$endsAt->format('d/m/Y')} ({$periodLabel})",
            'period' => [
                'start' => $periodStart->format('d/m/Y'),
                'end' => $endsAt->format('d/m/Y'),
                'label' => $periodLabel
            ]
        ]);
    }

    /**
     * Obtener información del próximo período a pagar
     * Útil para mostrar en el modal antes de registrar el pago
     */
    public function getNextPeriodInfo(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);
        $billingCycle = $request->get('billing_cycle', $shop->billing_cycle ?? 'monthly');

        $isReactivation = $shop->subscription_status === 'expired';
        $isFirstPayment = !$shop->cutoff;

        // Calcular período
        if ($isReactivation || $isFirstPayment) {
            $periodStart = now()->startOfDay();
            $cutoff = now()->day;
            $periodType = $isFirstPayment ? 'primer_pago' : 'reactivacion';
        } else {
            $periodStart = $shop->subscription_ends_at
                ? $shop->subscription_ends_at->copy()->startOfDay()
                : now()->startOfDay();
            $cutoff = $shop->cutoff;
            $periodType = 'renovacion';
        }

        if ($billingCycle === 'yearly') {
            $periodEnd = $periodStart->copy()->addYear();
            $periodLabel = '12 meses';
        } else {
            $periodEnd = $periodStart->copy()->addMonth();
            $periodLabel = '1 mes';
        }

        // Ajustar al día de corte
        if ($periodEnd->day != $cutoff) {
            try {
                $periodEnd->day($cutoff);
            } catch (\Exception $e) {
                $periodEnd = $periodEnd->endOfMonth();
            }
        }

        // Verificar si ya existe pago para este período
        $existingPayment = Subscription::where('shop_id', $shop->id)
            ->where('status', 'active')
            ->where('ends_at', '>=', $periodEnd)
            ->first();

        return response()->json([
            'success' => true,
            'period' => [
                'type' => $periodType,
                'start' => $periodStart->format('d/m/Y'),
                'end' => $periodEnd->format('d/m/Y'),
                'label' => $periodLabel,
                'cutoff' => $cutoff,
            ],
            'already_paid' => $existingPayment !== null,
            'existing_payment' => $existingPayment ? [
                'id' => $existingPayment->id,
                'ends_at' => $existingPayment->ends_at->format('d/m/Y'),
            ] : null,
            'shop_status' => $shop->subscription_status,
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
                    'date' => $payment->starts_at?->format('d/m/Y') ?? $payment->created_at->format('d/m/Y'),
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

    /**
     * =============================================
     * PÁGINA DEDICADA DE PAGOS POR TIENDA
     * =============================================
     */

    /**
     * Vista principal de pagos de una tienda
     */
    public function shopPaymentsPage($id)
    {
        $shop = Shop::with('plan')->findOrFail($id);

        return view('superadmin.shop_payments', [
            'shopId' => $shop->id,
            'shopName' => $shop->name,
        ]);
    }

    /**
     * Obtener lista de pagos paginada (para Vue)
     */
    public function getShopPayments(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $query = Subscription::where('shop_id', $id)
            ->with(['user:id,name', 'plan:id,name']);

        // Filtros
        if ($request->filled('billing_period')) {
            $query->where('billing_period', $request->billing_period);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('starts_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('starts_at', '<=', $request->date_to);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $payments = $query->paginate(15);

        // Estadísticas
        $stats = [
            'total_payments' => Subscription::where('shop_id', $id)->count(),
            'total_paid' => Subscription::where('shop_id', $id)->sum('total_amount'),
            'last_payment' => Subscription::where('shop_id', $id)->latest()->first()?->created_at?->format('d/m/Y'),
        ];

        return response()->json([
            'success' => true,
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'subscription_status' => $shop->subscription_status,
                'subscription_ends_at' => $shop->subscription_ends_at?->format('d/m/Y'),
                'billing_cycle' => $shop->billing_cycle,
                'cutoff' => $shop->cutoff,
                'monthly_price' => $shop->monthly_price,
                'yearly_price' => $shop->yearly_price,
                'plan_name' => $shop->plan?->name,
            ],
            'payments' => $payments->map(function ($p) {
                return [
                    'id' => $p->id,
                    'date' => $p->starts_at?->format('d/m/Y'),
                    'created_at' => $p->created_at->format('d/m/Y H:i'),
                    'billing_period' => $p->billing_period,
                    'price_without_iva' => $p->price_without_iva,
                    'iva_amount' => $p->iva_amount,
                    'total_amount' => $p->total_amount,
                    'currency' => $p->currency,
                    'payment_method' => $p->payment_method,
                    'transaction_id' => $p->transaction_id,
                    'status' => $p->status,
                    'starts_at' => $p->starts_at?->format('d/m/Y'),
                    'ends_at' => $p->ends_at?->format('d/m/Y'),
                    'registered_by' => $p->user?->name ?? 'Sistema',
                    'notes' => $p->admin_notes,
                    'plan_name' => $p->plan?->name,
                ];
            }),
            'pagination' => [
                'total' => $payments->total(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Obtener detalle de un pago específico
     */
    public function getPaymentDetail($shopId, $paymentId)
    {
        $payment = Subscription::where('shop_id', $shopId)
            ->with(['user:id,name', 'plan:id,name', 'shop:id,name'])
            ->findOrFail($paymentId);

        return response()->json([
            'success' => true,
            'payment' => [
                'id' => $payment->id,
                'shop_name' => $payment->shop?->name,
                'plan_name' => $payment->plan?->name,
                'date' => $payment->starts_at?->format('d/m/Y'),
                'created_at' => $payment->created_at->format('d/m/Y H:i'),
                'updated_at' => $payment->updated_at->format('d/m/Y H:i'),
                'billing_period' => $payment->billing_period,
                'price_without_iva' => $payment->price_without_iva,
                'iva_amount' => $payment->iva_amount,
                'total_amount' => $payment->total_amount,
                'currency' => $payment->currency,
                'payment_method' => $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status,
                'starts_at' => $payment->starts_at?->format('d/m/Y'),
                'ends_at' => $payment->ends_at?->format('d/m/Y'),
                'registered_by' => $payment->user?->name ?? 'Sistema',
                'user_id' => $payment->user_id,
                'notes' => $payment->admin_notes,
            ],
        ]);
    }

    /**
     * Actualizar un pago existente
     */
    public function updatePayment(Request $request, $shopId, $paymentId)
    {
        $payment = Subscription::where('shop_id', $shopId)->findOrFail($paymentId);

        $request->validate([
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:transfer,cash,card,other',
            'transaction_id' => 'nullable|string|max:100',
            'admin_notes' => 'nullable|string|max:500',
            'starts_at' => 'nullable|date',
        ]);

        // Guardar valores anteriores para el log
        $oldValues = [
            'total_amount' => $payment->total_amount,
            'payment_method' => $payment->payment_method,
            'transaction_id' => $payment->transaction_id,
            'admin_notes' => $payment->admin_notes,
        ];

        // Recalcular IVA si cambió el monto
        $amount = (float) $request->total_amount;
        $ivaRate = SubscriptionSetting::get('iva_rate', 16);

        // Asumimos que el monto incluye IVA si el original lo tenía
        if ($payment->iva_amount > 0) {
            $priceWithoutIva = round($amount / (1 + ($ivaRate / 100)), 2);
            $ivaAmount = round($amount - $priceWithoutIva, 2);
        } else {
            $priceWithoutIva = $amount;
            $ivaAmount = 0;
        }

        $payment->update([
            'price_without_iva' => $priceWithoutIva,
            'iva_amount' => $ivaAmount,
            'total_amount' => $amount,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id ?: $payment->transaction_id,
            'admin_notes' => $request->admin_notes,
            'starts_at' => $request->filled('starts_at') ? \Carbon\Carbon::parse($request->starts_at) : $payment->starts_at,
        ]);

        // Log de cambio (en admin_notes agregamos historial)
        $logEntry = "\n[Editado " . now()->format('d/m/Y H:i') . " por " . auth()->user()->name . "]";
        if ($oldValues['total_amount'] != $amount) {
            $logEntry .= " Monto: {$oldValues['total_amount']} → {$amount}";
        }

        return response()->json([
            'success' => true,
            'message' => 'Pago actualizado correctamente',
        ]);
    }

    /**
     * Eliminar un pago (soft delete conceptual - marcamos como cancelled)
     */
    public function deletePayment(Request $request, $shopId, $paymentId)
    {
        $payment = Subscription::where('shop_id', $shopId)->findOrFail($paymentId);

        // No eliminamos físicamente, marcamos como cancelado
        $payment->update([
            'status' => 'cancelled',
            'admin_notes' => $payment->admin_notes . "\n[Cancelado " . now()->format('d/m/Y H:i') . " por " . auth()->user()->name . "]",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pago cancelado correctamente',
        ]);
    }

    /**
     * Generar PDF de recibo de pago
     */
    public function generatePaymentPdf($shopId, $paymentId)
    {
        $payment = Subscription::where('shop_id', $shopId)
            ->with(['shop', 'plan'])
            ->findOrFail($paymentId);

        $shop = $payment->shop;

        // Mapeo de métodos de pago
        $metodosLabels = [
            'transfer' => 'Transferencia Bancaria',
            'cash' => 'Efectivo',
            'card' => 'Tarjeta de Crédito/Débito',
            'other' => 'Otro',
        ];

        // Preparar datos para la vista
        $data = [
            'recibo_numero' => '#REC-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
            'fecha_emision' => now()->format('d/m/Y'),
            'fecha_pago' => $payment->starts_at?->format('d/m/Y') ?? $payment->created_at->format('d/m/Y'),

            // Cliente (tienda)
            'cliente_nombre' => $shop->name,
            'cliente_email' => $shop->email,
            'cliente_telefono' => $shop->phone,

            // Concepto
            'concepto' => $payment->billing_period === 'yearly'
                ? 'Suscripcion Anual - J2Biznes'
                : 'Suscripcion Mensual - J2Biznes',
            'plan_nombre' => $payment->plan?->name ?? 'Plan Estándar',
            'periodo' => ($payment->starts_at?->format('d/m/Y') ?? '-') . ' al ' . ($payment->ends_at?->format('d/m/Y') ?? '-'),
            'ciclo' => $payment->billing_period,

            // Montos
            'subtotal' => $payment->price_without_iva ?? $payment->total_amount,
            'iva' => $payment->iva_amount ?? 0,
            'total' => $payment->total_amount,
            'moneda' => $payment->currency ?? 'MXN',

            // Pago
            'metodo_pago' => $metodosLabels[$payment->payment_method] ?? $payment->payment_method,
            'referencia' => $payment->transaction_id,
            'notas' => $payment->admin_notes,
        ];

        // Generar PDF
        $pdf = Pdf::loadView('superadmin.subscription_payment_pdf', $data);

        // Configurar opciones del PDF
        $pdf->setPaper('letter', 'portrait');

        // Nombre del archivo
        $filename = 'Recibo_' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) . '_' . $shop->name . '.pdf';
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename); // Limpiar caracteres especiales

        // Mostrar PDF en navegador (evita warning de Brave/Chrome en HTTP)
        return $pdf->stream($filename);
    }

    /**
     * Verificar si ya existe pago para el periodo actual
     */
    public function checkExistingPayment($shopId)
    {
        // Verificar que la tienda exista
        Shop::findOrFail($shopId);

        // Buscar si hay un pago activo que cubra el periodo actual
        $existingPayment = Subscription::where('shop_id', $shopId)
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->first();

        return response()->json([
            'exists' => $existingPayment !== null,
            'payment' => $existingPayment ? [
                'id' => $existingPayment->id,
                'ends_at' => $existingPayment->ends_at?->format('d/m/Y'),
            ] : null,
        ]);
    }
}
