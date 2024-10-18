<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColUserIdToClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Agregamos la columna user_id después del campo id, permitimos que sea nulo
            $table->unsignedBigInteger('user_id')->nullable()->after('id');

            // Establecemos la relación con la tabla users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Primero eliminamos la restricción de clave foránea
            $table->dropForeign(['user_id']);

            // Luego eliminamos la columna
            $table->dropColumn('user_id');
        });
    }
}
