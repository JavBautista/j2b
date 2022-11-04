<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsumablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rent_detail_id');
            $table->unsignedBigInteger('product_id');
            $table->string('description')->nullable();
            $table->integer('qty')->default(1)->nullable();
            $table->integer('counter')->nullable();
            $table->timestamps();
            $table->foreign('rent_detail_id')->references('id')->on('rent_details');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('consumables');
    }
}
