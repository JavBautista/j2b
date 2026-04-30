<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo multitenant de cuentas bancarias por tienda.
 * Reutilizable en:
 *  - Complementos de pago (CFDI Pagos 2.0): cuenta beneficiaria del comercio.
 *  - PDF de nota/recibo de venta: datos para depósito del cliente.
 *  - Configuración de la tienda en /admin/configuracion/cuentas-bancarias.
 *
 * Soft-delete preserva trazabilidad fiscal de complementos ya emitidos.
 *
 * Plan: xdev/facturacion/HUB CFDI/PLAN_HARDENING_PAGOS_20.md §3.1
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');

            $table->string('alias', 80);
            $table->string('bank_code', 10);
            $table->string('bank_name', 100);
            $table->string('bank_rfc', 13);

            $table->string('clabe', 18);
            $table->string('account_number', 20)->nullable();
            $table->string('holder_name', 150);

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['shop_id', 'is_default']);
            $table->index(['shop_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_bank_accounts');
    }
};
