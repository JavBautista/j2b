<?php

namespace App\Services\Receipts;

use App\Models\Shop;

/**
 * Calcula subtotal/IVA/total de una nota a partir de items + descuento global + flag,
 * usando la tasa de impuesto configurada en la tienda. El backend NO debe confiar en los
 * valores que envía el cliente — son recalculados aquí para garantizar consistencia fiscal.
 */
class ReceiptTaxCalculator
{
    /**
     * @param Shop  $shop
     * @param array $items  Cada item normalizado: ['qty'=>n, 'price'=>n, 'discount'=>n, 'is_complimentary'=>bool]
     *                      Soporta arrays asociativos u objetos stdClass.
     * @param float $descuentoGlobal  Descuento aplicado al subtotal (no por línea).
     * @param bool  $aplicarIva  Si la nota lleva IVA o no (el monto se calcula aquí).
     * @param float|null $taxRate  Tasa en porcentaje (ej. 16, 8, 0). Si es null usa la de la tienda.
     * @return array{
     *   subtotal: float,
     *   iva: float,
     *   total: float,
     *   descuento_global: float,
     *   detail_subtotals: array<int, float>,
     *   aplicar_iva: bool
     * }
     */
    public static function calcular(Shop $shop, array $items, float $descuentoGlobal, bool $aplicarIva, ?float $taxRate = null): array
    {
        $detailSubtotals = [];
        $subtotal = 0.0;

        foreach (array_values($items) as $idx => $raw) {
            $item = is_array($raw) ? $raw : (array) $raw;

            $isComplimentary = !empty($item['is_complimentary']);
            $qty             = max(0.0, (float) ($item['qty'] ?? 0));
            $price           = max(0.0, (float) ($item['price'] ?? 0));
            $discount        = max(0.0, (float) ($item['discount'] ?? 0));

            $bruto = $qty * $price;
            // El descuento de línea no puede dejar el subtotal negativo
            $neto  = max(0.0, $bruto - $discount);

            $subtotalLinea = $isComplimentary ? 0.0 : round($neto, 2);

            $detailSubtotals[$idx] = $subtotalLinea;
            $subtotal += $subtotalLinea;
        }

        $subtotal = round($subtotal, 2);

        // Descuento global: clamp a [0, subtotal] para nunca dejar base negativa
        $descuentoGlobal = max(0.0, $descuentoGlobal);
        if ($descuentoGlobal > $subtotal) {
            $descuentoGlobal = $subtotal;
        }
        $descuentoGlobal = round($descuentoGlobal, 2);

        $baseNeta = round($subtotal - $descuentoGlobal, 2);

        $taxDecimal = $taxRate !== null ? ($taxRate / 100) : $shop->getTaxDecimal();
        $iva = $aplicarIva ? round($baseNeta * $taxDecimal, 2) : 0.0;

        $total = round($baseNeta + $iva, 2);

        return [
            'subtotal'         => $subtotal,
            'iva'              => $iva,
            'total'            => $total,
            'descuento_global' => $descuentoGlobal,
            'detail_subtotals' => $detailSubtotals,
            'aplicar_iva'      => $aplicarIva,
        ];
    }

    /**
     * Compara los totales recalculados contra los enviados por el cliente.
     * Útil para logging: detectar clientes con cálculo desactualizado.
     *
     * @return array{discrepancia: bool, deltas: array<string, float>}
     */
    public static function compararConCliente(array $calc, float $subtotalCliente, float $ivaCliente, float $totalCliente, float $tolerancia = 0.02): array
    {
        $deltas = [
            'subtotal' => round($subtotalCliente - $calc['subtotal'], 4),
            'iva'      => round($ivaCliente - $calc['iva'], 4),
            'total'    => round($totalCliente - $calc['total'], 4),
        ];

        $discrepancia = (
            abs($deltas['subtotal']) > $tolerancia ||
            abs($deltas['iva']) > $tolerancia ||
            abs($deltas['total']) > $tolerancia
        );

        return [
            'discrepancia' => $discrepancia,
            'deltas'       => $deltas,
        ];
    }
}
