<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShopTaxRateRequest;
use App\Models\ShopTaxRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD del catálogo de tasas de impuesto por tienda.
 * Cada nota congela la tasa elegida (snapshot en receipts.tax_rate) — borrar/editar
 * una tasa aquí NO altera notas ya emitidas.
 *
 * Plan: xdev/ventas/PLAN_IMPUESTO_SELECCIONABLE_POR_NOTA.md
 */
class ShopTaxRateController extends Controller
{
    /**
     * Renderiza la vista Blade que monta el componente Vue.
     */
    public function page()
    {
        return view('admin.configuracion.tasas-impuesto');
    }

    /**
     * Listar tasas de la tienda autenticada (la default primero).
     */
    public function index(Request $request): JsonResponse
    {
        $shop = $request->user()->shop;

        $rates = ShopTaxRate::where('shop_id', $shop->id)
            ->orderByDesc('is_default')
            ->orderByDesc('active')
            ->orderBy('rate')
            ->get();

        return response()->json([
            'ok' => true,
            'rates' => $rates,
        ]);
    }

    /**
     * Crear tasa. Si is_default=true desmarca las demás. La primera tasa se fuerza default+activa.
     */
    public function store(ShopTaxRateRequest $request): JsonResponse
    {
        $shop = $request->user()->shop;
        $data = $request->validated();
        $data['shop_id'] = $shop->id;

        $rate = DB::transaction(function () use ($shop, $data) {
            $isFirst = ShopTaxRate::where('shop_id', $shop->id)->count() === 0;
            if ($isFirst) {
                $data['is_default'] = true;
                $data['active'] = true;
            }
            if (!empty($data['is_default'])) {
                ShopTaxRate::where('shop_id', $shop->id)->update(['is_default' => false]);
            }
            return ShopTaxRate::create($data);
        });

        return response()->json([
            'ok' => true,
            'rate' => $rate,
            'message' => 'Tasa de impuesto registrada.',
        ], 201);
    }

    /**
     * Actualizar tasa. Protege la invariante "siempre debe existir ≥1 tasa activa default".
     */
    public function update(ShopTaxRateRequest $request, int $id): JsonResponse
    {
        $shop = $request->user()->shop;
        $rate = ShopTaxRate::where('shop_id', $shop->id)->findOrFail($id);
        $data = $request->validated();

        // No permitir dejar inactiva (o quitar default de) la única tasa default activa
        // sin que exista otra que pueda tomar el relevo.
        $quitaDefault = array_key_exists('is_default', $data) && !$data['is_default'] && $rate->is_default;
        $desactiva    = array_key_exists('active', $data) && !$data['active'] && $rate->active;
        if (($quitaDefault || $desactiva) && $rate->is_default) {
            $hayOtraActiva = ShopTaxRate::where('shop_id', $shop->id)
                ->where('id', '!=', $rate->id)
                ->where('active', true)
                ->exists();
            if (!$hayOtraActiva) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Debe existir al menos una tasa activa predeterminada. Marca otra como predeterminada primero.',
                ], 422);
            }
        }

        DB::transaction(function () use ($shop, $rate, $data) {
            if (!empty($data['is_default'])) {
                ShopTaxRate::where('shop_id', $shop->id)
                    ->where('id', '!=', $rate->id)
                    ->update(['is_default' => false]);
            }
            $rate->update($data);

            // Si dejó de ser default/activa, promover otra activa como default.
            if (!$rate->fresh()->is_default || !$rate->fresh()->active) {
                $hayDefault = ShopTaxRate::where('shop_id', $shop->id)
                    ->where('active', true)->where('is_default', true)->exists();
                if (!$hayDefault) {
                    $promovida = ShopTaxRate::where('shop_id', $shop->id)
                        ->where('active', true)->orderBy('id')->first();
                    if ($promovida) {
                        $promovida->update(['is_default' => true]);
                    }
                }
            }
        });

        return response()->json([
            'ok' => true,
            'rate' => $rate->fresh(),
            'message' => 'Tasa de impuesto actualizada.',
        ]);
    }

    /**
     * Eliminar tasa del catálogo (seguro: los snapshots en receipts son por valor).
     * Si era default, promueve otra activa.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $shop = $request->user()->shop;
        $rate = ShopTaxRate::where('shop_id', $shop->id)->findOrFail($id);

        $totalActivas = ShopTaxRate::where('shop_id', $shop->id)->where('active', true)->count();
        if ($rate->active && $totalActivas <= 1) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes eliminar la única tasa activa. Crea otra primero.',
            ], 422);
        }

        DB::transaction(function () use ($shop, $rate) {
            $eraDefault = $rate->is_default;
            $rate->delete();
            if ($eraDefault) {
                $nueva = ShopTaxRate::where('shop_id', $shop->id)
                    ->where('active', true)->orderBy('id')->first();
                if ($nueva) {
                    $nueva->update(['is_default' => true]);
                }
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Tasa de impuesto eliminada.',
        ]);
    }

    /**
     * Marcar una tasa como predeterminada (desmarca las demás). La default se activa si no lo estaba.
     */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        $shop = $request->user()->shop;
        $rate = ShopTaxRate::where('shop_id', $shop->id)->findOrFail($id);

        DB::transaction(function () use ($shop, $rate) {
            ShopTaxRate::where('shop_id', $shop->id)->update(['is_default' => false]);
            $rate->update(['is_default' => true, 'active' => true]);
        });

        return response()->json([
            'ok' => true,
            'rate' => $rate->fresh(),
            'message' => 'Tasa marcada como predeterminada.',
        ]);
    }
}
