<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_fiscal_data', function (Blueprint $table) {
            $table->string('uso_cfdi', 5)->default('G03')->change();
        });
    }

    public function down(): void
    {
        Schema::table('client_fiscal_data', function (Blueprint $table) {
            $table->string('uso_cfdi', 3)->default('G03')->change();
        });
    }
};
