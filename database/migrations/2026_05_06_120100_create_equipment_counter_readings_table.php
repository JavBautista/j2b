<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_counter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('rent_detail_id')->nullable()->constrained('rent_details')->nullOnDelete();

            $table->string('raw_serial');
            $table->string('raw_model')->nullable();

            $table->unsignedInteger('counter_mono')->nullable();
            $table->unsignedInteger('counter_color')->nullable();

            $table->unsignedTinyInteger('toner_k')->nullable();
            $table->unsignedTinyInteger('toner_c')->nullable();
            $table->unsignedTinyInteger('toner_m')->nullable();
            $table->unsignedTinyInteger('toner_y')->nullable();

            $table->boolean('matched')->default(false);
            $table->string('source', 20)->default('snmp');
            $table->json('raw_payload')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->dateTime('read_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['shop_id', 'read_at']);
            $table->index(['rent_detail_id', 'read_at']);
            $table->index(['client_id', 'read_at']);
            $table->index(['shop_id', 'matched']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_counter_readings');
    }
};
