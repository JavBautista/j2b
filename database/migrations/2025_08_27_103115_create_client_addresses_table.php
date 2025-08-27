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
        Schema::create('client_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            
            // Campos de dirección
            $table->string('name')->nullable(); // Nombre de la sucursal/negocio
            $table->string('address'); // Dirección completa
            $table->string('num_ext')->nullable(); // Número exterior
            $table->string('num_int')->nullable(); // Número interior
            $table->string('colony')->nullable(); // Colonia
            $table->string('city'); // Ciudad
            $table->string('state'); // Estado
            $table->string('country')->default('México'); // País
            $table->string('postal_code', 10)->nullable(); // Código postal
            
            // Campos de ubicación para mapas
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_image')->nullable(); // Imagen de la ubicación
            
            // Información adicional
            $table->text('description')->nullable(); // Descripción de la ubicación
            $table->string('phone')->nullable(); // Teléfono específico de esta ubicación
            $table->string('email')->nullable(); // Email específico de esta ubicación
            $table->boolean('is_primary')->default(false); // Si es la dirección principal
            $table->boolean('active')->default(true); // Si está activa
            
            $table->timestamps();
            
            // Índices
            $table->index(['client_id', 'active']);
            $table->index(['city', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_addresses');
    }
};
