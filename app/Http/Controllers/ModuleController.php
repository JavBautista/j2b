<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * API (Ionic): expone los módulos activos de la tienda del usuario autenticado,
 * para que la app oculte/muestre secciones según lo que la tienda tiene contratado.
 *
 * Controlador propio de la API (separado del flujo web superadmin).
 */
class ModuleController extends Controller
{
    /**
     * GET /api/auth/user/modules
     * Devuelve los módulos activos (core + vendibles contratados vigentes) de la tienda.
     */
    public function mine(Request $request)
    {
        $shop = $request->user()->shop;

        if (!$shop) {
            return response()->json(['ok' => true, 'modules' => [], 'keys' => []]);
        }

        $activos = $shop->activeModules();

        return response()->json([
            'ok' => true,
            'modules' => $activos->map(fn ($m) => [
                'key'     => $m->key,
                'name'    => $m->name,
                'icon'    => $m->icon,
                'is_core' => (bool) $m->is_core,
            ])->values(),
            // Lista plana de keys para chequeo rápido en el front (ej. keys.includes('tasks')).
            'keys' => $activos->pluck('key')->values(),
        ]);
    }
}
