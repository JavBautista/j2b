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

    /**
     * Listado paginado para el CRUD admin web (consumido por SuppliersComponent.vue)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $buscar = $request->get('buscar', '');
        $criterio = $request->get('criterio', 'name');
        $estatus = $request->get('estatus', 'active');

        $query = Supplier::where('shop_id', $shop->id);

        if ($estatus === 'active') {
            $query->where('active', 1);
        } elseif ($estatus === 'inactive') {
            $query->where('active', 0);
        }

        if (!empty($buscar)) {
            $criteriosValidos = ['name', 'email', 'movil', 'company'];
            $campo = in_array($criterio, $criteriosValidos, true) ? $criterio : 'name';
            $query->where($campo, 'like', '%' . $buscar . '%');
        }

        $suppliers = $query->orderBy('id', 'desc')->paginate(12);

        return response()->json([
            'suppliers' => $suppliers,
            'pagination' => [
                'total' => $suppliers->total(),
                'current_page' => $suppliers->currentPage(),
                'per_page' => $suppliers->perPage(),
                'last_page' => $suppliers->lastPage(),
                'from' => $suppliers->firstItem(),
                'to' => $suppliers->lastItem(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->isLimitedAdmin()) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permisos para crear proveedores.',
            ], 403);
        }

        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'company'               => 'nullable|string|max:255',
            'email'                 => 'nullable|email|max:255',
            'phone'                 => 'nullable|string|max:30',
            'movil'                 => 'nullable|string|max:30',
            'address'               => 'nullable|string|max:255',
            'zip_code'              => 'nullable|string|max:20',
            'number_out'            => 'nullable|string|max:30',
            'number_int'            => 'nullable|string|max:30',
            'district'              => 'nullable|string|max:100',
            'city'                  => 'nullable|string|max:100',
            'state'                 => 'nullable|string|max:100',
            'reference'             => 'nullable|string|max:255',
            'detail'                => 'nullable|string|max:255',
            'observations'          => 'nullable|string',
            'bank_number_main'      => 'nullable|string|max:100',
            'bank_number_secondary' => 'nullable|string|max:100',
        ]);

        $shop = $user->shop;

        $supplier = new Supplier();
        $supplier->shop_id = $shop->id;
        $supplier->active = 1;
        $supplier->fill($data);
        $supplier->save();

        return response()->json([
            'ok' => true,
            'supplier' => $supplier,
            'message' => 'Proveedor creado exitosamente',
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        if ($user->isLimitedAdmin()) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permisos para editar proveedores.',
            ], 403);
        }

        $data = $request->validate([
            'id'                    => 'required|exists:suppliers,id',
            'name'                  => 'required|string|max:255',
            'company'               => 'nullable|string|max:255',
            'email'                 => 'nullable|email|max:255',
            'phone'                 => 'nullable|string|max:30',
            'movil'                 => 'nullable|string|max:30',
            'address'               => 'nullable|string|max:255',
            'zip_code'              => 'nullable|string|max:20',
            'number_out'            => 'nullable|string|max:30',
            'number_int'            => 'nullable|string|max:30',
            'district'              => 'nullable|string|max:100',
            'city'                  => 'nullable|string|max:100',
            'state'                 => 'nullable|string|max:100',
            'reference'             => 'nullable|string|max:255',
            'detail'                => 'nullable|string|max:255',
            'observations'          => 'nullable|string',
            'bank_number_main'      => 'nullable|string|max:100',
            'bank_number_secondary' => 'nullable|string|max:100',
        ]);

        $shop = $user->shop;

        $supplier = Supplier::where('id', $data['id'])
            ->where('shop_id', $shop->id)
            ->firstOrFail();

        unset($data['id']);
        $supplier->fill($data);
        $supplier->save();

        return response()->json([
            'ok' => true,
            'supplier' => $supplier,
            'message' => 'Proveedor actualizado exitosamente',
        ]);
    }

    public function inactive(Request $request)
    {
        return $this->toggleActive($request, 0, 'desactivar', 'desactivado');
    }

    public function active(Request $request)
    {
        return $this->toggleActive($request, 1, 'activar', 'activado');
    }

    private function toggleActive(Request $request, int $value, string $accion, string $resultado)
    {
        $user = Auth::user();

        if ($user->isLimitedAdmin()) {
            return response()->json([
                'ok' => false,
                'message' => "No tienes permisos para {$accion} proveedores.",
            ], 403);
        }

        $request->validate([
            'id' => 'required|exists:suppliers,id',
        ]);

        $shop = $user->shop;

        $supplier = Supplier::where('id', $request->id)
            ->where('shop_id', $shop->id)
            ->firstOrFail();

        $supplier->active = $value;
        $supplier->save();

        return response()->json([
            'ok' => true,
            'message' => "Proveedor {$resultado} exitosamente",
        ]);
    }
}
