<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfdi_emisor_impuestos_locales_defaults', function (Blueprint $t) {
            $t->bigIncrements('id');

            $t->unsignedBigInteger('cfdi_emisor_id');
            $t->enum('tipo', ['retencion', 'traslado']);
            $t->string('nombre', 100);
            $t->decimal('tasa_porcentaje', 8, 2);
            $t->boolean('activo')->default(true);

            $t->timestamps();

            $t->foreign('cfdi_emisor_id')->references('id')->on('cfdi_emisores')->cascadeOnDelete();
            $t->index(['cfdi_emisor_id', 'activo'], 'idx_emisor_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdi_emisor_impuestos_locales_defaults');
    }
};
