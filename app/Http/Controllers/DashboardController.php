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
            'ventas_mes' => $this->getVentasMes($shop_id),
            'tareas' => $this->getTareas($shop_id),
            'servicios' => $this->getServicios($shop_id),
            'adeudos' => $this->getAdeudos($shop_id),
            'stock_bajo' => $this->getStockBajo($shop_id),
            'usuarios' => $this->getUsuarios($shop_id),
        ]);
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

    private function getServicios($shop_id)
    {
        try {
            $counts = DB::table('client_services')
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
                ->leftJoin('partial_payments', 'receipts.id', '=', 'partial_payments.receipt_id')
                ->where('receipts.shop_id', $shop_id)
                ->where('receipts.status', 'POR COBRAR')
                ->where('receipts.quotation', 0)
                ->selectRaw('
                    COALESCE(SUM(receipts.total), 0) - COALESCE(SUM(partial_payments.amount), 0) as total_adeudo,
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
}
