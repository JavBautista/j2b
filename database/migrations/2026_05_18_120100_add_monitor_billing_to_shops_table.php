<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('monitor_billing_enabled')->default(false)->after('monitor_licenses_total');
            $table->unsignedBigInteger('monitor_tier_locked_id')->nullable()->after('monitor_billing_enabled');
            $table->decimal('monitor_locked_price_per_equipment', 8, 2)->nullable()->after('monitor_tier_locked_id');
            $table->decimal('monitor_locked_flat_amount', 10, 2)->nullable()->after('monitor_locked_price_per_equipment');

            $table->foreign('monitor_tier_locked_id')
                  ->references('id')->on('monitor_pricing_tiers')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropForeign(['monitor_tier_locked_id']);
            $table->dropColumn([
                'monitor_billing_enabled',
                'monitor_tier_locked_id',
                'monitor_locked_price_per_equipment',
                'monitor_locked_flat_amount',
            ]);
        });
    }
};
