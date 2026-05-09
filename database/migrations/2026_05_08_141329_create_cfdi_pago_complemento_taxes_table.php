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
        Schema::create('cfdi_pago_complemento_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfdi_pago_complemento_id')->constrained()->onDelete('cascade');
            $table->enum('scope', ['dr', 'p'])->comment('dr = ImpuestosDR (por documento relacionado), p = ImpuestosP (totales del pago)');
            $table->enum('tipo', ['traslado', 'retencion']);
            $table->string('impuesto', 3)->comment('001=ISR, 002=IVA, 003=IEPS');
            $table->string('tipo_factor', 10)->default('Tasa');
            $table->decimal('tasa', 8, 6)->nullable()->comment('NULL en retenciones globales (RetencionP) que no llevan tasa');
            $table->decimal('base', 12, 2)->nullable()->comment('NULL en retenciones globales (RetencionP) que no llevan base');
            $table->decimal('importe', 12, 2);
            $table->timestamps();

            $table->index(['cfdi_pago_complemento_id', 'scope', 'tipo'], 'cpct_complemento_scope_tipo_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfdi_pago_complemento_taxes');
    }
};
