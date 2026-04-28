<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsComplimentaryToReceiptDetailsTable extends Migration
{
    public function up()
    {
        Schema::table('receipt_details', function (Blueprint $table) {
            $table->boolean('is_complimentary')->default(false)->after('discount_concept');
        });
    }

    public function down()
    {
        Schema::table('receipt_details', function (Blueprint $table) {
            $table->dropColumn('is_complimentary');
        });
    }
}
