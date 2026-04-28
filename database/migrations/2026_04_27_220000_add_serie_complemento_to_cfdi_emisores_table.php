<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfdi_emisores', function (Blueprint $table) {
            $table->string('serie_complemento')->default('CP')->after('folio_actual');
            $table->integer('folio_complemento_actual')->default(0)->after('serie_complemento');
        });
    }

    public function down(): void
    {
        Schema::table('cfdi_emisores', function (Blueprint $table) {
            $table->dropColumn(['serie_complemento', 'folio_complemento_actual']);
        });
    }
};
