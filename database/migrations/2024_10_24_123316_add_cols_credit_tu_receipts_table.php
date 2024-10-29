<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColsCreditTuReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->boolean('credit')->default(0);
            $table->date('credit_date_notification')->nullable();
            $table->string('credit_type')->nullable();
            $table->boolean('credit_completed')->default(0);
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
            $table->dropColumn('credit');
            $table->dropColumn('credit_date_notification');
            $table->dropColumn('credit_type');
            $table->dropColumn('credit_completed');
        });
    }
}
