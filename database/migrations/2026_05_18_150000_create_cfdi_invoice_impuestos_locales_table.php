<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfdi_invoice_impuestos_locales', function (Blueprint $t) {
            $t->bigIncrements('id');

            $t->unsignedBigInteger('cfdi_invoice_id');
            $t->enum('tipo', ['retencion', 'traslado']);
            $t->string('nombre', 100); // CEDULAR, ISH, ISA, TURISMO, ESPECTACULOS, libre 3-100
            $t->decimal('tasa_porcentaje', 8, 2); // 5.00, 2.50, etc
            $t->decimal('base', 14, 2);
            $t->decimal('importe', 14, 2);

            $t->timestamps();

            $t->foreign('cfdi_invoice_id')->references('id')->on('cfdi_invoices')->cascadeOnDelete();
            $t->index(['cfdi_invoice_id', 'tipo'], 'idx_invoice_tipo');
            $t->index('nombre', 'idx_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdi_invoice_impuestos_locales');
    }
};
