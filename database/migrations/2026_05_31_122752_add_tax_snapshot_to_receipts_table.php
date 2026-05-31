<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot de la tasa de impuesto al momento de crear la nota.
     * NULL en notas históricas → el cálculo cae a la tasa de la tienda (fallback).
     * Ver xdev/ventas/PLAN_IMPUESTO_SELECCIONABLE_POR_NOTA.md
     */
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->nullable()->after('iva');
            $table->string('tax_name', 20)->nullable()->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'tax_name']);
        });
    }
};
