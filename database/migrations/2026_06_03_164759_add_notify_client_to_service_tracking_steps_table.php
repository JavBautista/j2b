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
        Schema::table('service_tracking_steps', function (Blueprint $table) {
            // Si está activo, el cliente recibe notificación (push + in-app)
            // cuando su servicio llega a este estatus.
            $table->boolean('notify_client')->default(false)->after('is_final');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_tracking_steps', function (Blueprint $table) {
            $table->dropColumn('notify_client');
        });
    }
};
