<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $t) {
            $t->enum('pipeline_timbrado', ['json', 'xml_compat'])
                ->default('json')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $t) {
            $t->dropColumn('pipeline_timbrado');
        });
    }
};
