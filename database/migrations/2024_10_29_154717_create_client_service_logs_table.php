<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientServiceLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_service_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_service_id');
            $table->string('user')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->foreign('client_service_id')->references('id')->on('client_services')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_service_logs');
    }
}
