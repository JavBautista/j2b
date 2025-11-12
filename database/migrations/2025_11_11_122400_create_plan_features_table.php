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
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id');

            // Límites numéricos (-1 = ilimitado)
            $table->integer('max_products')->default(-1);
            $table->integer('max_clients')->default(-1);
            $table->integer('max_collaborators')->default(-1);
            $table->integer('max_tasks')->default(-1);
            $table->integer('max_suppliers')->default(-1);

            // Features booleanas (módulos)
            $table->boolean('gps_tracking')->default(true);
            $table->boolean('reports_basic')->default(true);
            $table->boolean('reports_advanced')->default(false);
            $table->boolean('whatsapp_integration')->default(false);
            $table->boolean('email_marketing')->default(false);
            $table->boolean('custom_branding')->default(false);
            $table->boolean('api_access')->default(false);
            $table->boolean('multi_currency')->default(false);

            // Soporte
            $table->enum('support_level', ['email', 'email_chat', 'priority'])->default('email');

            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
