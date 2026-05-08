<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rent_details', function (Blueprint $table) {
            $table->string('local_ip', 45)->nullable()->after('url_web_monitor');
            $table->boolean('monitor_enabled')->default(false)->after('local_ip');
        });
    }

    public function down(): void
    {
        Schema::table('rent_details', function (Blueprint $table) {
            $table->dropColumn(['local_ip', 'monitor_enabled']);
        });
    }
};
