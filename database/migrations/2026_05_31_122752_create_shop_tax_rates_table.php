<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Catálogo de tasas de impuesto configurable por tienda.
     * Permite que una nota elija una tasa distinta a la default (ej. 16% vs 8% frontera vs 0% exento).
     * Ver xdev/ventas/PLAN_IMPUESTO_SELECCIONABLE_POR_NOTA.md
     */
    public function up(): void
    {
        Schema::create('shop_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->string('name', 30);                 // etiqueta: "IVA 16%", "IVA Frontera 8%", "Exento"
            $table->decimal('rate', 5, 2);              // 16.00 / 8.00 / 0.00
            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->index(['shop_id', 'active']);
        });

        // Seed: cada tienda existente arranca con su tasa actual como default.
        // Cero ruptura — las notas nuevas preseleccionan esta fila.
        $shops = DB::table('shops')->select('id', 'tax_name', 'tax_rate')->get();
        foreach ($shops as $shop) {
            $rate = $shop->tax_rate ?? 16.00;
            $taxName = $shop->tax_name ?: 'IVA';
            $name = ((float) $rate) > 0
                ? $taxName . ' ' . rtrim(rtrim(number_format((float) $rate, 2, '.', ''), '0'), '.') . '%'
                : 'Exento';

            DB::table('shop_tax_rates')->insert([
                'shop_id'    => $shop->id,
                'name'       => mb_substr($name, 0, 30),
                'rate'       => $rate,
                'is_default' => true,
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_tax_rates');
    }
};
