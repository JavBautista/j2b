<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * Servicio de consultas estructuradas para el chat IA
 *
 * Ejecuta queries SQL predefinidas y seguras (sin SQL dinámico)
 * para responder preguntas sobre ventas, adeudos y métricas.
 *
 * Todas las consultas filtran por shop_id (multi-tenant).
 */
class QueryService
{
    // =========================================================================
    // CONSULTAS DE VENTAS
    // =========================================================================

    /**
     * Resumen de ventas para un periodo
     *
     * Responde: "cuánto vendimos hoy", "ventas del mes", etc.
     */
    public function salesSummary(int $shopId, string $period): ?object
    {
        [$start, $end] = $this->parsePeriod($period);

        try {
            $result = DB::table('receipts as r')
                ->where('r.shop_id', $shopId)
                ->where('r.quotation', 0)
                ->where('r.status', '<>', 'CANCELADA')
                ->whereBetween('r.created_at', [$start, $end])
                ->selectRaw("
                    COUNT(*) as total_notas,
                    COUNT(CASE WHEN r.type = 'venta' THEN 1 END) as notas_venta,
                    COUNT(CASE WHEN r.type = 'renta' THEN 1 END) as notas_renta,
                    COALESCE(SUM(r.total), 0) as monto_total,
                    COUNT(CASE WHEN r.status = 'PAGADA' THEN 1 END) as notas_pagadas,
                    COUNT(CASE WHEN r.status = 'POR COBRAR' THEN 1 END) as notas_por_cobrar
                ")
                ->first();

            // Calcular cobrado real desde partial_payments (consistente con ReportsController)
            $cobrado = DB::table('partial_payments as pp')
                ->join('receipts as r', 'pp.receipt_id', '=', 'r.id')
                ->where('r.shop_id', $shopId)
                ->where('r.quotation', 0)
                ->where('r.status', '<>', 'CANCELADA')
                ->whereBetween('r.created_at', [$start, $end])
                ->sum('pp.amount');

            $result->monto_cobrado = $cobrado;
            $result->monto_pendiente = $result->monto_total - $cobrado;

            Log::info('QueryService::salesSummary', [
                'shop_id' => $shopId,
                'period' => $period,
                'total_notas' => $result->total_notas,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('QueryService::salesSummary error', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Top productos más vendidos en un periodo
     *
     * Responde: "producto más vendido", "top ventas del mes"
     */
    public function topProducts(int $shopId, string $period, int $limit = 10): array
    {
        [$start, $end] = $this->parsePeriod($period);

        try {
            return DB::table('receipt_details as rd')
                ->join('receipts as r', 'rd.receipt_id', '=', 'r.id')
                ->where('r.shop_id', $shopId)
                ->where('r.quotation', 0)
                ->where('r.status', '<>', 'CANCELADA')
                ->whereBetween('r.created_at', [$start, $end])
                ->groupBy('rd.descripcion')
                ->selectRaw("
                    rd.descripcion as producto,
                    SUM(CAST(rd.qty AS DECIMAL(10,2))) as cantidad_vendida,
                    SUM(rd.subtotal) as total_vendido
                ")
                ->orderByDesc('total_vendido')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('QueryService::topProducts error', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // =========================================================================
    // CONSULTAS DE ADEUDOS
    // =========================================================================

    /**
     * Clientes con adeudos pendientes
     *
     * Responde: "a quién le cobro", "quién me debe"
     */
    public function clientDebts(int $shopId): array
    {
        try {
            return DB::table('receipts as r')
                ->join('clients as c', 'r.client_id', '=', 'c.id')
                ->leftJoin(DB::raw('(SELECT receipt_id, SUM(amount) as total_abonado FROM partial_payments GROUP BY receipt_id) as pp'), 'r.id', '=', 'pp.receipt_id')
                ->where('r.shop_id', $shopId)
                ->where('r.quotation', 0)
                ->where('r.status', 'POR COBRAR')
                ->groupBy('c.id', 'c.name', 'c.phone', 'c.email', 'c.company')
                ->havingRaw('SUM(r.total - COALESCE(pp.total_abonado, 0)) > 0')
                ->selectRaw("
                    c.id as client_id,
                    c.name as cliente,
                    c.company as empresa,
                    c.phone as telefono,
                    c.email as email,
                    COUNT(r.id) as notas_pendientes,
                    SUM(r.total) as total_facturado,
                    SUM(COALESCE(pp.total_abonado, 0)) as total_abonado,
                    SUM(r.total - COALESCE(pp.total_abonado, 0)) as adeudo_total,
                    MIN(r.created_at) as nota_mas_antigua
                ")
                ->orderByDesc('adeudo_total')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('QueryService::clientDebts error', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Rentas activas con información de cobro
     *
     * Responde: "cobros de renta", "rentas pendientes"
     */
    public function activeRentals(int $shopId): array
    {
        try {
            return DB::table('rents as re')
                ->join('clients as c', 're.client_id', '=', 'c.id')
                ->join('rent_details as rd', 're.id', '=', 'rd.rent_id')
                ->where('rd.shop_id', $shopId)
                ->where('re.active', 1)
                ->where('rd.active', 1)
                ->groupBy('c.id', 'c.name', 'c.phone', 're.id', 're.cutoff')
                ->selectRaw("
                    c.id as client_id,
                    c.name as cliente,
                    c.phone as telefono,
                    re.cutoff as dia_corte,
                    COUNT(rd.id) as equipos_rentados,
                    SUM(rd.rent_price) as renta_mensual
                ")
                ->orderByDesc('renta_mensual')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('QueryService::activeRentals error', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // =========================================================================
    // CONSULTAS DE GASTOS
    // =========================================================================

    /**
     * Resumen de gastos para un periodo
     *
     * Responde: "cuánto gastamos hoy", "gastos del mes"
     */
    public function expenseSummary(int $shopId, string $period): ?object
    {
        [$start, $end] = $this->parsePeriod($period);

        try {
            return DB::table('expenses')
                ->where('shop_id', $shopId)
                ->where('active', 1)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->selectRaw("
                    COUNT(*) as total_gastos,
                    COALESCE(SUM(total), 0) as monto_total,
                    COUNT(CASE WHEN is_tax_invoiced = 1 THEN 1 END) as facturados,
                    COUNT(CASE WHEN is_tax_invoiced = 0 THEN 1 END) as no_facturados,
                    COALESCE(SUM(CASE WHEN is_tax_invoiced = 1 THEN total ELSE 0 END), 0) as monto_facturado,
                    COALESCE(SUM(CASE WHEN is_tax_invoiced = 0 THEN total ELSE 0 END), 0) as monto_no_facturado
                ")
                ->first();
        } catch (\Exception $e) {
            Log::error('QueryService::expenseSummary error', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Top categorías de gastos por monto
     *
     * Responde: "en qué gastamos más", "top gastos"
     */
    public function topExpenseCategories(int $shopId, string $period, int $limit = 10): array
    {
        [$start, $end] = $this->parsePeriod($period);

        try {
            return DB::table('expenses')
                ->where('shop_id', $shopId)
                ->where('active', 1)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->whereNotNull('name')
                ->where('name', '<>', '')
                ->groupBy('name')
                ->selectRaw("
                    name as categoria,
                    COUNT(*) as cantidad,
                    COALESCE(SUM(total), 0) as total_gastado
                ")
                ->orderByDesc('total_gastado')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('QueryService::topExpenseCategories error', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Detectar si pregunta por categorías/top gastos
     */
    public function asksForTopExpenses(string $prompt): bool
    {
        $p = mb_strtolower(trim($prompt));
        $keywords = [
            'en que gastamos', 'en qué gastamos',
            'mayor gasto', 'mayores gastos',
            'mas gastamos', 'más gastamos',
            'top gastos', 'categorias de gastos', 'categorías de gastos',
        ];

        foreach ($keywords as $kw) {
            if (mb_strpos($p, $kw) !== false) return true;
        }

        return false;
    }

    // =========================================================================
    // CONSULTAS DE COMPRAS A PROVEEDORES
    // =========================================================================

    /**
     * Resumen de compras para un periodo
     *
     * Responde: "cuánto compramos este mes", "compras del mes"
     */
    public function purchaseSummary(int $shopId, string $period): ?object
    {
        [$start, $end] = $this->parsePeriod($period);

        try {
            return DB::table('purchase_orders as po')
                ->where('po.shop_id', $shopId)
                ->whereBetween('po.created_at', [$start, $end])
                ->selectRaw("
                    COUNT(*) as total_ordenes,
                    COALESCE(SUM(po.total), 0) as monto_total,
                    COUNT(CASE WHEN po.payable = 1 THEN 1 END) as ordenes_por_pagar,
                    COUNT(CASE WHEN po.payable = 0 THEN 1 END) as ordenes_pagadas,
                    COUNT(CASE WHEN po.is_tax_invoiced = 1 THEN 1 END) as facturadas
                ")
                ->first();
        } catch (\Exception $e) {
            Log::error('QueryService::purchaseSummary error', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Deudas con proveedores (lo que debemos nosotros)
     *
     * Responde: "a quién le debo", "deudas con proveedores"
     */
    public function supplierDebts(int $shopId): array
    {
        try {
            return DB::table('purchase_orders as po')
                ->join('suppliers as s', 'po.supplier_id', '=', 's.id')
                ->leftJoin(DB::raw('(SELECT purchase_order_id, SUM(amount) as total_pagado FROM purchase_order_partial_payments GROUP BY purchase_order_id) as pp'), 'po.id', '=', 'pp.purchase_order_id')
                ->where('po.shop_id', $shopId)
                ->where('po.payable', 1)
                ->groupBy('s.id', 's.name', 's.company', 's.phone', 's.email')
                ->havingRaw('SUM(po.total - COALESCE(pp.total_pagado, 0)) > 0')
                ->selectRaw("
                    s.id as supplier_id,
                    s.name as proveedor,
                    s.company as empresa,
                    s.phone as telefono,
                    s.email as email,
                    COUNT(po.id) as ordenes_pendientes,
                    SUM(po.total) as total_comprado,
                    SUM(COALESCE(pp.total_pagado, 0)) as total_pagado,
                    SUM(po.total - COALESCE(pp.total_pagado, 0)) as deuda_total
                ")
                ->orderByDesc('deuda_total')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('QueryService::supplierDebts error', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Top proveedores por monto de compra
     *
     * Responde: "proveedor al que más le compro", "top proveedores"
     */
    public function topSuppliers(int $shopId, string $period, int $limit = 10): array
    {
        [$start, $end] = $this->parsePeriod($period);

        try {
            return DB::table('purchase_orders as po')
                ->join('suppliers as s', 'po.supplier_id', '=', 's.id')
                ->where('po.shop_id', $shopId)
                ->whereBetween('po.created_at', [$start, $end])
                ->groupBy('s.id', 's.name', 's.company')
                ->selectRaw("
                    s.name as proveedor,
                    s.company as empresa,
                    COUNT(po.id) as total_ordenes,
                    COALESCE(SUM(po.total), 0) as total_comprado
                ")
                ->orderByDesc('total_comprado')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('QueryService::topSuppliers error', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Detectar si pregunta por deudas con proveedores
     */
    public function asksForSupplierDebts(string $prompt): bool
    {
        $p = mb_strtolower(trim($prompt));
        $keywords = [
            'le debo', 'les debo', 'debo a proveedor', 'deuda con proveedor',
            'deudas con proveedores', 'cuentas por pagar', 'por pagar',
            'a quien le debo', 'a quién le debo',
        ];

        foreach ($keywords as $kw) {
            if (mb_strpos($p, $kw) !== false) return true;
        }

        return false;
    }

    /**
     * Detectar si pregunta por top proveedores
     */
    public function asksForTopSuppliers(string $prompt): bool
    {
        $p = mb_strtolower(trim($prompt));
        $keywords = [
            'proveedor al que mas', 'proveedor al que más',
            'mas le compro', 'más le compro',
            'top proveedor', 'top proveedores',
            'mayor compra', 'mayores compras',
        ];

        foreach ($keywords as $kw) {
            if (mb_strpos($p, $kw) !== false) return true;
        }

        return false;
    }

    // =========================================================================
    // CONSULTAS DE HISTORIAL DE CLIENTE
    // =========================================================================

    /**
     * Buscar cliente por nombre extraído del prompt
     *
     * Extrae el nombre del patrón detectado y busca en la BD
     */
    public function findClientByName(int $shopId, string $prompt): ?object
    {
        $normalized = mb_strtolower(trim($prompt));

        $patterns = [
            'que ha comprado ', 'qué ha comprado ',
            'cuanto ha comprado ', 'cuánto ha comprado ',
            'cuanto me ha comprado ', 'cuánto me ha comprado ',
            'cuanto nos ha comprado ', 'cuánto nos ha comprado ',
            'historial de compras de ', 'historial de compra de ',
            'historial del cliente ',
            'compras de ', 'ventas a ', 'ventas al cliente ',
        ];

        $clientName = null;
        foreach ($patterns as $pattern) {
            $pos = mb_strpos($normalized, $pattern);
            if ($pos !== false) {
                $clientName = trim(mb_substr($normalized, $pos + mb_strlen($pattern)));
                // Limpiar signos de interrogación y puntuación final
                $clientName = rtrim($clientName, '?¿.!');
                break;
            }
        }

        if (empty($clientName)) {
            return null;
        }

        try {
            return DB::table('clients')
                ->where('shop_id', $shopId)
                ->where('active', 1)
                ->where('name', 'like', '%' . $clientName . '%')
                ->select('id', 'name', 'company', 'phone', 'email')
                ->first();
        } catch (\Exception $e) {
            Log::error('QueryService::findClientByName error', [
                'shop_id' => $shopId,
                'name' => $clientName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Resumen de un cliente específico (total comprado, adeudo, etc.)
     */
    public function clientSummary(int $shopId, int $clientId): ?object
    {
        try {
            $summary = DB::table('receipts as r')
                ->where('r.shop_id', $shopId)
                ->where('r.client_id', $clientId)
                ->where('r.quotation', 0)
                ->where('r.status', '<>', 'CANCELADA')
                ->selectRaw("
                    COUNT(*) as total_notas,
                    COALESCE(SUM(r.total), 0) as total_comprado,
                    COUNT(CASE WHEN r.status = 'POR COBRAR' THEN 1 END) as notas_pendientes,
                    MAX(r.created_at) as ultima_compra
                ")
                ->first();

            // Calcular abonado real desde partial_payments
            $cobrado = DB::table('partial_payments as pp')
                ->join('receipts as r', 'pp.receipt_id', '=', 'r.id')
                ->where('r.shop_id', $shopId)
                ->where('r.client_id', $clientId)
                ->where('r.quotation', 0)
                ->where('r.status', '<>', 'CANCELADA')
                ->sum('pp.amount');

            $summary->total_cobrado = $cobrado;
            $summary->adeudo = $summary->total_comprado - $cobrado;

            return $summary;
        } catch (\Exception $e) {
            Log::error('QueryService::clientSummary error', [
                'shop_id' => $shopId,
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Últimas compras de un cliente
     */
    public function clientPurchaseHistory(int $shopId, int $clientId, string $period = 'this_year'): array
    {
        [$start, $end] = $this->parsePeriod($period);

        try {
            return DB::table('receipts as r')
                ->where('r.shop_id', $shopId)
                ->where('r.client_id', $clientId)
                ->where('r.quotation', 0)
                ->where('r.status', '<>', 'CANCELADA')
                ->whereBetween('r.created_at', [$start, $end])
                ->selectRaw("
                    r.id,
                    r.folio,
                    r.type,
                    r.status,
                    r.total,
                    r.created_at as fecha
                ")
                ->orderByDesc('r.created_at')
                ->limit(20)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('QueryService::clientPurchaseHistory error', [
                'shop_id' => $shopId,
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Formatear historial de cliente como texto para el LLM
     */
    public function formatClientHistory(object $client, ?object $summary, array $history): string
    {
        $empresa = $client->company ? " ({$client->company})" : '';
        $text = "[HISTORIAL DEL CLIENTE: {$client->name}{$empresa}]\n";

        if ($summary && $summary->total_notas > 0) {
            $text .= "Total comprado: $" . number_format($summary->total_comprado, 2) . "\n";
            $text .= "Total cobrado: $" . number_format($summary->total_cobrado, 2) . "\n";
            $text .= "Adeudo pendiente: $" . number_format($summary->adeudo, 2) . "\n";
            $text .= "Total de notas: {$summary->total_notas}\n";
            $text .= "Notas pendientes: {$summary->notas_pendientes}\n";
            if ($summary->ultima_compra) {
                $text .= "Última compra: " . Carbon::parse($summary->ultima_compra)->format('d/m/Y') . "\n";
            }
        } else {
            $text .= "Este cliente no tiene compras registradas.\n";
        }

        if (!empty($history)) {
            $text .= "\nÚltimas compras:\n";
            foreach ($history as $h) {
                $fecha = Carbon::parse($h->fecha)->format('d/m/Y');
                $total = number_format($h->total, 2);
                $folio = $h->folio ? "#{$h->folio}" : "#{$h->id}";
                $text .= "- {$folio} | {$fecha} | {$h->type} | {$h->status} | \${$total}\n";
            }
        }

        $text .= "[FIN HISTORIAL CLIENTE]";

        return $text;
    }

    // =========================================================================
    // DETECCION DE PERIODO
    // =========================================================================

    /**
     * Detectar periodo temporal del texto del usuario
     */
    public function detectPeriod(string $prompt): string
    {
        $p = mb_strtolower(trim($prompt));

        if (str_contains($p, 'ayer')) return 'yesterday';
        if (str_contains($p, 'hoy') || str_contains($p, 'dia de hoy') || str_contains($p, 'día de hoy')) return 'today';
        if (str_contains($p, 'semana pasada') || str_contains($p, 'semana anterior')) return 'last_week';
        if (str_contains($p, 'esta semana') || str_contains($p, 'semana actual')) return 'this_week';
        if (str_contains($p, 'mes pasado') || str_contains($p, 'mes anterior')) return 'last_month';
        if (str_contains($p, 'este mes') || str_contains($p, 'mes actual')) return 'this_month';
        if (str_contains($p, 'este año') || str_contains($p, 'este ano') || str_contains($p, 'año actual')) return 'this_year';

        // Default
        return 'today';
    }

    /**
     * Detectar si la pregunta pide top/ranking de productos
     */
    public function asksForTopProducts(string $prompt): bool
    {
        $p = mb_strtolower(trim($prompt));
        $keywords = [
            'mas vendido', 'más vendido', 'mejor vendido',
            'top producto', 'top ventas', 'producto estrella',
            'que se vende mas', 'qué se vende más',
            'productos vendidos',
        ];

        foreach ($keywords as $kw) {
            if (mb_strpos($p, $kw) !== false) return true;
        }

        return false;
    }

    // =========================================================================
    // FORMATEADORES PARA CONTEXTO LLM
    // =========================================================================

    /**
     * Formatear resumen de ventas como texto para el LLM
     */
    public function formatSalesSummary(?object $data, string $period): string
    {
        if (!$data || $data->total_notas == 0) {
            $label = $this->getPeriodLabel($period);
            return "[RESUMEN DE VENTAS - {$label}]\nNo hay ventas registradas en este periodo.\n[FIN RESUMEN VENTAS]";
        }

        $label = $this->getPeriodLabel($period);

        return "[RESUMEN DE VENTAS - {$label}]\n"
            . "Total de notas: {$data->total_notas}\n"
            . "- Ventas: {$data->notas_venta}\n"
            . "- Rentas: {$data->notas_renta}\n"
            . "Monto total: $" . number_format($data->monto_total, 2) . "\n"
            . "Cobrado: $" . number_format($data->monto_cobrado, 2) . "\n"
            . "Pendiente de cobro: $" . number_format($data->monto_pendiente, 2) . "\n"
            . "Notas pagadas: {$data->notas_pagadas}\n"
            . "Notas por cobrar: {$data->notas_por_cobrar}\n"
            . "[FIN RESUMEN VENTAS]";
    }

    /**
     * Formatear top productos como texto para el LLM
     */
    public function formatTopProducts(array $products): string
    {
        if (empty($products)) {
            return "[TOP PRODUCTOS VENDIDOS]\nNo hay datos de productos vendidos en este periodo.\n[FIN TOP PRODUCTOS]";
        }

        $text = "[TOP PRODUCTOS VENDIDOS]\n";
        foreach ($products as $i => $p) {
            $num = $i + 1;
            $qty = number_format($p->cantidad_vendida, 0);
            $total = number_format($p->total_vendido, 2);
            $text .= "{$num}. {$p->producto} | Cantidad: {$qty} | Total: \${$total}\n";
        }
        $text .= "[FIN TOP PRODUCTOS]";

        return $text;
    }

    /**
     * Formatear adeudos de clientes como texto para el LLM
     */
    public function formatClientDebts(array $debts): string
    {
        if (empty($debts)) {
            return "[ADEUDOS DE CLIENTES]\nNo hay clientes con adeudos pendientes.\n[FIN ADEUDOS]";
        }

        $totalDeuda = 0;
        $text = "[ADEUDOS DE CLIENTES]\n";
        foreach ($debts as $d) {
            $adeudo = number_format($d->adeudo_total, 2);
            $telefono = $d->telefono ?: 'Sin teléfono';
            $empresa = $d->empresa ? " ({$d->empresa})" : '';
            $text .= "- {$d->cliente}{$empresa} | Tel: {$telefono} | "
                . "Notas pendientes: {$d->notas_pendientes} | "
                . "Adeudo: \${$adeudo}\n";
            $totalDeuda += $d->adeudo_total;
        }
        $text .= "Total general de adeudos: $" . number_format($totalDeuda, 2) . "\n";
        $text .= "[FIN ADEUDOS]";

        return $text;
    }

    /**
     * Formatear rentas activas como texto para el LLM
     */
    public function formatActiveRentals(array $rentals): string
    {
        if (empty($rentals)) {
            return "[RENTAS ACTIVAS]\nNo hay rentas activas registradas.\n[FIN RENTAS]";
        }

        $totalMensual = 0;
        $text = "[RENTAS ACTIVAS]\n";
        foreach ($rentals as $r) {
            $renta = number_format($r->renta_mensual, 2);
            $telefono = $r->telefono ?: 'Sin teléfono';
            $corte = $r->dia_corte ?: 'Sin definir';
            $text .= "- {$r->cliente} | Tel: {$telefono} | "
                . "Equipos: {$r->equipos_rentados} | "
                . "Renta mensual: \${$renta} | "
                . "Día de corte: {$corte}\n";
            $totalMensual += $r->renta_mensual;
        }
        $text .= "Total rentas mensuales: $" . number_format($totalMensual, 2) . "\n";
        $text .= "[FIN RENTAS]";

        return $text;
    }

    /**
     * Formatear resumen de compras como texto para el LLM
     */
    public function formatPurchaseSummary(?object $data, string $period): string
    {
        if (!$data || $data->total_ordenes == 0) {
            $label = $this->getPeriodLabel($period);
            return "[RESUMEN DE COMPRAS - {$label}]\nNo hay órdenes de compra registradas en este periodo.\n[FIN RESUMEN COMPRAS]";
        }

        $label = $this->getPeriodLabel($period);

        return "[RESUMEN DE COMPRAS - {$label}]\n"
            . "Total de órdenes: {$data->total_ordenes}\n"
            . "Monto total: $" . number_format($data->monto_total, 2) . "\n"
            . "Órdenes pagadas: {$data->ordenes_pagadas}\n"
            . "Órdenes por pagar: {$data->ordenes_por_pagar}\n"
            . "Facturadas: {$data->facturadas}\n"
            . "[FIN RESUMEN COMPRAS]";
    }

    /**
     * Formatear deudas con proveedores como texto para el LLM
     */
    public function formatSupplierDebts(array $debts): string
    {
        if (empty($debts)) {
            return "[DEUDAS CON PROVEEDORES]\nNo hay deudas pendientes con proveedores.\n[FIN DEUDAS PROVEEDORES]";
        }

        $totalDeuda = 0;
        $text = "[DEUDAS CON PROVEEDORES]\n";
        foreach ($debts as $d) {
            $deuda = number_format($d->deuda_total, 2);
            $telefono = $d->telefono ?: 'Sin teléfono';
            $empresa = $d->empresa ? " ({$d->empresa})" : '';
            $text .= "- {$d->proveedor}{$empresa} | Tel: {$telefono} | "
                . "Órdenes pendientes: {$d->ordenes_pendientes} | "
                . "Deuda: \${$deuda}\n";
            $totalDeuda += $d->deuda_total;
        }
        $text .= "Total general de deudas: $" . number_format($totalDeuda, 2) . "\n";
        $text .= "[FIN DEUDAS PROVEEDORES]";

        return $text;
    }

    /**
     * Formatear top proveedores como texto para el LLM
     */
    public function formatTopSuppliers(array $suppliers): string
    {
        if (empty($suppliers)) {
            return "[TOP PROVEEDORES]\nNo hay datos de proveedores en este periodo.\n[FIN TOP PROVEEDORES]";
        }

        $text = "[TOP PROVEEDORES]\n";
        foreach ($suppliers as $i => $s) {
            $num = $i + 1;
            $total = number_format($s->total_comprado, 2);
            $empresa = $s->empresa ? " ({$s->empresa})" : '';
            $text .= "{$num}. {$s->proveedor}{$empresa} | Órdenes: {$s->total_ordenes} | Total: \${$total}\n";
        }
        $text .= "[FIN TOP PROVEEDORES]";

        return $text;
    }

    /**
     * Formatear resumen de gastos como texto para el LLM
     */
    public function formatExpenseSummary(?object $data, string $period): string
    {
        if (!$data || $data->total_gastos == 0) {
            $label = $this->getPeriodLabel($period);
            return "[RESUMEN DE GASTOS - {$label}]\nNo hay gastos registrados en este periodo.\n[FIN RESUMEN GASTOS]";
        }

        $label = $this->getPeriodLabel($period);

        return "[RESUMEN DE GASTOS - {$label}]\n"
            . "Total de gastos: {$data->total_gastos}\n"
            . "Monto total: $" . number_format($data->monto_total, 2) . "\n"
            . "Facturados: {$data->facturados} ($" . number_format($data->monto_facturado, 2) . ")\n"
            . "No facturados: {$data->no_facturados} ($" . number_format($data->monto_no_facturado, 2) . ")\n"
            . "[FIN RESUMEN GASTOS]";
    }

    /**
     * Formatear top categorías de gastos como texto para el LLM
     */
    public function formatTopExpenseCategories(array $categories): string
    {
        if (empty($categories)) {
            return "[TOP CATEGORIAS DE GASTOS]\nNo hay datos de categorías en este periodo.\n[FIN TOP GASTOS]";
        }

        $total = 0;
        $text = "[TOP CATEGORIAS DE GASTOS]\n";
        foreach ($categories as $i => $c) {
            $num = $i + 1;
            $monto = number_format($c->total_gastado, 2);
            $text .= "{$num}. {$c->categoria} | Gastos: {$c->cantidad} | Total: \${$monto}\n";
            $total += $c->total_gastado;
        }
        $text .= "Total general: $" . number_format($total, 2) . "\n";
        $text .= "[FIN TOP GASTOS]";

        return $text;
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    /**
     * Convertir periodo a rango de fechas [start, end]
     */
    private function parsePeriod(string $period): array
    {
        return match ($period) {
            'today'      => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'yesterday'  => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'this_week'  => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'last_week'  => [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'last_month' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            'this_year'  => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default      => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
        };
    }

    /**
     * Etiqueta legible del periodo
     */
    private function getPeriodLabel(string $period): string
    {
        return match ($period) {
            'today'      => 'HOY (' . Carbon::today()->format('d/m/Y') . ')',
            'yesterday'  => 'AYER (' . Carbon::yesterday()->format('d/m/Y') . ')',
            'this_week'  => 'ESTA SEMANA (' . Carbon::now()->startOfWeek()->format('d/m') . ' - ' . Carbon::now()->endOfWeek()->format('d/m') . ')',
            'last_week'  => 'SEMANA PASADA (' . Carbon::now()->subWeek()->startOfWeek()->format('d/m') . ' - ' . Carbon::now()->subWeek()->endOfWeek()->format('d/m') . ')',
            'this_month' => 'ESTE MES (' . Carbon::now()->translatedFormat('F Y') . ')',
            'last_month' => 'MES PASADO (' . Carbon::now()->subMonth()->translatedFormat('F Y') . ')',
            'this_year'  => 'ESTE AÑO (' . Carbon::now()->format('Y') . ')',
            default      => 'HOY',
        };
    }
}
