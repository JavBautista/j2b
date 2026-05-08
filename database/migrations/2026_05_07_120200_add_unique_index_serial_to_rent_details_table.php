<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Normalizar strings vacíos a NULL: MySQL UNIQUE permite múltiples NULL
        // pero trata '' como un valor concreto que sí choca consigo mismo.
        DB::table('rent_details')
            ->where('serial_number', '')
            ->update(['serial_number' => null]);

        Schema::table('rent_details', function (Blueprint $table) {
            $table->unique(['shop_id', 'serial_number'], 'rent_details_shop_serial_unique');
        });
    }

    public function down(): void
    {
        Schema::table('rent_details', function (Blueprint $table) {
            $table->dropUnique('rent_details_shop_serial_unique');
        });
    }
};
