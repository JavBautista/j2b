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
        Schema::table('tasks', function (Blueprint $table) {
            // Columnas para tracking GPS
            $table->boolean('tracking_active')->default(false)->after('status');
            $table->dateTime('tracking_started_at')->nullable()->after('tracking_active');
            $table->dateTime('tracking_finished_at')->nullable()->after('tracking_started_at');
            $table->decimal('tracking_start_lat', 10, 8)->nullable()->after('tracking_finished_at');
            $table->decimal('tracking_start_lng', 11, 8)->nullable()->after('tracking_start_lat');
            $table->decimal('tracking_end_lat', 10, 8)->nullable()->after('tracking_start_lng');
            $table->decimal('tracking_end_lng', 11, 8)->nullable()->after('tracking_end_lat');
            $table->decimal('tracking_distance_km', 10, 2)->nullable()->after('tracking_end_lng');
            $table->integer('tracking_points_count')->default(0)->after('tracking_distance_km');
            $table->integer('tracking_duration_minutes')->nullable()->after('tracking_points_count');

            // Índices para mejorar performance
            $table->index(['tracking_active', 'shop_id'], 'idx_tasks_tracking_active');
            $table->index(['tracking_started_at', 'shop_id'], 'idx_tasks_tracking_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex('idx_tasks_tracking_active');
            $table->dropIndex('idx_tasks_tracking_date');

            // Eliminar columnas
            $table->dropColumn([
                'tracking_active',
                'tracking_started_at',
                'tracking_finished_at',
                'tracking_start_lat',
                'tracking_start_lng',
                'tracking_end_lat',
                'tracking_end_lng',
                'tracking_distance_km',
                'tracking_points_count',
                'tracking_duration_minutes'
            ]);
        });
    }
};
