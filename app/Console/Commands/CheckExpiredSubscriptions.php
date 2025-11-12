<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Models\SubscriptionSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica trials y suscripciones vencidas, aplica periodos de gracia y bloquea shops';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando suscripciones vencidas...');

        $gracePeriodDays = SubscriptionSetting::get('grace_period_days', 7);

        // 1. Verificar trials vencidos
        $this->checkExpiredTrials($gracePeriodDays);

        // 2. Verificar suscripciones vencidas (pasar a grace period)
        $this->checkExpiredSubscriptions($gracePeriodDays);

        // 3. Verificar grace periods vencidos (bloquear shop)
        $this->checkExpiredGracePeriods();

        $this->info('✅ Verificación completada');
        return 0;
    }

    private function checkExpiredTrials($gracePeriodDays)
    {
        $expiredTrials = Shop::where('is_trial', true)
            ->where('trial_ends_at', '<=', now())
            ->where('subscription_status', 'trial')
            ->get();

        foreach ($expiredTrials as $shop) {
            $shop->update([
                'is_trial' => false,
                'subscription_status' => 'grace_period',
                'grace_period_ends_at' => now()->addDays($gracePeriodDays),
            ]);

            $this->warn("⚠️  Shop {$shop->id} ({$shop->name}) - Trial vencido, entrando en periodo de gracia ({$gracePeriodDays} días)");

            Log::info("Trial vencido: Shop {$shop->id}, periodo de gracia hasta: {$shop->grace_period_ends_at}");

            // TODO: Enviar email notificación "Tu trial ha vencido, tienes X días para activar un plan"
        }

        $this->info("Trials vencidos: {$expiredTrials->count()}");
    }

    private function checkExpiredSubscriptions($gracePeriodDays)
    {
        $expiredSubscriptions = Shop::where('is_trial', false)
            ->where('subscription_status', 'active')
            ->where('subscription_ends_at', '<=', now())
            ->get();

        foreach ($expiredSubscriptions as $shop) {
            $shop->update([
                'subscription_status' => 'grace_period',
                'grace_period_ends_at' => now()->addDays($gracePeriodDays),
            ]);

            $this->warn("⚠️  Shop {$shop->id} ({$shop->name}) - Suscripción vencida, entrando en periodo de gracia ({$gracePeriodDays} días)");

            Log::info("Suscripción vencida: Shop {$shop->id}, periodo de gracia hasta: {$shop->grace_period_ends_at}");

            // TODO: Enviar email "Tu suscripción ha vencido, renueva antes de X fecha"
        }

        $this->info("Suscripciones vencidas: {$expiredSubscriptions->count()}");
    }

    private function checkExpiredGracePeriods()
    {
        $expiredGracePeriods = Shop::where('subscription_status', 'grace_period')
            ->where('grace_period_ends_at', '<=', now())
            ->get();

        foreach ($expiredGracePeriods as $shop) {
            $shop->update([
                'subscription_status' => 'expired',
                'active' => false, // 🔒 BLOQUEAR SHOP
            ]);

            $this->error("🔒 Shop {$shop->id} ({$shop->name}) - Periodo de gracia vencido, SHOP BLOQUEADO");

            Log::warning("Shop bloqueado: Shop {$shop->id}, periodo de gracia terminado");

            // TODO: Enviar email "Tu tienda ha sido bloqueada, reactiva tu plan ahora"
        }

        $this->info("Shops bloqueados: {$expiredGracePeriods->count()}");
    }
}
