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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('notification_group_id')->nullable()->index()->after('id');
            $table->boolean('visible')->default(true)->index()->after('read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['notification_group_id']);
            $table->dropIndex(['visible']);
            $table->dropColumn('notification_group_id');
            $table->dropColumn('visible');
        });
    }
};
