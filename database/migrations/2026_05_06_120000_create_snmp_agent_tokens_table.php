<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snmp_agent_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            $table->string('token', 64)->unique();
            $table->boolean('active')->default(true);

            $table->dateTime('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();

            $table->timestamps();

            $table->index(['shop_id', 'active']);
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snmp_agent_tokens');
    }
};
