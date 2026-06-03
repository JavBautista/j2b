<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_regimenes_fiscales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('description', 255);
            $table->boolean('aplica_fisica')->default(false);
            $table->boolean('aplica_moral')->default(false);
            $table->boolean('vigente')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_regimenes_fiscales');
    }
};
