<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\CfdiTimbradoLog;
use Illuminate\Http\Request;

/**
 * Bitácora global de timbrado para superadmin (sin scope por shop).
 * Misma estructura que Admin\CfdiLogsController pero ve todas las tiendas.
 */
class CfdiLogsController extends Controller
{
    public function getLogs(Request $request)
    {
        $query = CfdiTimbradoLog::orderBy('id', 'desc');

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }

        if ($request->filled('source') && $request->source !== 'todos') {
            $query->where('source', $request->source);
        }

        if ($request->filled('event_type') && $request->event_type !== 'todos') {
            $query->where('event_type', 'like', $request->event_type . '%');
        }

        if ($request->filled('fecha_inicio')) {
            $query->where('created_at', '>=', $request->fecha_inicio . ' 00:00:00');
        }

        if ($request->filled('fecha_fin')) {
            $query->where('created_at', '<=', $request->fecha_fin . ' 23:59:59');
        }

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);
            $query->where(function ($q) use ($buscar) {
                $q->where('uuid', 'like', "%{$buscar}%")
                  ->orWhere('request_id', $buscar)
                  ->orWhere('error_message', 'like', "%{$buscar}%");
            });
        }

        $perPage = min((int) $request->get('per_page', 50), 200);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'ok' => true,
            'data' => $paginated->getCollection(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function show($id)
    {
        $log = CfdiTimbradoLog::find($id);
        if (!$log) {
            return response()->json(['ok' => false, 'message' => 'Log no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => array_merge($log->toArray(), [
                'request_payload' => $log->request_payload_decoded,
                'response_payload' => $log->response_payload_decoded,
            ]),
        ]);
    }
}
