<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColsPeriodRentsToReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('rent_periodo')->nullable()->after('type');
            $table->unsignedBigInteger('rent_yy')->default(0)->after('type');
            $table->unsignedBigInteger('rent_mm')->default(0)->after('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn('rent_periodo');
            $table->dropColumn('rent_yy');
            $table->dropColumn('rent_mm');
        });
    }
}
