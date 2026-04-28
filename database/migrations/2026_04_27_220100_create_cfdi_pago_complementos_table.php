<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfdi_pago_complementos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('cfdi_invoice_id')->constrained('cfdi_invoices')->onDelete('cascade');
            $table->foreignId('partial_payment_id')->nullable()->constrained('partial_payments')->onDelete('set null');

            $table->string('uuid', 36)->nullable()->index();
            $table->string('serie', 25)->default('CP');
            $table->integer('folio')->default(0);

            $table->dateTime('fecha_emision')->nullable();
            $table->dateTime('fecha_timbrado')->nullable();

            $table->decimal('monto', 12, 2)->default(0);
            $table->string('forma_pago', 2)->nullable();
            $table->integer('num_parcialidad')->default(1);
            $table->decimal('imp_saldo_ant', 12, 2)->default(0);
            $table->decimal('imp_pagado', 12, 2)->default(0);
            $table->decimal('imp_saldo_insoluto', 12, 2)->default(0);

            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();

            $table->string('status', 20)->default('pending')->index();
            $table->text('error_message')->nullable();

            $table->json('request_json')->nullable();
            $table->json('response_json')->nullable();

            $table->dateTime('fecha_cancelacion')->nullable();

            $table->timestamps();

            $table->index(['cfdi_invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdi_pago_complementos');
    }
};
