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
        Schema::create('task_tracking_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('assigned_user_id');
            $table->date('tracking_date');

            // Coordenadas inicio
            $table->decimal('start_lat', 10, 8);
            $table->decimal('start_lng', 11, 8);
            $table->dateTime('start_timestamp');

            // Coordenadas fin
            $table->decimal('end_lat', 10, 8);
            $table->decimal('end_lng', 11, 8);
            $table->dateTime('end_timestamp');

            // Métricas calculadas
            $table->integer('gps_points_count');
            $table->decimal('distance_km', 10, 2);
            $table->integer('duration_minutes');
            $table->decimal('avg_speed_kmh', 5, 2)->nullable();

            // Ruta completa simplificada (1 punto cada 5 min)
            $table->json('route_points')->nullable()->comment('Simplified route: 1 point every 5 min');

            // Metadata
            $table->string('firebase_path')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');

            // Índices
            $table->index(['shop_id', 'tracking_date'], 'idx_shop_date');
            $table->index('task_id', 'idx_task');
            $table->index(['assigned_user_id', 'tracking_date'], 'idx_user_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_tracking_history');
    }
};
