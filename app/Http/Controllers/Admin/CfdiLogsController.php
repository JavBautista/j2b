<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CfdiTimbradoLog;
use Illuminate\Http\Request;

/**
 * Bitácora de timbrado: vista admin de cfdi_timbrado_logs.
 * Scope automático al shop del usuario.
 */
class CfdiLogsController extends Controller
{
    public function index()
    {
        return view('admin.cfdi.bitacora');
    }

    public function getLogs(Request $request)
    {
        $shop = auth()->user()->shop;

        if (!$shop) {
            return response()->json(['ok' => false, 'message' => 'Sin tienda asociada'], 403);
        }

        $query = CfdiTimbradoLog::where('shop_id', $shop->id)
            ->orderBy('id', 'desc');

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
            'data' => $paginated->getCollection()->map(fn($row) => $this->summarize($row)),
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
        $shop = auth()->user()->shop;

        $log = CfdiTimbradoLog::where('id', $id)
            ->where('shop_id', $shop->id)
            ->first();

        if (!$log) {
            return response()->json(['ok' => false, 'message' => 'Log no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => array_merge(
                $this->summarize($log),
                [
                    'request_payload' => $log->request_payload_decoded,
                    'response_payload' => $log->response_payload_decoded,
                    'attempts' => $log->attempts,
                    'metadata' => $log->metadata,
                    'error_code' => $log->error_code,
                    'error_message' => $log->error_message,
                ],
            ),
        ]);
    }

    private function summarize(CfdiTimbradoLog $row): array
    {
        return [
            'id' => $row->id,
            'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            'event_type' => $row->event_type,
            'status' => $row->status,
            'source' => $row->source,
            'pipeline' => $row->pipeline,
            'request_id' => $row->request_id,
            'cfdi_invoice_id' => $row->cfdi_invoice_id,
            'receipt_id' => $row->receipt_id,
            'uuid' => $row->uuid,
            'http_status' => $row->http_status,
            'duration_ms' => $row->duration_ms,
            'error_message' => $row->error_message,
        ];
    }
}
