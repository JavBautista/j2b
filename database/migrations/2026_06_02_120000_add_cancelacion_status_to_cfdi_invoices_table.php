<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado del proceso de cancelación, separado del binario `status` (vigente|cancelada).
     * `status` solo pasa a 'cancelada' cuando la cancelación es final (aceptada).
     * Ver xdev/facturacion/PLAN_CANCELACION_SAT.md §1.2 y PLAN_CANCELACION_FIX_MINIMO.md.
     */
    public function up(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            $table->enum('cancelacion_status', [
                'en_proceso',
                'aceptada',
                'rechazada_receptor',
                'rechazada_sat',
            ])->nullable()->default(null)->after('motivo_cancelacion');
            $table->string('folio_sustitucion', 36)->nullable()->after('cancelacion_status');
            $table->dateTime('fecha_solicitud_cancelacion')->nullable()->after('folio_sustitucion');
            // String crudo del SAT: "Cancelado sin aceptacion" / "Cancelado con aceptacion" /
            // "En proceso" / "Plazo vencido" / "" (vacío si vigente).
            $table->string('estatus_cancelacion_sat', 64)->nullable()->after('fecha_solicitud_cancelacion');
            // EstatusUUID numérico SAT (201..205) — útil para soporte.
            $table->string('estatus_uuid_sat', 8)->nullable()->after('estatus_cancelacion_sat');
            $table->unsignedInteger('intentos_consulta_cancelacion')->default(0)->after('estatus_uuid_sat');
            $table->dateTime('ultima_consulta_cancelacion')->nullable()->after('intentos_consulta_cancelacion');
            $table->index(['cancelacion_status', 'ultima_consulta_cancelacion'], 'idx_cancelacion_pendiente');
        });

        // Backfill suave: históricos ya cancelados se asumen aceptados.
        DB::table('cfdi_invoices')
            ->where('status', 'cancelada')
            ->whereNull('cancelacion_status')
            ->update([
                'cancelacion_status' => 'aceptada',
                'fecha_solicitud_cancelacion' => DB::raw('COALESCE(fecha_cancelacion, updated_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            $table->dropIndex('idx_cancelacion_pendiente');
            $table->dropColumn([
                'cancelacion_status',
                'folio_sustitucion',
                'fecha_solicitud_cancelacion',
                'estatus_cancelacion_sat',
                'estatus_uuid_sat',
                'intentos_consulta_cancelacion',
                'ultima_consulta_cancelacion',
            ]);
        });
    }
};
