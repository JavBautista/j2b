<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceTrackingStep;
use Illuminate\Http\Request;

class ServiceTrackingConfigController extends Controller
{
    public function index()
    {
        return view('admin.configurations.service-tracking');
    }

    public function get()
    {
        $shop = auth()->user()->shop;
        $steps = $shop->serviceTrackingSteps()->orderBy('sort_order')->get();

        return response()->json([
            'steps' => $steps,
            'receipt_disclaimer' => $shop->receipt_disclaimer,
        ]);
    }

    public function updateDisclaimer(Request $request)
    {
        $request->validate([
            'receipt_disclaimer' => 'nullable|string|max:1000',
        ]);

        $shop = auth()->user()->shop;
        $shop->receipt_disclaimer = $request->receipt_disclaimer;
        $shop->save();

        return response()->json(['message' => 'Aviso legal actualizado']);
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

        $shop = auth()->user()->shop;

        // Calcular sort_order
        $maxOrder = $shop->serviceTrackingSteps()->max('sort_order') ?? -1;

        // Si se marca como inicial, quitar el flag de los demás
        if ($request->is_initial) {
            $shop->serviceTrackingSteps()->update(['is_initial' => false]);
        }

        // Si se marca como final, quitar el flag de los demás
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

        return response()->json(['step' => $step, 'message' => 'Paso creado correctamente']);
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

        $shop = auth()->user()->shop;
        $step = ServiceTrackingStep::where('shop_id', $shop->id)->findOrFail($id);

        // Si se marca como inicial, quitar el flag de los demás
        if ($request->is_initial) {
            $shop->serviceTrackingSteps()->where('id', '!=', $id)->update(['is_initial' => false]);
        }

        // Si se marca como final, quitar el flag de los demás
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

        return response()->json(['step' => $step, 'message' => 'Paso actualizado']);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:service_tracking_steps,id',
        ]);

        $shop = auth()->user()->shop;

        foreach ($request->ids as $index => $id) {
            ServiceTrackingStep::where('id', $id)
                ->where('shop_id', $shop->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Orden actualizado']);
    }

    public function toggleActive($id)
    {
        $shop = auth()->user()->shop;
        $step = ServiceTrackingStep::where('shop_id', $shop->id)->findOrFail($id);

        $step->update(['active' => !$step->active]);

        return response()->json(['step' => $step, 'message' => $step->active ? 'Paso activado' : 'Paso desactivado']);
    }

    public function setInitial($id)
    {
        $shop = auth()->user()->shop;
        $step = ServiceTrackingStep::where('shop_id', $shop->id)->findOrFail($id);

        // Quitar flag de todos y asignar a este
        $shop->serviceTrackingSteps()->update(['is_initial' => false]);
        $step->update(['is_initial' => true]);

        return response()->json(['message' => 'Paso inicial actualizado']);
    }

    public function setFinal($id)
    {
        $shop = auth()->user()->shop;
        $step = ServiceTrackingStep::where('shop_id', $shop->id)->findOrFail($id);

        $shop->serviceTrackingSteps()->update(['is_final' => false]);
        $step->update(['is_final' => true]);

        return response()->json(['message' => 'Paso final actualizado']);
    }

    public function delete($id)
    {
        $shop = auth()->user()->shop;
        $step = ServiceTrackingStep::where('shop_id', $shop->id)->findOrFail($id);

        // Verificar si está en uso
        $inUse = $step->trackingEntries()->exists();
        if ($inUse) {
            return response()->json(['message' => 'No se puede eliminar: este paso ya fue usado en tareas'], 422);
        }

        $step->delete();

        return response()->json(['message' => 'Paso eliminado']);
    }
}
