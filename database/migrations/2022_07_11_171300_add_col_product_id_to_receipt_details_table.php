<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColProductIdToReceiptDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('receipt_details', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->default(0)->after('receipt_id');
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
            $table->dropColumn('product_id');
        });
    }
}
