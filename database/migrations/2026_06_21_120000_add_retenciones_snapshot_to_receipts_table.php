<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot de retenciones (ISR/IVA) al momento de crear la nota.
     * El total comercial (receipts.total) NO se toca: las retenciones se
     * exponen como valor derivado (saldoEfectivoEsperado) para la cobranza
     * y como default que el timbrado jala. Tasas en factor decimal (0.10 = 10%),
     * mismo criterio que cfdi_emisores.ret_*_default_tasa.
     * Ver xdev/ventas/PLAN_RETENCIONES_DESDE_VENTA.md (Fase 1, T1.1)
     */
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->boolean('aplica_retencion')->default(false)->after('tax_name');
            $table->decimal('ret_isr_tasa', 8, 6)->nullable()->after('aplica_retencion');
            $table->decimal('ret_isr_monto', 12, 2)->default(0)->after('ret_isr_tasa');
            $table->decimal('ret_iva_tasa', 8, 6)->nullable()->after('ret_isr_monto');
            $table->decimal('ret_iva_monto', 12, 2)->default(0)->after('ret_iva_tasa');
            $table->decimal('total_retenciones', 12, 2)->default(0)->after('ret_iva_monto');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn([
                'aplica_retencion',
                'ret_isr_tasa',
                'ret_isr_monto',
                'ret_iva_tasa',
                'ret_iva_monto',
                'total_retenciones',
            ]);
        });
    }
};
