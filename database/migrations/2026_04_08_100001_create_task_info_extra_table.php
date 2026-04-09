<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('task_info_extra', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->string('field_name');
            $table->string('value')->nullable();
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->index(['task_id', 'field_name', 'value'], 'idx_task_info_extra_filter');
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_info_extra');
    }
};
