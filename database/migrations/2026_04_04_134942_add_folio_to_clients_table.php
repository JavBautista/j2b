<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('folio')->default(0)->nullable()->after('shop_id');
            $table->index(['shop_id', 'folio']);
        });

        // Backfill: asignar folios consecutivos a clientes existentes por shop
        $shops = DB::table('clients')->select('shop_id')->distinct()->get();
        foreach ($shops as $shop) {
            $clients = DB::table('clients')
                ->where('shop_id', $shop->shop_id)
                ->orderBy('id', 'asc')
                ->pluck('id');

            $folio = 1;
            foreach ($clients as $clientId) {
                DB::table('clients')
                    ->where('id', $clientId)
                    ->update(['folio' => $folio]);
                $folio++;
            }
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'folio']);
            $table->dropColumn('folio');
        });
    }
};
