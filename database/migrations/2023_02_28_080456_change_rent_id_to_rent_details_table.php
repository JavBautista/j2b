<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeRentIdToRentDetailsTable extends Migration
{
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('rent_details', function (Blueprint $table) {
            $table->unsignedBigInteger('rent_id')->nullable()->default(0)->change();
        });

        Schema::enableForeignKeyConstraints();

        // Aquí puedes insertar tus datos
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('rent_details', function (Blueprint $table) {
            $table->dropForeign(['rent_id']);
            $table->unsignedBigInteger('rent_id')->change();
        });

        Schema::enableForeignKeyConstraints();
    }
}
