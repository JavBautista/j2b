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
        Schema::create('subscription_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // trial_days, grace_period_days, etc.
            $table->text('value'); // Valor
            $table->enum('type', ['string', 'integer', 'boolean', 'decimal'])->default('string');
            $table->string('label'); // Etiqueta para el panel admin
            $table->text('description')->nullable(); // Descripción
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_settings');
    }
};
