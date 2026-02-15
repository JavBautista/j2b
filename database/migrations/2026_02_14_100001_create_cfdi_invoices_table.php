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
        Schema::create('cfdi_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('cfdi_emisor_id')->constrained('cfdi_emisores')->onDelete('cascade');
            $table->unsignedBigInteger('receipt_id')->nullable();
            $table->string('receptor_rfc', 13);
            $table->string('receptor_nombre');
            $table->string('receptor_regimen', 3);
            $table->string('receptor_cp', 5);
            $table->string('receptor_uso_cfdi', 3);
            $table->string('receptor_email')->nullable();
            $table->string('uuid')->unique();
            $table->string('serie');
            $table->string('folio');
            $table->dateTime('fecha_emision');
            $table->dateTime('fecha_timbrado')->nullable();
            $table->string('tipo_comprobante', 1);
            $table->string('forma_pago', 2);
            $table->string('metodo_pago', 3);
            $table->string('moneda', 3)->default('MXN');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total_impuestos', 12, 2);
            $table->decimal('total', 12, 2);
            $table->longText('xml_content')->nullable();
            $table->string('status')->default('vigente');
            $table->string('motivo_cancelacion')->nullable();
            $table->dateTime('fecha_cancelacion')->nullable();
            $table->json('request_json')->nullable();
            $table->json('response_json')->nullable();
            $table->timestamps();

            $table->foreign('receipt_id')->references('id')->on('receipts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfdi_invoices');
    }
};
