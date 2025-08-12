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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id');
            $table->foreignId('contract_template_id');
            $table->json('contract_data'); // Datos específicos del contrato
            $table->string('pdf_path')->nullable();
            $table->string('signature_path')->nullable(); // Ruta del archivo de firma
            $table->enum('status', ['draft', 'generated', 'sent', 'signed']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
