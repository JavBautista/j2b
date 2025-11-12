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
        Schema::table('shops', function (Blueprint $table) {
            // Usuario dueño/titular de la tienda
            $table->unsignedBigInteger('owner_user_id')->nullable()->after('plan_id');
            $table->foreign('owner_user_id')->references('id')->on('users')->onDelete('set null');

            // Control de trial
            $table->boolean('is_trial')->default(true)->after('owner_user_id');
            $table->timestamp('trial_ends_at')->nullable()->after('is_trial');

            // Control de suscripción
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            $table->timestamp('grace_period_ends_at')->nullable()->after('subscription_ends_at');

            // Estado de suscripción
            $table->enum('subscription_status', [
                'trial',           // En periodo de prueba
                'active',          // Suscripción activa y pagada
                'grace_period',    // En periodo de gracia (venció pero aún tiene X días)
                'expired',         // Periodo de gracia terminó, shop bloqueado
                'cancelled'        // Cancelado por usuario/admin
            ])->default('trial')->after('grace_period_ends_at');

            // Fecha último pago (para referencia)
            $table->timestamp('last_payment_at')->nullable()->after('subscription_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
            $table->dropColumn([
                'owner_user_id',
                'is_trial',
                'trial_ends_at',
                'subscription_ends_at',
                'grace_period_ends_at',
                'subscription_status',
                'last_payment_at'
            ]);
        });
    }
};
