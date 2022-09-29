<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColDiscountToReceiptDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('receipt_details', function (Blueprint $table) {
            $table->string('discount_concept')->nullable();
            $table->decimal('discount',8,2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('receipt_details', function (Blueprint $table) {
            $table->dropColumn('discount_concept');
            $table->dropColumn('discount');
        });
    }
}
