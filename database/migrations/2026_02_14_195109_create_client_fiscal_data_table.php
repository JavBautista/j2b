<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_fiscal_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('rfc', 13);
            $table->string('razon_social');
            $table->string('regimen_fiscal', 3);
            $table->string('uso_cfdi', 3)->default('G03');
            $table->string('codigo_postal', 5);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_fiscal_data');
    }
};
