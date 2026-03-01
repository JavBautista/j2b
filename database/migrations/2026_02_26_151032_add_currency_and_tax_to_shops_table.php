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
            $table->string('currency', 3)->default('MXN')->after('active');
            $table->string('tax_name', 20)->nullable()->default('IVA')->after('currency');
            $table->decimal('tax_rate', 5, 2)->default(16.00)->after('tax_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['currency', 'tax_name', 'tax_rate']);
        });
    }
};
