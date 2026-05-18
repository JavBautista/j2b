<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Monitor\MonitorBillingService;
use App\Services\Monitor\MonitorLicenseService;
use Illuminate\Support\Facades\Auth;

class MonitorController extends Controller
{
    /**
     * Resumen del cupo de licencias J2 Monitor de la tienda del admin actual.
     * El cupo total lo asigna el superadmin; aquí solo se consulta.
     */
    public function shopSummary(MonitorLicenseService $service)
    {
        $shop = Auth::user()->shop;

        return response()->json([
            'ok' => true,
            'monitor' => $service->summary($shop),
        ]);
    }

    /**
     * Cobro mensual previsto del servicio J2 Monitor según tier vigente
     * (o el override grandfathered si aplica). Read-only para que el admin
     * sepa cuánto pagará el próximo corte.
     */
    public function billingSummary(MonitorBillingService $service)
    {
        $shop = Auth::user()->shop;
        $billing = $service->calculateForShop($shop);
        $tier = $billing['tier'];

        return response()->json([
            'ok' => true,
            'shop_id' => $shop->id,
            'monitor_billing_enabled' => (bool) $shop->monitor_billing_enabled,
            'monitor_licenses_total' => (int) $shop->monitor_licenses_total,
            'tier' => $tier ? [
                'id' => $tier->id,
                'name' => $tier->name,
                'min_equipment' => $tier->min_equipment,
                'max_equipment' => $tier->max_equipment,
                'is_flat_rate' => (bool) $tier->is_flat_rate,
                'includes_base_plan' => (bool) $tier->includes_base_plan,
            ] : null,
            'unit_price' => $billing['unit_price'],
            'flat_amount' => $billing['flat_amount'],
            'monitor_amount_monthly' => $billing['monitor_amount'],
            'is_locked_price' => $shop->monitor_tier_locked_id !== null,
            'currency' => $shop->plan?->currency ?? 'MXN',
        ]);
    }
}
