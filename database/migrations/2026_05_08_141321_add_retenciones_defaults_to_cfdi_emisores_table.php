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
        Schema::table('cfdi_emisores', function (Blueprint $table) {
            $table->boolean('ret_isr_default_aplica')->default(false)->after('folio_actual');
            $table->decimal('ret_isr_default_tasa', 8, 6)->nullable()->after('ret_isr_default_aplica');
            $table->boolean('ret_iva_default_aplica')->default(false)->after('ret_isr_default_tasa');
            $table->decimal('ret_iva_default_tasa', 8, 6)->nullable()->after('ret_iva_default_aplica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cfdi_emisores', function (Blueprint $table) {
            $table->dropColumn([
                'ret_isr_default_aplica',
                'ret_isr_default_tasa',
                'ret_iva_default_aplica',
                'ret_iva_default_tasa',
            ]);
        });
    }
};
