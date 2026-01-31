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
        Schema::table('shop_ai_settings', function (Blueprint $table) {
            $table->timestamp('last_embedding_sync')->nullable()->after('system_prompt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_ai_settings', function (Blueprint $table) {
            $table->dropColumn('last_embedding_sync');
        });
    }
};
