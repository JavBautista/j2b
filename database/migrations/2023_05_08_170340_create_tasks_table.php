<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('client_id')->nullable()->default(0);
            $table->boolean('active')->default(1);
            $table->string('status')->nullable();
            $table->unsignedBigInteger('priority')->default(1);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('solution')->nullable();
            $table->string('image')->nullable();
            $table->date('expiration')->nullable();
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
