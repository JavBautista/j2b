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
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['terms', 'privacy'])->comment('terms = Términos y Condiciones, privacy = Aviso de Privacidad');
            $table->string('title');
            $table->longText('content')->comment('Contenido HTML del documento');
            $table->string('version')->default('1.0');
            $table->date('effective_date')->nullable()->comment('Fecha de vigencia');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
    }
};
