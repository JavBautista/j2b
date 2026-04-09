<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('extra_fields_shops', function (Blueprint $table) {
            $table->boolean('apply_to_receipts')->default(true)->after('filterable');
            $table->boolean('apply_to_tasks')->default(false)->after('apply_to_receipts');
        });
    }

    public function down()
    {
        Schema::table('extra_fields_shops', function (Blueprint $table) {
            $table->dropColumn(['apply_to_receipts', 'apply_to_tasks']);
        });
    }
};
