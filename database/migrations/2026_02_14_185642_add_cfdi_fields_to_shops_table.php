<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('cfdi_enabled')->default(false)->after('active');
            $table->integer('cfdi_timbres_contratados')->default(0)->after('cfdi_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['cfdi_enabled', 'cfdi_timbres_contratados']);
        });
    }
};
