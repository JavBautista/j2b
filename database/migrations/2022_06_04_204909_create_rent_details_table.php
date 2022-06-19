<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rent_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rent_id');
            $table->boolean('active')->default(1);

            $table->string('trademark')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('rent_price',8,2)->nullable();

            $table->boolean('monochrome')->default(0);
            $table->integer('pages_included_mono')->nullable();
            $table->decimal('extra_page_cost_mono',8,2)->nullable();
            $table->integer('counter_mono')->nullable();
            $table->date('update_counter_mono')->nullable();

            $table->boolean('color')->default(0);
            $table->integer('pages_included_color')->nullable();
            $table->decimal('extra_page_cost_color',8,2)->nullable();
            $table->integer('counter_color')->nullable();
            $table->date('update_counter_color')->nullable();


            $table->timestamps();

            $table->foreign('rent_id')->references('id')->on('rents');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rent_details');
    }
}
