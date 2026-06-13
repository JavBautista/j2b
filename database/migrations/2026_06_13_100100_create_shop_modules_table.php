<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pivote: qué módulos tiene contratados cada tienda y a qué precio pactado.
     */
    public function up(): void
    {
        Schema::create('shop_modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('module_id');
            $table->boolean('enabled')->default(true);
            $table->decimal('price', 10, 2)->nullable();        // precio pactado para esta tienda; null = usa modules.base_price
            $table->timestamp('contracted_at')->nullable();
            $table->timestamp('expires_at')->nullable();        // null = sigue el ciclo de la suscripción general
            $table->unsignedBigInteger('assigned_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
            $table->foreign('assigned_by_user_id')->references('id')->on('users')->onDelete('set null');

            // Una tienda no puede tener el mismo módulo dos veces
            $table->unique(['shop_id', 'module_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_modules');
    }
};
