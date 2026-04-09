<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cfdi_timbre_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('total', 10, 2);
            $table->unsignedBigInteger('assigned_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('assigned_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['shop_id', 'created_at']);
        });

        // Insertar setting de precio por timbre
        DB::table('subscription_settings')->insert([
            'key' => 'cfdi_precio_por_timbre',
            'value' => '2.00',
            'type' => 'decimal',
            'label' => 'Precio por timbre CFDI',
            'description' => 'Precio unitario que se cobra por cada timbre asignado a una tienda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('cfdi_timbre_transactions');
        DB::table('subscription_settings')->where('key', 'cfdi_precio_por_timbre')->delete();
    }
};
