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
            $table->unsignedBigInteger('current_service_step_id')->nullable()->after('origin');
            $table->string('tracking_code', 20)->nullable()->unique()->after('current_service_step_id');

            $table->foreign('current_service_step_id')->references('id')->on('service_tracking_steps')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['current_service_step_id']);
            $table->dropColumn(['current_service_step_id', 'tracking_code']);
        });
    }
};
