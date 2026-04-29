<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            $table->string('receptor_uso_cfdi', 5)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            $table->string('receptor_uso_cfdi', 3)->change();
        });
    }
};
