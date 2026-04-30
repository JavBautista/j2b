<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte para complementos consolidados — cuando se timbra una nota PPD que
 * tiene varios abonos previos, el usuario puede optar por emitir UN solo
 * complemento que abarca todos esos abonos. Para no destruir el histórico
 * granular se preserva el array de partial_payment_ids consolidados.
 *
 * - `consolidated_partial_payment_ids` = NULL → complemento normal (1 abono).
 * - `consolidated_partial_payment_ids` = [153, 154, 155] → consolidado.
 *   En ese caso, `partial_payment_id` apunta al primero (representativo, joins
 *   legacy), `monto` = suma, `fecha_pago` = la del último (o la elegida).
 *
 * Plan: PLAN_HARDENING_PAGOS_20.md adendum (2026-04-29).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfdi_pago_complementos', function (Blueprint $table) {
            $table->json('consolidated_partial_payment_ids')->nullable()->after('partial_payment_id')
                  ->comment('IDs de partial_payments consolidados en este complemento. NULL si es complemento individual.');
        });
    }

    public function down(): void
    {
        Schema::table('cfdi_pago_complementos', function (Blueprint $table) {
            $table->dropColumn('consolidated_partial_payment_ids');
        });
    }
};
