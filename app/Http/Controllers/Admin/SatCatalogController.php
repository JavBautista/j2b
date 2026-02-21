<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SatProductCode;
use App\Models\SatUnitCode;
use Illuminate\Http\Request;

class SatCatalogController extends Controller
{
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
