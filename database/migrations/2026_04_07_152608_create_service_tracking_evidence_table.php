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
        Schema::create('service_tracking_evidence', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tracking_id');
            $table->string('image', 255);
            $table->string('caption', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('tracking_id')
                ->references('id')->on('task_service_tracking')
                ->onDelete('cascade');

            $table->index('tracking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_tracking_evidence');
    }
};
