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
     * @param float $descuentoGlobal  Descuento aplicado al subtotal (no por línea). Valor CRUDO tecleado por el usuario.
     * @param bool  $aplicarIva  Si la nota lleva IVA o no (el monto se calcula aquí).
     * @param float|null $taxRate  Tasa en porcentaje (ej. 16, 8, 0). Si es null usa la de la tienda.
     * @param string $descuentoConcepto  '%' o '$'. Define cómo interpretar $descuentoGlobal. Default '$'.
     * @return array{
     *   subtotal: float,
     *   iva: float,
     *   total: float,
     *   descuento_global: float,
     *   descuento_pesos: float,
     *   detail_subtotals: array<int, float>,
     *   aplicar_iva: bool
     * }
     */
    public static function calcular(Shop $shop, array $items, float $descuentoGlobal, bool $aplicarIva, ?float $taxRate = null, string $descuentoConcepto = '$'): array
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

        // Descuento global: se interpreta según su concepto ('%' o '$').
        // Se convierte a pesos SOLO para calcular baseNeta/iva/total; el valor que se
        // GUARDA (descuento_global) sigue siendo el crudo, para no romper el contrato
        // con la app/web que recalculan el desglose desde discount + discount_concept.
        $descuentoGlobal = max(0.0, $descuentoGlobal); // crudo, no negativo

        if ($descuentoConcepto === '%') {
            $pct            = min($descuentoGlobal, 100.0);      // un % > 100 no tiene sentido
            $descuentoPesos = round($subtotal * $pct / 100, 2);  // 1000 * 7/100 = 70.00
            $descuentoCrudo = round($pct, 2);                    // se GUARDA el % crudo (ya clampeado a 100)
        } else {
            $descuentoPesos = round($descuentoGlobal, 2);
            if ($descuentoPesos > $subtotal) {
                $descuentoPesos = $subtotal;                     // clamp $ a subtotal
            }
            $descuentoCrudo = $descuentoPesos;                   // en $: crudo == pesos
        }

        $baseNeta = round($subtotal - $descuentoPesos, 2);       // 1000 - 70 = 930

        $taxDecimal = $taxRate !== null ? ($taxRate / 100) : $shop->getTaxDecimal();
        $iva = $aplicarIva ? round($baseNeta * $taxDecimal, 2) : 0.0;

        $total = round($baseNeta + $iva, 2);

        return [
            'subtotal'         => $subtotal,
            'iva'              => $iva,
            'total'            => $total,
            'descuento_global' => $descuentoCrudo,   // CRUDO (ej. 7 si es %); mismo contrato que hoy
            'descuento_pesos'  => $descuentoPesos,   // informativo: descuento ya convertido a pesos
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
