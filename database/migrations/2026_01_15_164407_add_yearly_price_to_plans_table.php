<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar precio anual de referencia a los planes.
     * Este valor se usa como referencia al registrar una tienda.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Precio anual de referencia (ejemplo: $3,500 para plan de $350/mes)
            $table->decimal('yearly_price', 10, 2)->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('yearly_price');
        });
    }
};
