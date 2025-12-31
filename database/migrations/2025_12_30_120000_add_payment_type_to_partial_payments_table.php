<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migración para centralizar ingresos en partial_payments
     *
     * Agrega campo payment_type para identificar el tipo de pago:
     * - 'unico': Pago completo en una sola exhibición (al crear nota)
     * - 'inicial': Primer pago parcial (al crear nota, received < total)
     * - 'abono': Pago posterior parcial (suma < total)
     * - 'liquidacion': Pago que completa el total (suma >= total)
     *
     * Documentación: j2b-app/xdev/ventas/PLAN_CENTRALIZACION_PAGOS.md
     */
    public function up(): void
    {
        Schema::table('partial_payments', function (Blueprint $table) {
            $table->enum('payment_type', ['unico', 'inicial', 'abono', 'liquidacion'])
                  ->default('abono')
                  ->after('amount')
                  ->comment('Tipo de pago: unico, inicial, abono, liquidacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partial_payments', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
};
