<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_regimen_uso', function (Blueprint $table) {
            $table->id();
            $table->string('regimen_code', 3)->index();
            $table->string('uso_code', 5)->index();
            $table->unique(['regimen_code', 'uso_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_regimen_uso');
    }
};
