<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SatProductCode;
use App\Models\SatUnitCode;
use App\Services\Facturacion\SatCatalogService;
use Illuminate\Http\Request;

class SatCatalogController extends Controller
{
    /**
     * Bundle completo de catálogos fiscales SAT (fuente única para web + Ionic):
     * { regimenes, usos, matriz }. Catálogos chicos y cuasi-estáticos, cacheados.
     */
    public function fiscalCatalogs(SatCatalogService $catalogs)
    {
        return response()->json($catalogs->bundle());
    }

    public function productCodes(Request $request)
    {
        $q = $request->get('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = SatProductCode::where('code', 'like', "{$q}%")
            ->orWhere('description', 'like', "%{$q}%")
            ->limit(20)
            ->get(['code', 'description']);

        return response()->json($results);
    }

    public function unitCodes(Request $request)
    {
        $q = $request->get('q', '');

        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $results = SatUnitCode::where('code', 'like', "{$q}%")
            ->orWhere('name', 'like', "%{$q}%")
            ->limit(20)
            ->get(['code', 'name']);

        return response()->json($results);
    }
}
