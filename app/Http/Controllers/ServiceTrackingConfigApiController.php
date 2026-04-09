<?php

namespace App\Http\Controllers;

use App\Models\ServiceTrackingStep;
use Illuminate\Http\Request;

class ServiceTrackingConfigApiController extends Controller
{
    public function get(Request $request)
    {
        $shop = $request->user()->shop;
        $steps = $shop->serviceTrackingSteps()->orderBy('sort_order')->get();

        return response()->json([
            'ok' => true,
            'steps' => $steps,
            'receipt_disclaimer' => $shop->receipt_disclaimer,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'is_initial' => 'boolean',
            'is_final' => 'boolean',
        ]);

        $shop = $request->user()->shop;
        $maxOrder = $shop->serviceTrackingSteps()->max('sort_order') ?? -1;

        if ($request->is_initial) {
            $shop->serviceTrackingSteps()->update(['is_initial' => false]);
        }
        if ($request->is_final) {
            $shop->serviceTrackingSteps()->update(['is_final' => false]);
        }

        $step = ServiceTrackingStep::create([
            'shop_id' => $shop->id,
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'icon' => $request->icon,
            'sort_order' => $maxOrder + 1,
            'is_initial' => $request->is_initial ?? false,
            'is_final' => $request->is_final ?? false,
        ]);

        return response()->json(['ok' => true, 'step' => $step, 'message' => 'Paso creado correctamente']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'is_initial' => 'boolean',
            'is_final' => 'boolean',
        ]);

        $shop = $request->user()->shop;
        $step = ServiceTrackingStep::where('shop_id', $shop->id)->findOrFail($id);

        if ($request->is_initial) {
            $shop->serviceTrackingSteps()->where('id', '!=', $id)->update(['is_initial' => false]);
        }
        if ($request->is_final) {
            $shop->serviceTrackingSteps()->where('id', '!=', $id)->update(['is_final' => false]);
        }

        $step->update([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'icon' => $request->icon,
            'is_initial' => $request->is_initial ?? false,
            'is_final' => $request->is_final ?? false,
        ]);

        return response()->json(['ok' => true, 'step' => $step, 'message' => 'Paso actualizado']);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:service_tracking_steps,id',
        ]);

        $shop = $request->user()->shop;

        foreach ($request->ids as $index => $id) {
            ServiceTrackingStep::where('id', $id)
                ->where('shop_id', $shop->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['ok' => true, 'message' => 'Orden actualizado']);
    }

    public function toggleActive(Request $request, $id)
    {
        $shop = $request->user()->shop;
        $step = ServiceTrackingStep::where('shop_id', $shop->id)->findOrFail($id);

        $step->update(['active' => !$step->active]);

        return response()->json(['ok' => true, 'step' => $step, 'message' => $step->active ? 'Paso activado' : 'Paso desactivado']);
    }

    public function setInitial(Request $request, $id)
    {
        $shop = $request->user()->shop;
        $step = ServiceTrackingStep::where('shop_id', $shop->id)->findOrFail($id);

        $shop->serviceTrackingSteps()->update(['is_initial' => false]);
        $step->update(['is_initial' => true]);

        return response()->json(['ok' => true, 'message' => 'Paso inicial actualizado']);
    }

    public function setFinal(Request $request, $id)
    {
        $shop = $request->user()->shop;
        $step = ServiceTrackingStep::where('shop_id', $shop->id)->findOrFail($id);

        $shop->serviceTrackingSteps()->update(['is_final' => false]);
        $step->update(['is_final' => true]);

        return response()->json(['ok' => true, 'message' => 'Paso final actualizado']);
    }

    public function delete(Request $request, $id)
    {
        $shop = $request->user()->shop;
        $step = ServiceTrackingStep::where('shop_id', $shop->id)->findOrFail($id);

        if ($step->trackingEntries()->exists()) {
            return response()->json(['ok' => false, 'message' => 'No se puede eliminar: este paso ya fue usado en tareas'], 422);
        }

        $step->delete();

        return response()->json(['ok' => true, 'message' => 'Paso eliminado']);
    }

    public function updateDisclaimer(Request $request)
    {
        $request->validate([
            'receipt_disclaimer' => 'nullable|string|max:1000',
        ]);

        $shop = $request->user()->shop;
        $shop->receipt_disclaimer = $request->receipt_disclaimer;
        $shop->save();

        return response()->json(['ok' => true, 'message' => 'Aviso legal actualizado']);
    }
}
