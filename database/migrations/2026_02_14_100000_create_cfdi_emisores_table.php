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
        Schema::create('cfdi_emisores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->string('rfc', 13);
            $table->string('razon_social');
            $table->string('regimen_fiscal', 3);
            $table->string('codigo_postal', 5);
            $table->string('cer_file')->nullable();
            $table->string('key_file')->nullable();
            $table->text('password')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_registered')->default(false);
            $table->json('hub_response')->nullable();
            $table->integer('timbres_asignados')->default(0);
            $table->integer('timbres_usados')->default(0);
            $table->string('serie')->default('A');
            $table->integer('folio_actual')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique('shop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfdi_emisores');
    }
};
