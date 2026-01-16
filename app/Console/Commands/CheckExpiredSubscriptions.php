<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Models\SubscriptionSetting;
use App\Models\User;
use App\Models\Notification;
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

        // Días de gracia global (se usa como fallback si la tienda no tiene valor propio)
        $globalGracePeriodDays = SubscriptionSetting::get('grace_period_days', 7);

        // 0. Verificar trials que están por vencer (alertas tempranas)
        $this->checkTrialsEnding();

        // 0.5 Verificar suscripciones activas que están por vencer (alertas tempranas)
        $this->checkSubscriptionsEnding();

        // 1. Verificar trials vencidos
        $this->checkExpiredTrials($globalGracePeriodDays);

        // 2. Verificar suscripciones vencidas (pasar a grace period)
        $this->checkExpiredSubscriptions($globalGracePeriodDays);

        // 3. Verificar grace periods vencidos (bloquear shop)
        $this->checkExpiredGracePeriods();

        $this->info('✅ Verificación completada');
        return 0;
    }

    private function checkExpiredTrials($globalGracePeriodDays)
    {
        $expiredTrials = Shop::where('is_trial', true)
            ->where('trial_ends_at', '<=', now())
            ->where('subscription_status', 'trial')
            ->get();

        foreach ($expiredTrials as $shop) {
            // TRIALS SIEMPRE SE BLOQUEAN INMEDIATAMENTE (sin gracia)
            // El grace_period_days solo aplica para clientes que ya pagaron
            $shop->update([
                'is_trial' => false,
                'subscription_status' => 'expired',
                'active' => false,
            ]);

            $this->error("🔒 Shop {$shop->id} ({$shop->name}) - Trial vencido, SHOP BLOQUEADO");

            Log::warning("Trial vencido y bloqueado: Shop {$shop->id}");

            $this->createNotification($shop, 'shop_blocked',
                '🔒 Tu periodo de prueba ha terminado',
                'Tu trial ha vencido. Tu tienda ha sido bloqueada. Contacta con nosotros para activar tu suscripción.',
                'subscription'
            );

            // Desactivar TODOS los usuarios del shop
            User::where('shop_id', $shop->id)->update(['active' => false]);
        }

        $this->info("Trials vencidos (bloqueados): {$expiredTrials->count()}");
    }

    private function checkExpiredSubscriptions($globalGracePeriodDays)
    {
        $expiredSubscriptions = Shop::where('is_trial', false)
            ->where('subscription_status', 'active')
            ->where('subscription_ends_at', '<=', now())
            ->get();

        foreach ($expiredSubscriptions as $shop) {
            // Usar días de gracia de la tienda si existe, sino el global
            $gracePeriodDays = $shop->grace_period_days ?? $globalGracePeriodDays;

            $shop->update([
                'subscription_status' => 'grace_period',
                'grace_period_ends_at' => now()->addDays($gracePeriodDays),
            ]);

            $this->warn("⚠️  Shop {$shop->id} ({$shop->name}) - Suscripción vencida, entrando en periodo de gracia ({$gracePeriodDays} días)");

            Log::info("Suscripción vencida: Shop {$shop->id}, periodo de gracia hasta: {$shop->grace_period_ends_at}");

            // Crear notificación
            $this->createNotification($shop, 'subscription_expired',
                'Tu suscripción ha vencido',
                "Tu suscripción venció. Tienes {$gracePeriodDays} días de gracia para renovar y continuar usando J2B.",
                'subscription'
            );
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

            // Crear notificación
            $this->createNotification($shop, 'shop_blocked',
                '🔒 Tu tienda ha sido bloqueada',
                'Tu periodo de gracia terminó. Tu tienda ha sido bloqueada. Contacta con nosotros para reactivar tu suscripción.',
                'subscription'
            );

            // Desactivar TODOS los usuarios del shop
            \App\Models\User::where('shop_id', $shop->id)->update(['active' => false]);
        }

        $this->info("Shops bloqueados: {$expiredGracePeriods->count()}");
    }

    /**
     * Verificar trials que están por vencer (7 y 3 días antes)
     */
    private function checkTrialsEnding()
    {
        // Trials que vencen en 7 días
        $trialsEnding7Days = Shop::where('is_trial', true)
            ->where('subscription_status', 'trial')
            ->whereDate('trial_ends_at', '=', now()->addDays(7)->startOfDay())
            ->get();

        foreach ($trialsEnding7Days as $shop) {
            $this->createNotification($shop, 'trial_ending_7days',
                '¡Tu trial vence en 7 días!',
                'Te quedan 7 días de prueba. Activa tu plan para continuar usando J2B sin interrupciones.',
                'subscription'
            );
            $this->info("📧 Shop {$shop->id} ({$shop->name}) - Trial vence en 7 días, notificación creada");
        }

        // Trials que vencen en 3 días
        $trialsEnding3Days = Shop::where('is_trial', true)
            ->where('subscription_status', 'trial')
            ->whereDate('trial_ends_at', '=', now()->addDays(3)->startOfDay())
            ->get();

        foreach ($trialsEnding3Days as $shop) {
            $this->createNotification($shop, 'trial_ending_3days',
                '¡Tu trial vence en 3 días!',
                'Solo te quedan 3 días de prueba. ¡No pierdas acceso a tu tienda! Activa tu plan ahora.',
                'subscription'
            );
            $this->warn("📧 Shop {$shop->id} ({$shop->name}) - Trial vence en 3 días, notificación creada");
        }

        $this->info("Alertas de trial: {$trialsEnding7Days->count()} (7 días) + {$trialsEnding3Days->count()} (3 días)");
    }

    /**
     * Verificar suscripciones activas que están por vencer (7 y 3 días antes)
     */
    private function checkSubscriptionsEnding()
    {
        // Suscripciones activas que vencen en 7 días
        $subsEnding7Days = Shop::where('is_trial', false)
            ->where('subscription_status', 'active')
            ->whereDate('subscription_ends_at', '=', now()->addDays(7)->startOfDay())
            ->get();

        foreach ($subsEnding7Days as $shop) {
            $this->createNotification($shop, 'subscription_ending_7days',
                '¡Tu suscripción vence en 7 días!',
                'Tu suscripción está por vencer. Renueva tu plan para continuar usando J2B sin interrupciones.',
                'subscription'
            );
            $this->info("📧 Shop {$shop->id} ({$shop->name}) - Suscripción vence en 7 días, notificación creada");
        }

        // Suscripciones activas que vencen en 3 días
        $subsEnding3Days = Shop::where('is_trial', false)
            ->where('subscription_status', 'active')
            ->whereDate('subscription_ends_at', '=', now()->addDays(3)->startOfDay())
            ->get();

        foreach ($subsEnding3Days as $shop) {
            $this->createNotification($shop, 'subscription_ending_3days',
                '¡Tu suscripción vence en 3 días!',
                'Solo te quedan 3 días de suscripción. ¡Renueva ahora para no perder acceso a tu tienda!',
                'subscription'
            );
            $this->warn("📧 Shop {$shop->id} ({$shop->name}) - Suscripción vence en 3 días, notificación creada");
        }

        $this->info("Alertas de suscripción: {$subsEnding7Days->count()} (7 días) + {$subsEnding3Days->count()} (3 días)");
    }

    /**
     * Crear notificación para todos los admins de un shop
     */
    private function createNotification(Shop $shop, string $type, string $title, string $message, string $action = 'subscription', $data = null)
    {
        // Obtener usuarios admin/superadmin del shop que estén activos
        $admins = User::where('shop_id', $shop->id)
            ->whereHas('roles', function($query) {
                $query->whereIn('role_user.role_id', [1, 2]); // 1=superadmin, 2=admin
            })
            ->where('active', 1)
            ->get();

        // Generar notification_group_id único para agrupar
        $notificationGroupId = Notification::generateGroupId();

        // Crear notificación para cada admin
        foreach ($admins as $admin) {
            Notification::create([
                'notification_group_id' => $notificationGroupId,
                'user_id' => $admin->id,
                'type' => $type,
                'description' => $title,
                'action' => $action,
                'data' => $data ?? $shop->id,
                'read' => false,
                'visible' => true,
            ]);
        }

        Log::info("Notificación creada: Shop {$shop->id}, Tipo: {$type}, Admins notificados: {$admins->count()}");
    }
}
