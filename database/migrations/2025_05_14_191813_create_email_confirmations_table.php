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
        Schema::create('email_confirmations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shop');
            $table->string('phone');
            $table->string('email')->unique();
            $table->string('password'); // encriptado
            $table->string('avatar')->nullable();
            $table->string('token'); // token de confirmación único
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_confirmations');
    }
};
