<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Tasa de impuesto del catálogo de una tienda (ej. IVA 16%, IVA Frontera 8%, Exento).
 * Una nota guarda el VALOR (rate/name) congelado, no la referencia — ver Receipt.
 */
class ShopTaxRate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'rate'       => 'decimal:2',
        'is_default' => 'boolean',
        'active'     => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function getTaxDecimal(): float
    {
        return ((float) $this->rate) / 100;
    }

    /**
     * Siembra la tasa default del catálogo a partir del tax_rate/tax_name de la tienda.
     * Idempotente: si la tienda ya tiene tasas, no hace nada. Se llama al dar de alta una
     * tienda para que su catálogo no quede vacío (espejo del seed de la migración).
     */
    public static function seedDefaultForShop(Shop $shop): void
    {
        if ($shop->taxRates()->exists()) {
            return;
        }

        $rate = $shop->tax_rate ?? 16.00;
        $taxName = $shop->tax_name ?: 'IVA';
        $name = ((float) $rate) > 0
            ? $taxName . ' ' . rtrim(rtrim(number_format((float) $rate, 2, '.', ''), '0'), '.') . '%'
            : 'Exento';

        $shop->taxRates()->create([
            'name'       => mb_substr($name, 0, 30),
            'rate'       => $rate,
            'is_default' => true,
            'active'     => true,
        ]);
    }

    /**
     * Resuelve la tasa a aplicar en una nota: la elegida por el cliente ($taxRateId)
     * si pertenece a la tienda y está activa; si no, la default de la tienda.
     * Retorna null si la tienda no tiene catálogo (el caller cae a Shop::getTaxRate()).
     */
    public static function resolveForShop(Shop $shop, $taxRateId = null): ?ShopTaxRate
    {
        if ($taxRateId) {
            $rate = $shop->taxRates()->where('id', $taxRateId)->where('active', true)->first();
            if ($rate) {
                return $rate;
            }
        }
        return $shop->defaultTaxRate();
    }
}
