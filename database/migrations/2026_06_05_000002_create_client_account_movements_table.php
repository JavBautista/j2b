<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Libro de movimientos (ledger) de la cuenta corriente del cliente.
        // Fuente de verdad del saldo a favor; clients.account_balance es solo un caché.
        Schema::create('client_account_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('shop_id')->index();        // multi-tenant + queries
            $table->enum('type', [
                'deposito_anticipo',   // (+) anticipo directo a cuenta
                'sobrepago_nota',      // (+) excedente de un abono
                'devolucion',          // (+) devolución / nota de crédito
                'ajuste_manual',       // (±) corrección del admin
                'aplicacion_venta',    // (−) se usa el saldo para pagar una venta (Fase 2)
            ]);
            $table->decimal('amount', 12, 2);          // CON signo: + aumenta saldo a favor, − lo consume
            $table->decimal('balance_after', 12, 2);   // saldo resultante (snapshot p/ estado de cuenta)
            $table->nullableMorphs('reference');       // reference_type/reference_id: receipt, partial_payment...
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();   // user que registró
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_account_movements');
    }
};
