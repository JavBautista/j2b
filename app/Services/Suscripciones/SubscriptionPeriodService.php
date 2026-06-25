<?php

namespace App\Services\Suscripciones;

use App\Models\Shop;
use App\Models\Subscription;
use Carbon\Carbon;

/**
 * Fuente ÚNICA de verdad para el cálculo de períodos de suscripción.
 *
 * Regla (modelo Spotify/Netflix, confirmada con el usuario 2026-06-24):
 *  - El día de corte `cutoff` se fija en el primer pago y se mantiene mientras la tienda NO se
 *    corte: un pago tardío dentro de gracia NO mueve la fecha.
 *  - Si la tienda se CORTÓ (`expired`/`cancelled`) y se reactiva pagando, el corte se RE-ANCLA
 *    al día del nuevo pago (ver resolveCutoff). Una reactivación empieza ciclo nuevo desde ahí.
 *  - Cada pago avanza el vencimiento exactamente un ciclo, anclado al día de corte vigente.
 *  - Un plan mensual nunca debe exceder ~31 días de vigencia (salvo prepago genuino).
 *
 * Sustituye la lógica de fechas duplicada/inconsistente que vivía en
 * SuperAdminController (registerPaymentJson / getNextPeriodInfo / deletePayment / updatePayment).
 */
class SubscriptionPeriodService
{
    /**
     * Día de corte (día de pago) de la tienda.
     *
     * Regla (modelo Spotify/Netflix, confirmada con el usuario 2026-06-24):
     *  - Primer pago (la tienda aún no tiene cutoff) → se fija al día del pago.
     *  - Reactivación tras CORTE TOTAL (`expired`/`cancelled`) → se RE-ANCLA al día del nuevo
     *    pago. Si te cortaron y reactivas meses después, tu fecha de pago es la del reingreso.
     *  - Tienda vigente o en GRACIA (`active`/`grace_period`, aún no cortada) → se RESPETA el
     *    corte fijo (un pago tardío dentro de gracia NO mueve la fecha).
     */
    public function resolveCutoff(Shop $shop, Carbon $paymentDate): int
    {
        if (!$shop->cutoff || $this->wasCutOff($shop)) {
            return $paymentDate->day;
        }

        return $shop->cutoff;
    }

    /**
     * ¿La tienda quedó CORTADA (sin servicio)? Esto distingue una reactivación (re-ancla el
     * corte) de un pago tardío dentro de gracia (respeta el corte). La gracia NO es corte.
     */
    public function wasCutOff(Shop $shop): bool
    {
        return in_array($shop->subscription_status, ['expired', 'cancelled'], true);
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
     * `last_payment_at`, `subscription_status`, `cutoff`) desde el ledger ACTIVO de pagos.
     * Se llama tras cancelar o editar un pago para evitar fechas colgadas/infladas.
     *
     * El `cutoff` se revierte al día del último pago activo: como cada pago ancla su `ends_at`
     * al corte vigente en ese momento, el día del último pago = el corte que debe quedar. Así,
     * cancelar el pago de una reactivación regresa el corte al valor previo (reversibilidad).
     */
    public function rebuildShopFromLedger(Shop $shop): void
    {
        $ultimo = Subscription::where('shop_id', $shop->id)
            ->where('status', 'active')
            ->orderByDesc('ends_at')
            ->first();

        if (!$ultimo) {
            // Sin cobertura activa: no fabricar fecha. Marcar para revisión del superadmin.
            // El corte se deja como está: con status `expired` el próximo pago lo re-ancla.
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
            'cutoff'               => $end->day, // corte vigente = día del último pago activo
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
