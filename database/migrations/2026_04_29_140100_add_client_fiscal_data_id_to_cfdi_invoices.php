<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            $table->foreignId('client_fiscal_data_id')
                ->nullable()
                ->after('receipt_id')
                ->constrained('client_fiscal_data')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            $table->dropForeign(['client_fiscal_data_id']);
            $table->dropColumn('client_fiscal_data_id');
        });
    }
};
