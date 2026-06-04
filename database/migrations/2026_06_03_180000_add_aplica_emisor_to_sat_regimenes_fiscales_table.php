<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sat_regimenes_fiscales', function (Blueprint $table) {
            // Régimen que un emisor (la tienda que factura) puede tener.
            // Subconjunto curado: no todos los regímenes emiten CFDI de ingreso.
            $table->boolean('aplica_emisor')->default(false)->after('aplica_moral');
        });
    }

    public function down(): void
    {
        Schema::table('sat_regimenes_fiscales', function (Blueprint $table) {
            $table->dropColumn('aplica_emisor');
        });
    }
};
