<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_fiscal_data', function (Blueprint $table) {
            $table->string('email', 150)->nullable()->after('codigo_postal');
            $table->string('nickname', 80)->nullable()->after('email');
            $table->boolean('active')->default(true)->after('is_default');

            $table->index(['client_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::table('client_fiscal_data', function (Blueprint $table) {
            $table->dropIndex(['client_id', 'active']);
            $table->dropColumn(['active', 'nickname', 'email']);
        });
    }
};
