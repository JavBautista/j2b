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
        Schema::table('contracts', function (Blueprint $table) {
            // Modificar enum status para incluir 'cancelled'
            $table->enum('status', ['draft', 'generated', 'sent', 'signed', 'cancelled'])->change();

            // Agregar campos de cancelación
            $table->timestamp('cancelled_at')->nullable()->after('expiration_date');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancellation_reason');

            // Foreign key para saber quién canceló
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Eliminar foreign key
            $table->dropForeign(['cancelled_by']);

            // Eliminar campos agregados
            $table->dropColumn(['cancelled_at', 'cancellation_reason', 'cancelled_by']);

            // Revertir enum status
            $table->enum('status', ['draft', 'generated', 'sent', 'signed'])->change();
        });
    }
};
