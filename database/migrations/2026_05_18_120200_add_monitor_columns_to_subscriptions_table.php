<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('plan_amount', 10, 2)->nullable()->after('total_amount');
            $table->decimal('monitor_amount', 10, 2)->nullable()->after('plan_amount');
            $table->unsignedBigInteger('monitor_tier_id')->nullable()->after('monitor_amount');
            $table->unsignedInteger('monitor_equipment_count')->nullable()->after('monitor_tier_id');
            $table->decimal('monitor_unit_price', 8, 2)->nullable()->after('monitor_equipment_count');

            $table->foreign('monitor_tier_id')
                  ->references('id')->on('monitor_pricing_tiers')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['monitor_tier_id']);
            $table->dropColumn([
                'plan_amount',
                'monitor_amount',
                'monitor_tier_id',
                'monitor_equipment_count',
                'monitor_unit_price',
            ]);
        });
    }
};
