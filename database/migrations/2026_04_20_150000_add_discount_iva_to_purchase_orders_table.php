<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->after('total');
            $table->decimal('discount', 10, 2)->default(0)->after('subtotal');
            $table->string('discount_concept', 2)->nullable()->after('discount');
            $table->decimal('iva', 10, 2)->default(0)->after('discount_concept');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'discount', 'discount_concept', 'iva']);
        });
    }
};
