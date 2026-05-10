<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
}
