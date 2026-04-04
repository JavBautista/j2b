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
            $table->unsignedBigInteger('folio')->default(0)->nullable()->after('client_id');
        });

        // Backfill: asignar folios consecutivos por shop (via client.shop_id)
        $shops = DB::table('rents')
            ->join('clients', 'rents.client_id', '=', 'clients.id')
            ->select('clients.shop_id')
            ->distinct()
            ->get();

        foreach ($shops as $shop) {
            $rents = DB::table('rents')
                ->join('clients', 'rents.client_id', '=', 'clients.id')
                ->where('clients.shop_id', $shop->shop_id)
                ->orderBy('rents.id', 'asc')
                ->pluck('rents.id');

            $folio = 1;
            foreach ($rents as $rentId) {
                DB::table('rents')
                    ->where('id', $rentId)
                    ->update(['folio' => $folio]);
                $folio++;
            }
        }
    }

    public function down(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->dropColumn('folio');
        });
    }
};
