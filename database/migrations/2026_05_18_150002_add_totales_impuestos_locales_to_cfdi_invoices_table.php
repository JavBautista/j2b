<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $t) {
            $t->decimal('total_impuestos_locales_retenidos', 14, 2)->default(0)->after('total_retenciones');
            $t->decimal('total_impuestos_locales_trasladados', 14, 2)->default(0)->after('total_impuestos_locales_retenidos');
        });
    }

    public function down(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $t) {
            $t->dropColumn(['total_impuestos_locales_retenidos', 'total_impuestos_locales_trasladados']);
        });
    }
};
