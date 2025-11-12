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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('user_id'); // Quien realizó el pago

            // Montos
            $table->decimal('price_without_iva', 8, 2); // Precio sin IVA
            $table->decimal('iva_amount', 8, 2); // Monto del IVA
            $table->decimal('total_amount', 8, 2); // Total pagado (con IVA)
            $table->enum('currency', ['MXN', 'USD'])->default('MXN');

            // Pago
            $table->enum('payment_method', [
                'stripe',
                'paypal',
                'mercadopago',
                'openpay',
                'transfer',
                'cash',
                'other'
            ])->default('other');
            $table->string('transaction_id')->nullable(); // ID de transacción externa
            $table->string('invoice_id')->nullable(); // ID factura fiscal (opcional)

            // Periodo de la suscripción
            $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');
            $table->timestamp('starts_at'); // Fecha inicio suscripción
            $table->timestamp('ends_at'); // Fecha fin suscripción

            // Estado
            $table->enum('status', [
                'pending',    // Pago pendiente
                'active',     // Suscripción activa
                'expired',    // Periodo terminó
                'cancelled',  // Cancelada
                'refunded'    // Reembolsada
            ])->default('pending');

            // Notas admin
            $table->text('admin_notes')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Índices
            $table->index(['shop_id', 'status']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
