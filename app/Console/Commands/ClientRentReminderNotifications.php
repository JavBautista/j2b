<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Rent;
use App\Models\Notification;
use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClientRentReminderNotifications extends Command
{
    protected $signature = 'create:client_rent_reminders';
    protected $description = 'Notifica a clientes con APP cuando su renta está por vencer (3 días y 1 día antes)';

    public function handle()
    {
        $this->log("Se inicia proceso de recordatorios de renta para clientes.");

        $today = Carbon::now();
        $day_in_3 = $today->copy()->addDays(3)->day;
        $day_in_1 = $today->copy()->addDays(1)->day;

        $this->log("Hoy: día {$today->day} | Buscando cutoffs: {$day_in_3} (3 días) y {$day_in_1} (1 día)");

        // Buscar rentas activas cuyo cutoff sea en 3 o 1 día, y cuyo cliente tenga user_id (app)
        $rents = Rent::with('client')
            ->where('active', 1)
            ->whereIn('cutoff', [$day_in_3, $day_in_1])
            ->whereHas('client', function ($query) {
                $query->whereNotNull('user_id');
            })
            ->get();

        $this->log("Rentas encontradas: {$rents->count()}");

        if ($rents->isEmpty()) {
            $this->log("No hay rentas por notificar. Fin del proceso.");
            return;
        }

        $firebaseService = app(FirebaseService::class);
        $notified = 0;

        foreach ($rents as $rent) {
            $client = $rent->client;
            $cutoff = (int) $rent->cutoff;
            $days_left = ($cutoff == $day_in_3) ? 3 : 1;

            $this->log("Procesando renta {$rent->id} | Cliente: {$client->name} (user_id: {$client->user_id}) | Cutoff: día {$cutoff} | Faltan: {$days_left} días");

            $description = $this->buildMessage($days_left, $cutoff);

            // 1. Notificación de BD
            $notification = new Notification();
            $notification->user_id     = $client->user_id;
            $notification->description = $description;
            $notification->type        = 'renta_cliente';
            $notification->action      = 'rent_reminder';
            $notification->data        = $rent->id;
            $notification->read        = 0;
            $notification->visible     = 1;
            $notification->save();

            $this->log("Notificación BD creada para user {$client->user_id}");

            // 2. Push FCM
            try {
                $title = ($days_left === 1)
                    ? 'Tu renta vence mañana'
                    : "Tu renta vence en {$days_left} días";

                $firebaseService->sendToUser(
                    $client->user_id,
                    $title,
                    $description,
                    [
                        'type'      => 'rent_reminder',
                        'rent_id'   => (string) $rent->id,
                        'days_left' => (string) $days_left,
                        'action'    => 'open_rents',
                    ]
                );
                $this->log("Push FCM enviado a user {$client->user_id}");
            } catch (\Exception $e) {
                $this->log("Error FCM para user {$client->user_id}: " . $e->getMessage());
            }

            $notified++;
        }

        $this->log("Proceso finalizado. Clientes notificados: {$notified}");
    }

    private function buildMessage(int $daysLeft, int $cutoff): string
    {
        if ($daysLeft === 1) {
            return "Tu renta vence mañana (día {$cutoff}). No olvides realizar tu pago.";
        }

        return "Tu renta vence en {$daysLeft} días (día {$cutoff}). Recuerda preparar tu pago.";
    }

    private function log(string $message): void
    {
        $text = "[" . now() . "] " . $message;
        Storage::append("log_ntf_client_rent_reminders.txt", $text);
        Log::info("ClientRentReminder: " . $message);
    }
}
