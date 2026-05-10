<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('snmp_agent_tokens', function (Blueprint $table) {
            $table->foreignId('rent_id')->nullable()->after('client_id')->constrained()->onDelete('cascade');
            $table->index(['client_id', 'rent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('snmp_agent_tokens', function (Blueprint $table) {
            $table->dropIndex(['client_id', 'rent_id']);
            $table->dropForeign(['rent_id']);
            $table->dropColumn('rent_id');
        });
    }
};
