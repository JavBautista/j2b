<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColForSalesToRentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rent_details', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->decimal('cost',8,2)->nullable();
            $table->decimal('retail',8,2)->nullable();
            $table->decimal('wholesale',8,2)->nullable();
            $table->boolean('type_sale')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rent_details', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('cost');
            $table->dropColumn('retail');
            $table->dropColumn('wholesale');
            $table->dropColumn('type_sale');
        });
    }
}
