<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('client_id');
            $table->boolean('active')->default(1);
            $table->string('status')->nullable();
            $table->unsignedBigInteger('priority')->default(1);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('solution')->nullable();
            $table->string('image')->nullable();
            $table->date('date_completed')->nullable();
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops');
            $table->foreign('client_id')->references('id')->on('clients');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_services');
    }
}
