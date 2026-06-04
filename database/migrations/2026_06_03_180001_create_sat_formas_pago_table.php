<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_formas_pago', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('description', 255);
            // vigente = se ofrece en los selects. El catálogo SAT completo vive en BD;
            // por default solo las formas comunes están vigentes (superadmin activa el resto).
            $table->boolean('vigente')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_formas_pago');
    }
};
