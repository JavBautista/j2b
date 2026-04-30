<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campos por-abono para Pagos 2.0:
 *  - payment_method: forma SAT del abono (cada abono puede tener forma distinta)
 *  - shop_bank_account_id: cuenta beneficiaria que recibió el dinero
 *  - bank_ord_code, cta_ordenante, is_foreign_bank_ord, num_operacion: datos del cliente pagador
 *
 * Backfill: registros existentes obtienen payment_method derivado del receipt.payment.
 *
 * Plan: xdev/facturacion/HUB CFDI/PLAN_HARDENING_PAGOS_20.md §3.2
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partial_payments', function (Blueprint $table) {
            $table->string('payment_method', 2)->nullable()->default('99')->after('payment_type')
                  ->comment('Forma de pago SAT del abono: 01,02,03,04,05,06,28,29,99');
            $table->foreignId('shop_bank_account_id')->nullable()->after('payment_method')
                  ->constrained('shop_bank_accounts')->onDelete('set null');
            $table->string('bank_ord_code', 10)->nullable()->after('shop_bank_account_id')
                  ->comment('Banco del cliente (catálogo c_Banco) — opcional');
            $table->string('cta_ordenante', 50)->nullable()->after('bank_ord_code')
                  ->comment('Cuenta del cliente — opcional');
            $table->boolean('is_foreign_bank_ord')->default(false)->after('cta_ordenante');
            $table->string('num_operacion', 100)->nullable()->after('is_foreign_bank_ord')
                  ->comment('Referencia bancaria/SPEI');
        });

        // Backfill: derivar payment_method desde receipt.payment para registros existentes
        $mapping = [
            'EFECTIVO'      => '01',
            'CHEQUE'        => '02',
            'TRANSFERENCIA' => '03',
            'TARJETA'       => '04',
        ];

        foreach ($mapping as $payment => $sat) {
            DB::statement(
                'UPDATE partial_payments pp
                 INNER JOIN receipts r ON r.id = pp.receipt_id
                 SET pp.payment_method = ?
                 WHERE UPPER(TRIM(r.payment)) = ?',
                [$sat, $payment]
            );
        }
    }

    public function down(): void
    {
        Schema::table('partial_payments', function (Blueprint $table) {
            $table->dropForeign(['shop_bank_account_id']);
            $table->dropColumn([
                'payment_method',
                'shop_bank_account_id',
                'bank_ord_code',
                'cta_ordenante',
                'is_foreign_bank_ord',
                'num_operacion',
            ]);
        });
    }
};
