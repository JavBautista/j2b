<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_product_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->index();
            $table->string('description', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_product_codes');
    }
};
