<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CfdiInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RetencionesReporteExport;

class RetencionesReporteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $shop = auth()->user()->shop;
        if (!$shop || !$shop->cfdi_enabled) {
            return redirect('/admin')->with('error', 'CFDI no habilitado');
        }
        return view('admin.reportes.retenciones');
    }

    /**
     * GET /admin/reportes/retenciones/data?mes=YYYY-MM
     * o ?fecha_inicio=...&fecha_fin=...
     */
    public function data(Request $request)
    {
        $shop = auth()->user()->shop;
        if (!$shop || !$shop->cfdi_enabled) {
            return response()->json(['ok' => false, 'message' => 'CFDI no habilitado'], 403);
        }

        if ($request->mes && preg_match('/^\d{4}-\d{2}$/', $request->mes)) {
            $base = Carbon::createFromFormat('Y-m', $request->mes, 'America/Mexico_City');
            $fechaInicio = $base->copy()->startOfMonth();
            $fechaFin = $base->copy()->endOfMonth();
        } else {
            $fechaInicio = $request->fecha_inicio
                ? Carbon::parse($request->fecha_inicio)->startOfDay()
                : Carbon::now('America/Mexico_City')->startOfMonth();
            $fechaFin = $request->fecha_fin
                ? Carbon::parse($request->fecha_fin)->endOfDay()
                : Carbon::now('America/Mexico_City')->endOfDay();
        }

        $facturas = CfdiInvoice::where('shop_id', $shop->id)
            ->where('status', 'vigente')
            ->where('total_retenciones', '>', 0)
            ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
            ->with('retenciones')
            ->orderBy('fecha_emision', 'desc')
            ->get();

        $totalIsr = 0;
        $totalIva = 0;
        $totalGeneral = 0;
        $rows = [];

        foreach ($facturas as $f) {
            $retIsr = (float) $f->retenciones()->whereNull('concepto_index')->where('impuesto', '001')->sum('importe');
            $retIva = (float) $f->retenciones()->whereNull('concepto_index')->where('impuesto', '002')->sum('importe');

            $totalIsr += $retIsr;
            $totalIva += $retIva;
            $totalGeneral += (float) $f->total_retenciones;

            $rows[] = [
                'id' => $f->id,
                'uuid' => $f->uuid,
                'serie_folio' => $f->serie . $f->folio,
                'fecha_emision' => $f->fecha_emision ? $f->fecha_emision->format('d/m/Y') : null,
                'receptor_rfc' => $f->receptor_rfc,
                'receptor_nombre' => $f->receptor_nombre,
                'subtotal' => (float) $f->subtotal,
                'total_impuestos' => (float) $f->total_impuestos,
                'ret_isr' => round($retIsr, 2),
                'ret_iva' => round($retIva, 2),
                'total_retenciones' => (float) $f->total_retenciones,
                'total' => (float) $f->total,
            ];
        }

        // Top clientes (top 5 por total retenciones)
        $topClientes = collect($rows)
            ->groupBy('receptor_rfc')
            ->map(function ($group) {
                return [
                    'receptor_rfc' => $group->first()['receptor_rfc'],
                    'receptor_nombre' => $group->first()['receptor_nombre'],
                    'count' => $group->count(),
                    'total_retenciones' => round($group->sum('total_retenciones'), 2),
                ];
            })
            ->sortByDesc('total_retenciones')
            ->take(5)
            ->values();

        return response()->json([
            'ok' => true,
            'periodo' => $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'),
            'totales' => [
                'count' => count($rows),
                'total_isr' => round($totalIsr, 2),
                'total_iva' => round($totalIva, 2),
                'total_general' => round($totalGeneral, 2),
            ],
            'top_clientes' => $topClientes,
            'facturas' => $rows,
        ]);
    }

    public function export(Request $request)
    {
        $shop = auth()->user()->shop;
        if (!$shop || !$shop->cfdi_enabled) {
            return redirect('/admin')->with('error', 'CFDI no habilitado');
        }

        if ($request->mes && preg_match('/^\d{4}-\d{2}$/', $request->mes)) {
            $base = Carbon::createFromFormat('Y-m', $request->mes, 'America/Mexico_City');
            $fechaInicio = $base->copy()->startOfMonth()->format('Y-m-d');
            $fechaFin = $base->copy()->endOfMonth()->format('Y-m-d');
        } else {
            $fechaInicio = $request->fecha_inicio ?: Carbon::now('America/Mexico_City')->startOfMonth()->format('Y-m-d');
            $fechaFin = $request->fecha_fin ?: Carbon::now('America/Mexico_City')->format('Y-m-d');
        }

        return Excel::download(
            new RetencionesReporteExport($shop, $fechaInicio, $fechaFin),
            'reporte_retenciones_' . $fechaInicio . '_' . $fechaFin . '.xlsx'
        );
    }
}
