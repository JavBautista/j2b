<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('folio')->default(0)->nullable()->after('shop_id');
            $table->index(['shop_id', 'folio']);
        });

        // Asignar folios consecutivos a ordenes existentes por shop
        $shops = DB::table('purchase_orders')->select('shop_id')->distinct()->get();
        foreach ($shops as $shop) {
            $orders = DB::table('purchase_orders')
                ->where('shop_id', $shop->shop_id)
                ->orderBy('id', 'asc')
                ->pluck('id');

            $folio = 1;
            foreach ($orders as $orderId) {
                DB::table('purchase_orders')
                    ->where('id', $orderId)
                    ->update(['folio' => $folio]);
                $folio++;
            }
        }
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'folio']);
            $table->dropColumn('folio');
        });
    }
};
