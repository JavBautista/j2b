<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $user = $request->user();
        $shop_id = $user->shop_id;

        return response()->json([
            'ok' => true,
            'ventas_hoy' => $this->getVentasHoy($shop_id),
            'ventas_mes' => $this->getVentasMes($shop_id),
            'tareas' => $this->getTareas($shop_id),
            'adeudos' => $this->getAdeudos($shop_id),
            'stock_bajo' => $this->getStockBajo($shop_id),
            'usuarios' => $this->getUsuarios($shop_id),
            'tareas_por_colaborador' => $this->getTareasPorColaborador($shop_id),
            'cliente_top_incidencias' => $this->getClienteTopIncidencias($shop_id),
            'productos_mas_vendidos' => $this->getProductosMasVendidos($shop_id),
        ]);
    }

    private function getVentasHoy($shop_id)
    {
        try {
            $hoy = Carbon::today();

            $result = DB::table('receipts')
                ->where('shop_id', $shop_id)
                ->where('quotation', 0)
                ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION'])
                ->whereDate('created_at', $hoy)
                ->selectRaw('COALESCE(SUM(total), 0) as total, COUNT(*) as cantidad')
                ->first();

            $cantidad = (int) $result->cantidad;

            return [
                'total' => round((float) $result->total, 2),
                'cantidad' => $cantidad,
                'ticket_promedio' => $cantidad > 0 ? round((float) $result->total / $cantidad, 2) : 0,
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'cantidad' => 0, 'ticket_promedio' => 0];
        }
    }

    private function getVentasMes($shop_id)
    {
        try {
            $inicio = Carbon::now()->startOfMonth();
            $fin = Carbon::now()->endOfMonth();

            $result = DB::table('receipts')
                ->where('shop_id', $shop_id)
                ->where('quotation', 0)
                ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION'])
                ->whereBetween('created_at', [$inicio, $fin])
                ->selectRaw('COALESCE(SUM(total), 0) as total, COUNT(*) as cantidad')
                ->first();

            return [
                'total' => round((float) $result->total, 2),
                'cantidad' => (int) $result->cantidad,
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'cantidad' => 0];
        }
    }

    private function getTareas($shop_id)
    {
        try {
            $counts = DB::table('tasks')
                ->where('shop_id', $shop_id)
                ->whereIn('status', ['NUEVO', 'PENDIENTE', 'ATENDIDO'])
                ->selectRaw("
                    SUM(CASE WHEN status = 'NUEVO' THEN 1 ELSE 0 END) as nuevo,
                    SUM(CASE WHEN status = 'PENDIENTE' THEN 1 ELSE 0 END) as pendiente,
                    SUM(CASE WHEN status = 'ATENDIDO' THEN 1 ELSE 0 END) as atendido
                ")
                ->first();

            return [
                'nuevo' => (int) ($counts->nuevo ?? 0),
                'pendiente' => (int) ($counts->pendiente ?? 0),
                'atendido' => (int) ($counts->atendido ?? 0),
            ];
        } catch (\Exception $e) {
            return ['nuevo' => 0, 'pendiente' => 0, 'atendido' => 0];
        }
    }

    private function getAdeudos($shop_id)
    {
        try {
            $result = DB::table('receipts')
                ->leftJoin(
                    DB::raw('(SELECT receipt_id, COALESCE(SUM(amount), 0) as total_pagado FROM partial_payments GROUP BY receipt_id) as pp'),
                    'receipts.id', '=', 'pp.receipt_id'
                )
                ->where('receipts.shop_id', $shop_id)
                ->where('receipts.status', 'POR COBRAR')
                ->where('receipts.quotation', 0)
                ->selectRaw('
                    COALESCE(SUM(receipts.total - COALESCE(pp.total_pagado, 0)), 0) as total_adeudo,
                    COUNT(DISTINCT receipts.client_id) as num_clientes
                ')
                ->first();

            return [
                'total' => round((float) ($result->total_adeudo ?? 0), 2),
                'num_clientes' => (int) ($result->num_clientes ?? 0),
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'num_clientes' => 0];
        }
    }

    private function getStockBajo($shop_id)
    {
        try {
            $count = DB::table('products')
                ->where('shop_id', $shop_id)
                ->where('active', 1)
                ->where('stock', '>', 0)
                ->where('stock', '<=', 5)
                ->count();

            return ['count' => $count];
        } catch (\Exception $e) {
            return ['count' => 0];
        }
    }

    private function getUsuarios($shop_id)
    {
        try {
            $users = DB::table('users')
                ->join('role_user', 'users.id', '=', 'role_user.user_id')
                ->where('users.shop_id', $shop_id)
                ->where('users.active', 1)
                ->selectRaw("
                    SUM(CASE WHEN role_user.role_id IN (1,2) AND (users.limited = 0 OR users.limited IS NULL) THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN role_user.role_id IN (1,2) AND users.limited = 1 THEN 1 ELSE 0 END) as admins_limited,
                    SUM(CASE WHEN role_user.role_id = 4 THEN 1 ELSE 0 END) as colaboradores
                ")
                ->first();

            $clients = DB::table('clients')
                ->where('shop_id', $shop_id)
                ->where('active', 1)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) as con_app
                ")
                ->first();

            return [
                'admins' => (int) ($users->admins ?? 0),
                'admins_limited' => (int) ($users->admins_limited ?? 0),
                'colaboradores' => (int) ($users->colaboradores ?? 0),
                'clientes_total' => (int) ($clients->total ?? 0),
                'clientes_con_app' => (int) ($clients->con_app ?? 0),
            ];
        } catch (\Exception $e) {
            return [
                'admins' => 0, 'admins_limited' => 0, 'colaboradores' => 0,
                'clientes_total' => 0, 'clientes_con_app' => 0,
            ];
        }
    }

    private function getTareasPorColaborador($shop_id)
    {
        try {
            $inicioMes = Carbon::now()->startOfMonth();
            $inicioSemana = Carbon::now()->startOfWeek();

            $rows = DB::table('tasks')
                ->join('users', 'tasks.assigned_user_id', '=', 'users.id')
                ->where('tasks.shop_id', $shop_id)
                ->where('tasks.status', 'ATENDIDO')
                ->where('tasks.updated_at', '>=', $inicioMes)
                ->groupBy('tasks.assigned_user_id', 'users.name')
                ->selectRaw("
                    users.name,
                    COUNT(*) as total_mes,
                    SUM(CASE WHEN tasks.updated_at >= ? THEN 1 ELSE 0 END) as total_semana
                ", [$inicioSemana])
                ->orderByDesc('total_mes')
                ->limit(5)
                ->get();

            return $rows->map(fn($r) => [
                'nombre' => $r->name,
                'total_mes' => (int) $r->total_mes,
                'total_semana' => (int) $r->total_semana,
            ])->values()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getClienteTopIncidencias($shop_id)
    {
        try {
            $rows = DB::table('tasks')
                ->join('clients', 'tasks.client_id', '=', 'clients.id')
                ->where('tasks.shop_id', $shop_id)
                ->whereNotNull('tasks.client_id')
                ->groupBy('tasks.client_id', 'clients.name')
                ->selectRaw('clients.name, COUNT(*) as total')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            return $rows->map(fn($r) => [
                'nombre' => $r->name,
                'total' => (int) $r->total,
            ])->values()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getProductosMasVendidos($shop_id)
    {
        try {
            $rows = DB::table('receipt_details')
                ->join('receipts', 'receipt_details.receipt_id', '=', 'receipts.id')
                ->join('products', 'receipt_details.product_id', '=', 'products.id')
                ->where('receipts.shop_id', $shop_id)
                ->where('receipts.quotation', 0)
                ->whereNotIn('receipts.status', ['CANCELADA', 'DEVOLUCION'])
                ->where('receipt_details.type', 'product')
                ->groupBy('receipt_details.product_id', 'products.name')
                ->selectRaw('products.name, SUM(receipt_details.qty) as total_qty')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get();

            return $rows->map(fn($r) => [
                'nombre' => $r->name,
                'cantidad' => (int) $r->total_qty,
            ])->values()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
}
