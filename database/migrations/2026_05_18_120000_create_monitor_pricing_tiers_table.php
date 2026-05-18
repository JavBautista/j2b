<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->unsignedInteger('min_equipment');
            $table->unsignedInteger('max_equipment')->nullable();
            $table->decimal('price_per_equipment', 8, 2)->nullable();
            $table->boolean('is_flat_rate')->default(false);
            $table->decimal('flat_amount', 10, 2)->nullable();
            $table->boolean('includes_base_plan')->default(false);
            $table->enum('currency', ['MXN', 'USD'])->default('MXN');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['active', 'sort_order']);
            $table->index(['min_equipment', 'max_equipment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_pricing_tiers');
    }
};
