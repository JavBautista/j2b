<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Unificación de Tasks + ClientService
     * - origin: indica si la tarea fue creada por admin o solicitada por cliente
     * - requested_by_user_id: ID del usuario cliente que solicitó el servicio
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Campo para diferenciar origen: 'admin' (tarea normal) o 'client' (solicitud de cliente)
            $table->string('origin', 10)->default('admin')->after('client_id');

            // ID del usuario cliente que solicitó el servicio (solo cuando origin='client')
            $table->unsignedBigInteger('requested_by_user_id')->nullable()->after('origin');
            $table->foreign('requested_by_user_id')->references('id')->on('users')->onDelete('set null');

            // Índice para filtrar por origen
            $table->index('origin', 'idx_tasks_origin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_origin');
            $table->dropForeign(['requested_by_user_id']);
            $table->dropColumn(['origin', 'requested_by_user_id']);
        });
    }
};
