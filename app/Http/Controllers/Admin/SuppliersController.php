<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuppliersController extends Controller
{
    /**
     * Buscar proveedores para el modal de selección
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $buscar = $request->get('buscar', '');

        $query = Supplier::where('shop_id', $shop->id)
            ->where('active', 1);

        // Filtro de búsqueda por nombre, empresa o teléfono
        if (!empty($buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', '%' . $buscar . '%')
                  ->orWhere('company', 'like', '%' . $buscar . '%')
                  ->orWhere('phone', 'like', '%' . $buscar . '%')
                  ->orWhere('movil', 'like', '%' . $buscar . '%');
            });
        }

        $suppliers = $query->orderBy('name', 'asc')->paginate(15);

        return response()->json([
            'suppliers' => $suppliers
        ]);
    }
}
