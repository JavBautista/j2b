<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\CfdiEmisor;
use App\Services\Facturacion\HubCfdiService;

class CfdiController extends Controller
{
    /**
     * Lista tiendas con su estado CFDI (para tabla principal)
     */
    public function getShops(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $shops = Shop::with('cfdiEmisor:id,shop_id,rfc,razon_social,is_registered,timbres_asignados,timbres_usados')
            ->when($request->buscar != '', function ($query) use ($request) {
                return $query->where('name', 'like', '%' . $request->buscar . '%');
            })
            ->when($request->estatus != '', function ($query) use ($request) {
                if ($request->estatus === 'cfdi_active') {
                    return $query->where('cfdi_enabled', true);
                } elseif ($request->estatus === 'cfdi_inactive') {
                    return $query->where('cfdi_enabled', false);
                } elseif ($request->estatus === 'configured') {
                    return $query->whereHas('cfdiEmisor');
                }
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        return [
            'pagination' => [
                'total' => $shops->total(),
                'current_page' => $shops->currentPage(),
                'per_page' => $shops->perPage(),
                'last_page' => $shops->lastPage(),
                'from' => $shops->firstItem(),
                'to' => $shops->lastItem(),
            ],
            'shops' => $shops,
        ];
    }

    /**
     * Habilitar/deshabilitar CFDI para una tienda
     */
    public function toggleCfdi(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $request->validate([
            'shop_id' => 'required|exists:shops,id',
        ]);

        $shop = Shop::findOrFail($request->shop_id);
        $shop->cfdi_enabled = !$shop->cfdi_enabled;
        $shop->save();

        $estado = $shop->cfdi_enabled ? 'habilitado' : 'deshabilitado';

        return response()->json([
            'ok' => true,
            'message' => "CFDI {$estado} para {$shop->name}",
            'cfdi_enabled' => $shop->cfdi_enabled,
        ]);
    }

    /**
     * Asignar timbres contratados a una tienda
     */
    public function asignarTimbresShop(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $shop = Shop::findOrFail($request->shop_id);
        $shop->cfdi_timbres_contratados += $request->cantidad;
        $shop->save();

        // Si ya tiene emisor configurado, sincronizar timbres_asignados
        if ($shop->cfdiEmisor) {
            $shop->cfdiEmisor->timbres_asignados = $shop->cfdi_timbres_contratados;
            $shop->cfdiEmisor->save();
        }

        return response()->json([
            'ok' => true,
            'message' => "Se asignaron {$request->cantidad} timbres a {$shop->name}",
            'cfdi_timbres_contratados' => $shop->cfdi_timbres_contratados,
        ]);
    }

    /**
     * Lista emisores registrados (tabla secundaria)
     */
    public function get(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $emisores = CfdiEmisor::with('shop:id,name')
            ->withCount('invoices')
            ->when($request->buscar != '', function ($query) use ($request) {
                if ($request->criterio === 'shop') {
                    return $query->whereHas('shop', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->buscar . '%');
                    });
                }
                return $query->where($request->criterio, 'like', '%' . $request->buscar . '%');
            })
            ->when($request->estatus != '', function ($query) use ($request) {
                if ($request->estatus === 'active') {
                    return $query->where('active', true);
                } elseif ($request->estatus === 'inactive') {
                    return $query->where('active', false);
                } elseif ($request->estatus === 'registered') {
                    return $query->where('is_registered', true);
                }
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        return [
            'pagination' => [
                'total' => $emisores->total(),
                'current_page' => $emisores->currentPage(),
                'per_page' => $emisores->perPage(),
                'last_page' => $emisores->lastPage(),
                'from' => $emisores->firstItem(),
                'to' => $emisores->lastItem(),
            ],
            'emisores' => $emisores,
        ];
    }

    /**
     * Timbres globales desde HUB CFDI
     */
    public function getTimbresGlobales()
    {
        try {
            $service = new HubCfdiService();
            $result = $service->obtenerTimbres();

            if ($result['success']) {
                $body = $result['data']['body'] ?? $result['data'];
                return response()->json([
                    'ok' => true,
                    'data' => [
                        'contratados' => $body['TimbresContratados'] ?? 0,
                        'consumidos' => $body['TimbresConsumidos'] ?? 0,
                        'disponibles' => $body['TimbresDisponibles'] ?? 0,
                    ],
                ]);
            }

            return response()->json([
                'ok' => false,
                'error' => $result['error'] ?? 'Error al consultar timbres',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
