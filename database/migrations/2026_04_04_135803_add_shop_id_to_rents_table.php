<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_id')->nullable()->after('id');
            $table->index(['shop_id', 'folio']);
        });

        // Backfill: copiar shop_id desde el cliente
        DB::table('rents')
            ->join('clients', 'rents.client_id', '=', 'clients.id')
            ->update(['rents.shop_id' => DB::raw('clients.shop_id')]);
    }

    public function down(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'folio']);
            $table->dropColumn('shop_id');
        });
    }
};
