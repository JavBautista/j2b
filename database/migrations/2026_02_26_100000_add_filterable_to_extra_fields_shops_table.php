<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('extra_fields_shops', function (Blueprint $table) {
            $table->boolean('filterable')->default(false)->after('active');
        });

        Schema::table('receipt_info_extra', function (Blueprint $table) {
            $table->index(['receipt_id', 'field_name', 'value'], 'idx_receipt_info_extra_filter');
        });
    }

    public function down()
    {
        Schema::table('extra_fields_shops', function (Blueprint $table) {
            $table->dropColumn('filterable');
        });

        Schema::table('receipt_info_extra', function (Blueprint $table) {
            $table->dropIndex('idx_receipt_info_extra_filter');
        });
    }
};
