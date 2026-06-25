<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Models\Subscription;
use App\Services\Suscripciones\SubscriptionPeriodService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repara (de forma IDEMPOTENTE) las fechas de suscripción que quedaron infladas o con el
 * día de corte driftado por los bugs de cálculo (ver xdev/suscripciones/fix-fechas-pagos/).
 *
 * ⚠️  LIMITACIÓN (2026-06-24): este comando hace REPLAY con UN SOLO día de corte para toda la
 *     historia de la tienda. Bajo la regla correcta (modelo Spotify), el corte se RE-ANCLA cada
 *     vez que la tienda se corta y reactiva → una tienda con reactivaciones tiene VARIOS cortes
 *     a lo largo de su ledger, y este replay los aplastaría. Por eso NO debe usarse para
 *     "arreglar" tiendas que se reactivaron (ej. Taval): daría fechas falsas. Úsalo solo para
 *     inflación pura por apilamiento en tiendas sin reactivación, y siempre revisando el dry-run.
 *
 * Método: REPLAY del ledger. Para cada tienda recalcula, en orden cronológico, el `ends_at`
 * de cada pago activo con la regla canónica (SubscriptionPeriodService), infiriendo el día de
 * corte real del PRIMER pago. Luego sincroniza `shops.subscription_ends_at` y `shops.cutoff`.
 *
 * - Por defecto es DRY-RUN (solo reporta). Con --apply escribe (en transacción por tienda).
 * - NO toca `subscription_status` ni `active` (eso lo reconcilia el cron / el superadmin).
 * - Una tienda ya correcta no genera cambios (idempotente).
 *
 * Uso:
 *   php artisan subscriptions:repair-dates              # dry-run, todas
 *   php artisan subscriptions:repair-dates --shop=23    # dry-run, una tienda
 *   php artisan subscriptions:repair-dates --apply      # aplica
 */
class RepairSubscriptionDates extends Command
{
    protected $signature = 'subscriptions:repair-dates
                            {--apply : Aplica los cambios (por defecto es dry-run)}
                            {--shop= : Reparar solo la tienda con este id}
                            {--cutoff= : Forzar el día de corte (SOLO con --shop, para drift confirmado)}
                            {--allow-extend : Permite mover el vencimiento hacia ADELANTE (por defecto solo colapsa inflados)}';

    protected $description = 'Recalcula (idempotente) fechas de vencimiento y día de corte desde el ledger de pagos';

