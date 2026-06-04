<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_metodos_pago', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('description', 255);
            $table->boolean('vigente')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_metodos_pago');
    }
};
