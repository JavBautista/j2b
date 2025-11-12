<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Moneda
            $table->enum('currency', ['MXN', 'USD'])->default('MXN')->after('price');

            // IVA (porcentaje, ej: 16.00 para 16%)
            $table->decimal('iva_percentage', 5, 2)->default(16.00)->after('currency');

            // Precio sin IVA (para cálculos)
            $table->decimal('price_without_iva', 8, 2)->nullable()->after('price');

            // Periodo de facturación (mensual, anual)
            $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly')->after('iva_percentage');

            // Orden de visualización
            $table->integer('sort_order')->default(0)->after('billing_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['currency', 'iva_percentage', 'price_without_iva', 'billing_period', 'sort_order']);
        });
    }
};
