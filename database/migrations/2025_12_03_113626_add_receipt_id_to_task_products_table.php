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
        Schema::table('task_products', function (Blueprint $table) {
            $table->foreignId('receipt_id')->nullable()->after('returned_at')
                  ->constrained('receipts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_products', function (Blueprint $table) {
            $table->dropForeign(['receipt_id']);
            $table->dropColumn('receipt_id');
        });
    }
};
