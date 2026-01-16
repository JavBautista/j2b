<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar campos para ciclo de facturacion anual.
     * - yearly_price: precio anual personalizado de esta tienda
     * - billing_cycle: ciclo actual (mensual o anual)
     */
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Precio anual personalizado (se toma del plan al registrar, luego editable)
            $table->decimal('yearly_price', 10, 2)->nullable()->after('monthly_price');

            // Ciclo de facturacion actual (mensual por defecto)
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly')->after('yearly_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['yearly_price', 'billing_cycle']);
        });
    }
};
