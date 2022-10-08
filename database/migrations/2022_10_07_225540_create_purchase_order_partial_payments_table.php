<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderPartialPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_order_partial_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->decimal('amount',8,2)->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_order_partial_payments');
    }
}
