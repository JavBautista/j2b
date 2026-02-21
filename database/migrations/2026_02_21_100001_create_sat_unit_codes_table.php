<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_unit_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->index();
            $table->string('name', 255);
            $table->string('note', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_unit_codes');
    }
};
