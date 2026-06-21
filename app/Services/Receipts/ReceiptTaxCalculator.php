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
     * @param array $items  Cada item normalizado: ['qty'=>n, 'price'=>n, 'discount'=>n, 'is_complimentary'=>bool, 'aplica_retencion'=>bool]
     *                      Soporta arrays asociativos u objetos stdClass.
     * @param float $descuentoGlobal  Descuento aplicado al subtotal (no por línea). Valor CRUDO tecleado por el usuario.
     * @param bool  $aplicarIva  Si la nota lleva IVA o no (el monto se calcula aquí).
     * @param float|null $taxRate  Tasa en porcentaje (ej. 16, 8, 0). Si es null usa la de la tienda.
     * @param string $descuentoConcepto  '%' o '$'. Define cómo interpretar $descuentoGlobal. Default '$'.
     * @param array|null $retencion  Retenciones a aplicar (snapshot). Estructura:
     *                      ['isr_aplica'=>bool, 'isr_tasa'=>float, 'iva_aplica'=>bool, 'iva_tasa'=>float].
     *                      Las tasas van en FACTOR decimal (0.10 = 10%), igual que cfdi_emisores.ret_*_default_tasa.
     *                      Si es null, no hay retenciones (comportamiento idéntico al histórico).
     * @return array{
     *   subtotal: float,
     *   iva: float,
     *   total: float,
     *   descuento_global: float,
     *   descuento_pesos: float,
     *   detail_subtotals: array<int, float>,
     *   aplicar_iva: bool,
     *   aplica_retencion: bool,
     *   ret_isr_tasa: float|null,
     *   ret_isr_monto: float,
     *   ret_iva_tasa: float|null,
     *   ret_iva_monto: float,
     *   total_retenciones: float
     * }
     */
    public static function calcular(Shop $shop, array $items, float $descuentoGlobal, bool $aplicarIva, ?float $taxRate = null, string $descuentoConcepto = '$', ?array $retencion = null): array
    {
        $detailSubtotals = [];
        $subtotal = 0.0;
        // Base bruta (antes de descuento global) de las partidas que aplican retención.
        $baseRetencionBruta = 0.0;

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

            // Solo las partidas marcadas (aplica_retencion_default del producto/servicio)
            // y no cortesía suman a la base de retención.
            if (!$isComplimentary && !empty($item['aplica_retencion'])) {
                $baseRetencionBruta += $subtotalLinea;
            }
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

        // ── Retenciones (snapshot) ───────────────────────────────────────────
        // NO alteran subtotal/iva/total (que siguen siendo comerciales). Solo se
        // calculan sobre la base de las partidas que aplican retención, prorrateando
        // el descuento global de forma consistente con la base del IVA.
        $retIsrAplica = !empty($retencion['isr_aplica']);
        $retIvaAplica = !empty($retencion['iva_aplica']);
        $retIsrTasa   = $retIsrAplica ? (float) ($retencion['isr_tasa'] ?? 0) : null;
        $retIvaTasa   = $retIvaAplica ? (float) ($retencion['iva_tasa'] ?? 0) : null;

        $baseRetencion = round($baseRetencionBruta, 2);
        if ($descuentoPesos > 0 && $subtotal > 0 && $baseRetencion > 0) {
            $baseRetencion = round($baseRetencion - ($descuentoPesos * $baseRetencion / $subtotal), 2);
        }

        $retIsrMonto      = $retIsrAplica ? round($baseRetencion * (float) $retIsrTasa, 2) : 0.0;
        $retIvaMonto      = $retIvaAplica ? round($baseRetencion * (float) $retIvaTasa, 2) : 0.0;
        $totalRetenciones = round($retIsrMonto + $retIvaMonto, 2);

        return [
            'subtotal'          => $subtotal,
            'iva'               => $iva,
            'total'             => $total,
            'descuento_global'  => $descuentoCrudo,   // CRUDO (ej. 7 si es %); mismo contrato que hoy
            'descuento_pesos'   => $descuentoPesos,   // informativo: descuento ya convertido a pesos
            'detail_subtotals'  => $detailSubtotals,
            'aplicar_iva'       => $aplicarIva,
            'aplica_retencion'  => $retIsrAplica || $retIvaAplica,
            'ret_isr_tasa'      => $retIsrTasa,
            'ret_isr_monto'     => $retIsrMonto,
            'ret_iva_tasa'      => $retIvaTasa,
            'ret_iva_monto'     => $retIvaMonto,
            'total_retenciones' => $totalRetenciones,
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
