<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\MonitorPricingTier;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MonitorPricingTiersController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->ajax()) {
            return redirect('/');
        }

        $tiers = MonitorPricingTier::orderBy('sort_order')->get();

        $tierIdsInUse = Subscription::whereNotNull('monitor_tier_id')
            ->distinct()
            ->pluck('monitor_tier_id')
            ->all();

        return response()->json([
            'tiers' => $tiers,
            'tier_ids_in_use' => $tierIdsInUse,
        ]);
    }

    public function store(Request $request)
    {
        if (! $request->ajax()) {
            return redirect('/');
        }

        $data = $this->validatePayload($request);

        $tier = MonitorPricingTier::create($data);

        return response()->json([
            'ok' => true,
            'message' => "Tier \"{$tier->name}\" creado.",
            'tier' => $tier,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (! $request->ajax()) {
            return redirect('/');
        }

        $tier = MonitorPricingTier::findOrFail($id);

        $inUse = Subscription::where('monitor_tier_id', $tier->id)->exists();
        $data = $this->validatePayload($request, $tier->id);

        // Si el tier ya tiene subscriptions referenciándolo, bloquear cambio de rango
        // para preservar integridad histórica. Precio y bandera active sí se pueden cambiar.
        if ($inUse) {
            $rangeChanged = $tier->min_equipment !== (int) $data['min_equipment']
                || $tier->max_equipment !== ($data['max_equipment'] !== null ? (int) $data['max_equipment'] : null)
                || (bool) $tier->is_flat_rate !== (bool) $data['is_flat_rate']
                || (bool) $tier->includes_base_plan !== (bool) $data['includes_base_plan'];

            if ($rangeChanged) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Este tier ya tiene pagos registrados. Solo se puede editar el precio y el estado activo. Para cambiar rangos o tipo, desactiva este tier y crea uno nuevo.',
                ], 422);
            }
        }

        $tier->update($data);

        return response()->json([
            'ok' => true,
            'message' => "Tier \"{$tier->name}\" actualizado.",
            'tier' => $tier->fresh(),
        ]);
    }

    public function toggleActive(Request $request, $id)
    {
        if (! $request->ajax()) {
            return redirect('/');
        }

        $tier = MonitorPricingTier::findOrFail($id);
        $tier->active = ! $tier->active;
        $tier->save();

        return response()->json([
            'ok' => true,
            'message' => "Tier \"{$tier->name}\" " . ($tier->active ? 'activado' : 'desactivado') . '.',
            'tier' => $tier,
        ]);
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:60'],
            'min_equipment' => ['required', 'integer', 'min:1'],
            'max_equipment' => ['nullable', 'integer', 'gte:min_equipment'],
            'is_flat_rate' => ['required', 'boolean'],
            'price_per_equipment' => ['nullable', 'numeric', 'min:0', 'required_if:is_flat_rate,false'],
            'flat_amount' => ['nullable', 'numeric', 'min:0', 'required_if:is_flat_rate,true'],
            'includes_base_plan' => ['required', 'boolean'],
            'currency' => ['required', Rule::in(['MXN', 'USD'])],
            'active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];

        $request->validate($rules);

        $data = $request->only(array_keys($rules));

        // Normalizar: si flat, anular price_per_equipment; si no, anular flat_amount.
        if ($data['is_flat_rate']) {
            $data['price_per_equipment'] = null;
        } else {
            $data['flat_amount'] = null;
            $data['includes_base_plan'] = false; // includes_base_plan solo aplica a flat
        }

        return $data;
    }
}
