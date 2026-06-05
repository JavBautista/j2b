<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Saldo cacheado de la cuenta corriente del cliente.
            // + = saldo a favor (anticipo) | - = adeudo. Fuente de verdad: client_account_movements.
            $table->decimal('account_balance', 12, 2)->default(0)->after('origin_chatbot');
            // Reservado para el módulo de crédito con límite (fase posterior). Aún no se usa.
            $table->decimal('credit_limit', 12, 2)->nullable()->after('account_balance');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['account_balance', 'credit_limit']);
        });
    }
};
