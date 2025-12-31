<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migración de datos históricos
     *
     * Crea registros en partial_payments para notas que:
     * - Tienen finished = 1 (pagadas completamente)
     * - No tienen registros en partial_payments
     * - No son cotizaciones
     * - No están canceladas ni son devoluciones
     * - Tienen received > 0
     *
     * Esto permite que los reportes solo consulten partial_payments
     * como única fuente de verdad para ingresos.
     *
     * Documentación: j2b-app/xdev/ventas/PLAN_CENTRALIZACION_PAGOS.md
     */
    public function up(): void
    {
        // Insertar partial_payments para notas históricas pagadas sin registro de pago
        DB::statement("
            INSERT INTO partial_payments (receipt_id, amount, payment_type, payment_date, created_at, updated_at)
            SELECT
                r.id,
                LEAST(r.received, r.total),
                'unico',
                DATE(r.created_at),
                r.created_at,
                NOW()
            FROM receipts r
            WHERE r.finished = 1
            AND r.quotation = 0
            AND r.status NOT IN ('CANCELADA', 'DEVOLUCION')
            AND r.received > 0
            AND NOT EXISTS (
                SELECT 1 FROM partial_payments pp WHERE pp.receipt_id = r.id
            )
        ");
    }

    /**
     * Reverse the migrations.
     *
     * Nota: No eliminamos los registros creados porque podrían mezclarse
     * con pagos legítimos creados después de la migración.
     * En caso de rollback, se recomienda restaurar desde backup.
     */
    public function down(): void
    {
        // No hacemos nada en down para evitar eliminar datos legítimos
        // Si se necesita rollback, restaurar desde backup de BD
    }
};
