<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_id')->nullable()->after('id');
            $table->unsignedBigInteger('folio')->default(0)->nullable()->after('shop_id');
            $table->index(['shop_id', 'folio']);
        });

        // Backfill shop_id desde client.shop_id
        DB::table('contracts')
            ->join('clients', 'contracts.client_id', '=', 'clients.id')
            ->update(['contracts.shop_id' => DB::raw('clients.shop_id')]);

        // Backfill folios consecutivos por shop
        $shops = DB::table('contracts')
            ->select('shop_id')
            ->whereNotNull('shop_id')
            ->distinct()
            ->get();

        foreach ($shops as $shop) {
            $contracts = DB::table('contracts')
                ->where('shop_id', $shop->shop_id)
                ->orderBy('id', 'asc')
                ->pluck('id');

            $folio = 1;
            foreach ($contracts as $contractId) {
                DB::table('contracts')
                    ->where('id', $contractId)
                    ->update(['folio' => $folio]);
                $folio++;
            }
        }
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'folio']);
            $table->dropColumn(['shop_id', 'folio']);
        });
    }
};
