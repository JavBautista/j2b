<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Catálogo de módulos vendibles de la plataforma. Generaliza el patrón
     * hardcodeado de shops.cfdi_enabled / shops.monitor_billing_enabled.
     */
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();                 // 'cfdi', 'tasks', 'gps'...
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();              // clase fa-* para el sidebar
            $table->boolean('is_core')->default(false);      // true = incluido siempre, no se vende suelto, no se gatea
            $table->decimal('base_price', 10, 2)->default(0);// precio mensual sugerido (override por tienda en shop_modules)
            $table->enum('billing_type', ['flat', 'tiered', 'usage'])->default('flat'); // tiered=Monitor, usage=timbres
            $table->json('requires')->nullable();            // keys de módulos de los que depende, ej: ["tasks"]
            $table->boolean('is_external')->default(false);  // J2Doctor a futuro; no se usa aún
            $table->boolean('active')->default(true);        // si se ofrece en el catálogo
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
