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
        Schema::table('contracts', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('status')->comment('Fecha de inicio del contrato');
            $table->date('expiration_date')->nullable()->after('start_date')->comment('Fecha de vencimiento del contrato');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'expiration_date']);
        });
    }
};
