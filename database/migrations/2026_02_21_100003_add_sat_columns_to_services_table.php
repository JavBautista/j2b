<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('sat_product_code', 8)->nullable()->after('price');
            $table->string('sat_unit_code', 5)->nullable()->default('E48')->after('sat_product_code');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['sat_product_code', 'sat_unit_code']);
        });
    }
};