    public function handle(SubscriptionPeriodService $svc): int
    {
        $apply = (bool) $this->option('apply');
        $shopId = $this->option('shop');
        $cutoffOverride = $this->option('cutoff') !== null ? (int) $this->option('cutoff') : null;
        $allowExtend = (bool) $this->option('allow-extend');

        if ($cutoffOverride !== null && !$shopId) {
            $this->error('--cutoff solo puede usarse junto con --shop (es un override puntual para drift confirmado).');
            return self::FAILURE;
        }

        $this->info($apply
            ? '⚠️  MODO APPLY — se escribirán cambios en la base de datos.'
            : '🔎 DRY-RUN — no se escribe nada. Usa --apply para aplicar.');
        $this->line('Conexión BD: ' . config('database.connections.' . config('database.default') . '.database'));
        $this->newLine();

        $query = Shop::query()->where('is_exempt', false);
        if ($shopId) {
            $query->where('id', $shopId);
        }
        $shops = $query->orderBy('id')->get();

        $rows = [];
        $overdue = [];
        $skippedExtend = [];
        $totalShops = 0;
        $totalPagos = 0;

        foreach ($shops as $shop) {
            $activos = Subscription::where('shop_id', $shop->id)
                ->where('status', 'active')
                ->orderBy('starts_at')
                ->orderBy('id')
                ->get();

            if ($activos->isEmpty()) {
                continue; // tienda sin pagos (trial puro) → no se toca
            }

            // Día de corte: se RESPETA el guardado en la tienda (la fuente establecida).
            // Solo se infiere del primer pago si la tienda nunca tuvo cutoff.
            // Para drift confirmado (ej. Taval), usar --shop con --cutoff para forzarlo.
            $first = $activos->first();
            $firstDate = $first->starts_at ?: $first->created_at;
            $cutoff = $cutoffOverride ?? ((int) $shop->cutoff ?: (int) $firstDate->day);

            // REPLAY: recalcular ends_at de cada pago en orden.
            $runningEnd = null;
            $pagoDiffs = [];
            $nuevosEnds = []; // id => Carbon (para aplicar)
            foreach ($activos as $p) {
                $payDate = ($p->starts_at ?: $p->created_at)->copy();
                $cycle = $p->billing_period ?: 'monthly';
                $period = $svc->computePeriod($cutoff, $runningEnd, $cycle, $payDate);
                $newEnd = $period['end'];
                $nuevosEnds[$p->id] = $newEnd;

                $oldStr = $p->ends_at ? $p->ends_at->format('Y-m-d') : '—';
                if ($oldStr !== $newEnd->format('Y-m-d')) {
                    $pagoDiffs[] = "{$p->id}: {$oldStr}→{$newEnd->format('Y-m-d')}";
                }
                $runningEnd = $newEnd;
            }

            $newShopEnd = $runningEnd; // el del último pago
            $oldShopEnd = $shop->subscription_ends_at ? $shop->subscription_ends_at->format('Y-m-d') : '—';
            $shopEndChanged = ($oldShopEnd !== $newShopEnd->format('Y-m-d'));
            $cutoffChanged = ((int) $shop->cutoff !== $cutoff);

            // Solo actuamos si cambia el ESTADO OPERATIVO (vencimiento o día de corte).
            // Diferencias solo en filas históricas se ignoran (no reescribir recibos emitidos).
            if (!$shopEndChanged && !$cutoffChanged) {
                continue;
            }

            // Seguro: por defecto NUNCA extendemos el vencimiento hacia adelante (solo colapsar
            // inflados). Protege suscripciones anuales/desfasadas de ganar acceso por error.
            $wouldExtend = $shop->subscription_ends_at
                && $newShopEnd->gt($shop->subscription_ends_at->copy()->startOfDay());
            if ($wouldExtend && !$allowExtend) {
                $skippedExtend[] = "#{$shop->id} {$shop->name}: {$oldShopEnd} → {$newShopEnd->format('Y-m-d')}";
                continue;
            }

            $rows[] = [
                $shop->id,
                mb_strimwidth((string) $shop->name, 0, 24, '…'),
                ($shop->cutoff ?? '—') . ($cutoffChanged ? " → {$cutoff}" : ''),
                $oldShopEnd . ($shopEndChanged ? " → {$newShopEnd->format('Y-m-d')}" : ''),
                count($pagoDiffs) ? implode(', ', $pagoDiffs) : '—',
            ];
            $totalShops++;
            $totalPagos += count($pagoDiffs);
            if ($newShopEnd->lt(Carbon::now())) {
                $overdue[] = "#{$shop->id} {$shop->name} → vencería {$newShopEnd->format('Y-m-d')}";
            }

            if ($apply) {
                DB::transaction(function () use ($shop, $activos, $nuevosEnds, $cutoff, $newShopEnd) {
                    foreach ($activos as $p) {
                        if (isset($nuevosEnds[$p->id])) {
                            $p->ends_at = $nuevosEnds[$p->id];
                            $p->save();
                        }
                    }
                    $shop->cutoff = $cutoff;
                    $shop->subscription_ends_at = $newShopEnd;
                    $shop->save();
                });
            }
        }

        if (!empty($skippedExtend)) {
            $this->newLine();
            $this->warn('⏭️  OMITIDAS porque EXTENDERÍAN el vencimiento (revisar a mano; usar --allow-extend si procede):');
            foreach ($skippedExtend as $s) {
                $this->line('   - ' . $s);
            }
        }

        if (empty($rows)) {
            $this->newLine();
            $this->info('✅ No hay tiendas que colapsar (todas consistentes o protegidas por el seguro anti-extensión).');
            return self::SUCCESS;
        }

        $this->table(['shop', 'nombre', 'cutoff', 'subscription_ends_at', 'pagos recalculados'], $rows);
        $this->newLine();
        $this->info("Tiendas con cambios: {$totalShops} | filas de pago recalculadas: {$totalPagos}");

        if (!empty($overdue)) {
            $this->newLine();
            $this->warn('⚠️  Tras la corrección quedarían VENCIDAS (sus días inflados eran falsos):');
            foreach ($overdue as $o) {
                $this->line('   - ' . $o);
            }
            $this->line('   (El cron las pasará a gracia/bloqueo según corresponda.)');
        }

        $this->newLine();
        $this->info($apply
            ? '✅ Cambios aplicados.'
            : 'Dry-run completado. Re-ejecuta con --apply para escribir los cambios.');

        return self::SUCCESS;
    }
}
