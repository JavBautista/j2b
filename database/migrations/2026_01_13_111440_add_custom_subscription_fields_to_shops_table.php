<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega campos de suscripción personalizables por tienda:
     * - monthly_price: Precio mensual que se le cobra a esta tienda (puede diferir del plan)
     * - trial_days: Días de prueba gratuita para esta tienda
     * - grace_period_days: Días de gracia antes de bloquear esta tienda
     */
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Precio mensual personalizado (si es null, usar el del plan)
            $table->decimal('monthly_price', 10, 2)->nullable()->after('plan_id');

            // Días de trial personalizados (si es null, usar config global)
            $table->integer('trial_days')->nullable()->after('is_trial');

            // Días de gracia personalizados (si es null, usar config global)
            $table->integer('grace_period_days')->nullable()->after('grace_period_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['monthly_price', 'trial_days', 'grace_period_days']);
        });
    }
};
