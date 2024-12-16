<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientServiceImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_service_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_service_id');
            $table->string('image')->nullable();
            $table->string('user')->nullable();
            $table->foreign('client_service_id')->references('id')->on('client_services')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_service_images');
    }
}
