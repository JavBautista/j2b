<?php

namespace App\Services\Suscripciones;

use App\Models\Shop;
use App\Models\Subscription;
use Carbon\Carbon;

/**
 * Fuente ÚNICA de verdad para el cálculo de períodos de suscripción.
 *
 * Regla de oro (confirmada con el usuario 2026-06-23):
 *  - El día de corte `cutoff` (día de pago) es FIJO. Se fija solo en el primer pago y NUNCA
 *    lo mueve un pago tardío, la gracia, ni que la tienda se haya vencido.
 *  - Cada pago avanza el vencimiento exactamente un ciclo, anclado al día de corte.
 *  - Un plan mensual nunca debe exceder ~31 días de vigencia (salvo prepago genuino).
 *
 * Sustituye la lógica de fechas duplicada/inconsistente que vivía en
 * SuperAdminController (registerPaymentJson / getNextPeriodInfo / deletePayment / updatePayment).
 */
class SubscriptionPeriodService
{
    /**
     * Día de corte FIJO de la tienda.
     * Solo se calcula desde la fecha de pago cuando la tienda aún no tiene cutoff (primer pago).
     */
    public function resolveCutoff(Shop $shop, Carbon $paymentDate): int
    {
        return $shop->cutoff ?: $paymentDate->day;
    }

    /**
     * Calcula el período (inicio y fin) que cubre un pago, anclado al día de corte fijo.
     *
     * @param  string  $cycle  'monthly' | 'yearly'
     * @return array{start: Carbon, end: Carbon}
     */
    public function resolveNextPeriod(Shop $shop, string $cycle, Carbon $paymentDate): array
    {
        $cutoff = $this->resolveCutoff($shop, $paymentDate);
        $currentEnd = $shop->subscription_ends_at ? $shop->subscription_ends_at->copy() : null;

        return $this->computePeriod($cutoff, $currentEnd, $cycle, $paymentDate);
    }

    /**
     * Núcleo del cálculo con primitivos (sin depender del modelo Shop).
     * Reutilizable por el comando de reparación que reproduce el historial (replay).
     *
     * @param  string  $cycle  'monthly' | 'yearly'
     * @return array{start: Carbon, end: Carbon}
     */
    public function computePeriod(int $cutoff, ?Carbon $currentEnd, string $cycle, Carbon $paymentDate): array
    {
        $payDay = $paymentDate->copy()->startOfDay();
        $end = $currentEnd ? $currentEnd->copy()->startOfDay() : null;

        if ($end && $end->gt($payDay)) {
            // A tiempo / prepago: el vencimiento vigente está en el futuro → extender desde ahí.
            $periodStart = $end->copy();
        } else {
            // Tardío / vencido: este pago cubre el ciclo en curso → última ocurrencia del
            // día de corte <= fecha de pago. La gracia NO mueve la fecha.
            $periodStart = $this->lastCutoffOnOrBefore($payDay, $cutoff);
        }

        $periodEnd = $cycle === 'yearly'
            ? $periodStart->copy()->addYearsNoOverflow(1)
            : $periodStart->copy()->addMonthsNoOverflow(1);

        $periodEnd = $this->anchorToCutoff($periodEnd, $cutoff);

        return ['start' => $periodStart, 'end' => $periodEnd];
    }

    /**
     * Reconstruye el estado denormalizado de la tienda (`subscription_ends_at`,
     * `last_payment_at`, `subscription_status`) desde el ledger ACTIVO de pagos.
     * Se llama tras cancelar o editar un pago para evitar fechas colgadas/infladas.
     *
     * NOTA: `cutoff` NO se recalcula aquí (es el día fijo guardado en la tienda).
     */
    public function rebuildShopFromLedger(Shop $shop): void
    {
        $ultimo = Subscription::where('shop_id', $shop->id)
            ->where('status', 'active')
            ->orderByDesc('ends_at')
            ->first();

        if (!$ultimo) {
            // Sin cobertura activa: no fabricar fecha. Marcar para revisión del superadmin.
            $shop->update([
                'subscription_ends_at' => null,
                'subscription_status'  => 'expired',
            ]);
            return;
        }

        $end = $ultimo->ends_at->copy();
        $now = now();

        if ($end->gt($now)) {
            $status = 'active';
        } elseif ($shop->grace_period_ends_at && $shop->grace_period_ends_at->gt($now)) {
            $status = 'grace_period';
        } else {
            $status = 'expired';
        }

        $shop->update([
            'subscription_ends_at' => $end,
            'last_payment_at'      => $ultimo->starts_at,
            'subscription_status'  => $status,
        ]);
    }

    /**
     * Última fecha con día = cutoff que sea <= la referencia dada.
     */
    private function lastCutoffOnOrBefore(Carbon $ref, int $cutoff): Carbon
    {
        $candidate = $this->anchorToCutoff($ref, $cutoff);

        if ($candidate->gt($ref)) {
            $candidate = $this->anchorToCutoff($candidate->copy()->subMonthNoOverflow(), $cutoff);
        }

        return $candidate->startOfDay();
    }

    /**
     * Fija el día del mes al cutoff. Si el día no existe en ese mes (ej: 31 en febrero),
     * usa el último día del mes.
     */
    private function anchorToCutoff(Carbon $date, int $cutoff): Carbon
    {
        $daysInMonth = $date->copy()->endOfMonth()->day;
        $day = min($cutoff, $daysInMonth);

        return $date->copy()->day($day);
    }
}
