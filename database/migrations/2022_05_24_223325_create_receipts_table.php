<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('type')->nullable();
            $table->string('description')->nullable();
            $table->string('observation')->nullable();
            $table->string('discount_concept')->nullable();
            $table->string('status')->nullable();
            $table->string('payment')->nullable();
            $table->decimal('subtotal',8,2)->nullable();
            $table->decimal('discount',8,2)->nullable();
            $table->decimal('received',8,2)->nullable();
            $table->decimal('total',8,2)->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('receipts');
    }
}
