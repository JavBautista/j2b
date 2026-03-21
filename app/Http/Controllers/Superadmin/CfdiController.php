<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Exports\FacturasEmitidasExport;
use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\CfdiEmisor;
use App\Models\CfdiInvoice;
use App\Services\Facturacion\HubCfdiService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

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
     * Primero asigna en TBT API, solo si responde ok suma en BD local
     */
    public function asignarTimbresShop(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $shop = Shop::findOrFail($request->shop_id);

        // Validar que tenga emisor registrado en TBT (necesita RFC)
        if (!$shop->cfdiEmisor || !$shop->cfdiEmisor->is_registered) {
            return response()->json([
                'ok' => false,
                'message' => 'La tienda no tiene emisor registrado en HUB CFDI. Debe registrar su emisor primero.',
            ], 422);
        }

        // Llamar a TBT API para asignar timbres al emisor
        try {
            $service = new HubCfdiService();
            $result = $service->asignarTimbres($shop->cfdiEmisor->rfc, $request->cantidad);

            if (!$result['success']) {
                Log::warning('TBT AsignarTimbres falló', [
                    'shop_id' => $shop->id,
                    'rfc' => $shop->cfdiEmisor->rfc,
                    'cantidad' => $request->cantidad,
                    'error' => $result['error'],
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'Error al asignar timbres en HUB CFDI: ' . ($result['error'] ?? 'Error desconocido'),
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('TBT AsignarTimbres excepción', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'No se pudo conectar con HUB CFDI: ' . $e->getMessage(),
            ], 500);
        }

        // TBT respondió ok, ahora sí sumar en BD local
        $shop->cfdi_timbres_contratados += $request->cantidad;
        $shop->save();

        $shop->cfdiEmisor->timbres_asignados = $shop->cfdi_timbres_contratados;
        $shop->cfdiEmisor->save();

        Log::info('Timbres asignados exitosamente', [
            'shop_id' => $shop->id,
            'rfc' => $shop->cfdiEmisor->rfc,
            'cantidad' => $request->cantidad,
            'total_contratados' => $shop->cfdi_timbres_contratados,
        ]);

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
     * Obtener facturas de todas las tiendas con filtros (JSON)
     */
    public function getFacturas(Request $request)
    {
        $fechaInicio = $request->fecha_inicio
            ? Carbon::parse($request->fecha_inicio)->startOfDay()
            : Carbon::now('America/Mexico_City')->startOfMonth()->startOfDay();
        $fechaFin = $request->fecha_fin
            ? Carbon::parse($request->fecha_fin)->endOfDay()
            : Carbon::now('America/Mexico_City')->endOfDay();

        $query = CfdiInvoice::with('shop:id,name')
            ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin]);

        if ($request->status && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }

        if ($request->shop_id) {
            $query->where('shop_id', $request->shop_id);
        }

        if ($request->buscar) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('receptor_rfc', 'like', "%{$buscar}%")
                  ->orWhere('receptor_nombre', 'like', "%{$buscar}%");
            });
        }

        $facturas = $query->orderBy('fecha_emision', 'desc')->get();

        $vigentes = $facturas->where('status', 'vigente');
        $canceladas = $facturas->where('status', 'cancelada');

        return response()->json([
            'ok' => true,
            'periodo' => $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'),
            'totales' => [
                'count' => $facturas->count(),
                'vigentes' => $vigentes->count(),
                'canceladas' => $canceladas->count(),
                'subtotal' => round($vigentes->sum('subtotal'), 2),
                'impuestos' => round($vigentes->sum('total_impuestos'), 2),
                'total' => round($vigentes->sum('total'), 2),
            ],
            'facturas' => $facturas->map(function ($f) {
                return [
                    'id' => $f->id,
                    'uuid' => $f->uuid,
                    'serie' => $f->serie,
                    'folio' => $f->folio,
                    'fecha_emision' => $f->fecha_emision ? $f->fecha_emision->format('d/m/Y H:i') : null,
                    'receptor_rfc' => $f->receptor_rfc,
                    'receptor_nombre' => $f->receptor_nombre,
                    'shop_name' => $f->shop ? $f->shop->name : '-',
                    'subtotal' => $f->subtotal,
                    'total_impuestos' => $f->total_impuestos,
                    'total' => $f->total,
                    'status' => $f->status,
                ];
            })->values(),
        ]);
    }

    /**
     * Exportar facturas de todas las tiendas a Excel
     */
    public function exportFacturas(Request $request)
    {
        $fechaInicio = $request->fecha_inicio ?: Carbon::now('America/Mexico_City')->startOfMonth()->format('Y-m-d');
        $fechaFin = $request->fecha_fin ?: Carbon::now('America/Mexico_City')->format('Y-m-d');
        $status = $request->status ?: 'todos';
        $shopId = $request->shop_id ?: null;

        return Excel::download(
            new FacturasEmitidasExport(null, $fechaInicio, $fechaFin, $status, $shopId),
            'facturas_todas_tiendas_' . date('Ymd') . '.xlsx'
        );
    }

    /**
     * Sincronizar: cruce interno BD + comparación global con TBT
     */
    public function sincronizarTimbres()
    {
        // 1. Obtener datos globales de TBT
        $tbtGlobal = null;
        $tbtError = null;
        try {
            $service = new HubCfdiService();
            $result = $service->obtenerTimbres();
            if ($result['success']) {
                $body = $result['data']['body'] ?? $result['data'];
                $tbtGlobal = [
                    'contratados' => $body['TimbresContratados'] ?? 0,
                    'consumidos' => $body['TimbresConsumidos'] ?? 0,
                    'disponibles' => $body['TimbresDisponibles'] ?? 0,
                ];
            } else {
                $tbtError = $result['error'] ?? 'Error al consultar TBT';
            }
        } catch (\Exception $e) {
            $tbtError = $e->getMessage();
        }

        // 2. Obtener tiendas con emisor
        $shops = Shop::with('cfdiEmisor')
            ->whereHas('cfdiEmisor')
            ->get();

        $tiendas = [];
        $totalProblemas = 0;

        foreach ($shops as $shop) {
            $emisor = $shop->cfdiEmisor;
            $facturasVigentes = CfdiInvoice::where('shop_id', $shop->id)
                ->where('status', 'vigente')->count();

            $problemas = [];

            // Check 1: shops.cfdi_timbres_contratados vs cfdi_emisores.timbres_asignados
            if ((int) $shop->cfdi_timbres_contratados !== (int) $emisor->timbres_asignados) {
                $problemas[] = [
                    'tipo' => 'asignados',
                    'desc' => "shops.cfdi_timbres_contratados ({$shop->cfdi_timbres_contratados}) ≠ cfdi_emisores.timbres_asignados ({$emisor->timbres_asignados})",
                    'valor_shop' => (int) $shop->cfdi_timbres_contratados,
                    'valor_emisor' => (int) $emisor->timbres_asignados,
                ];
            }

            // Check 2: cfdi_emisores.timbres_usados vs count(facturas vigentes)
            if ((int) $emisor->timbres_usados !== $facturasVigentes) {
                $problemas[] = [
                    'tipo' => 'usados',
                    'desc' => "cfdi_emisores.timbres_usados ({$emisor->timbres_usados}) ≠ facturas vigentes ({$facturasVigentes})",
                    'valor_emisor' => (int) $emisor->timbres_usados,
                    'valor_facturas' => $facturasVigentes,
                ];
            }

            $totalProblemas += count($problemas);

            $tiendas[] = [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'rfc' => $emisor->rfc,
                'contratados_shop' => (int) $shop->cfdi_timbres_contratados,
                'asignados_emisor' => (int) $emisor->timbres_asignados,
                'usados_emisor' => (int) $emisor->timbres_usados,
                'facturas_vigentes' => $facturasVigentes,
                'ok' => empty($problemas),
                'problemas' => $problemas,
            ];
        }

        // 3. Check global: asignados internos no deben exceder contratados TBT
        $sumAsignados = (int) Shop::sum('cfdi_timbres_contratados');
        $alertaGlobal = null;
        if ($tbtGlobal && $sumAsignados > $tbtGlobal['contratados']) {
            $alertaGlobal = "Total asignado a tiendas ({$sumAsignados}) excede los contratados en TBT ({$tbtGlobal['contratados']})";
            $totalProblemas++;
        }

        return response()->json([
            'ok' => true,
            'tbt_global' => $tbtGlobal,
            'tbt_error' => $tbtError,
            'alerta_global' => $alertaGlobal,
            'tiendas' => $tiendas,
            'total_problemas' => $totalProblemas,
        ]);
    }

    /**
     * Corregir discrepancia de una tienda específica
     */
    public function corregirTimbres(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'tipo' => 'required|in:asignados,usados',
        ]);

        $shop = Shop::with('cfdiEmisor')->findOrFail($request->shop_id);

        if (!$shop->cfdiEmisor) {
            return response()->json(['ok' => false, 'message' => 'La tienda no tiene emisor configurado.'], 422);
        }

        $emisor = $shop->cfdiEmisor;
        $mensaje = '';

        if ($request->tipo === 'asignados') {
            // Fuente de verdad: shops.cfdi_timbres_contratados (lo que el superadmin asignó)
            $antes = $emisor->timbres_asignados;
            $emisor->timbres_asignados = $shop->cfdi_timbres_contratados;
            $emisor->save();
            $mensaje = "timbres_asignados: {$antes} → {$shop->cfdi_timbres_contratados}";
        }

        if ($request->tipo === 'usados') {
            // Fuente de verdad: count de facturas vigentes
            $facturasVigentes = CfdiInvoice::where('shop_id', $shop->id)
                ->where('status', 'vigente')->count();
            $antes = $emisor->timbres_usados;
            $emisor->timbres_usados = $facturasVigentes;
            $emisor->save();
            $mensaje = "timbres_usados: {$antes} → {$facturasVigentes}";
        }

        Log::info('Corrección de timbres por sincronización', [
            'shop_id' => $shop->id,
            'tipo' => $request->tipo,
            'correccion' => $mensaje,
        ]);

        return response()->json([
            'ok' => true,
            'message' => "Corregido {$shop->name}: {$mensaje}",
        ]);
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

                // Totales internos (asignados a tiendas)
                $asignados = (int) Shop::sum('cfdi_timbres_contratados');
                $usados = (int) CfdiEmisor::sum('timbres_usados');

                return response()->json([
                    'ok' => true,
                    'data' => [
                        'contratados' => $body['TimbresContratados'] ?? 0,
                        'consumidos' => $body['TimbresConsumidos'] ?? 0,
                        'disponibles' => $body['TimbresDisponibles'] ?? 0,
                    ],
                    'internos' => [
                        'asignados' => $asignados,
                        'usados' => $usados,
                        'disponibles' => $asignados - $usados,
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
