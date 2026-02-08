<?php

namespace App\Console\Commands;

use App\Models\Receipt;
use App\Models\Notification;
use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClientPaymentReminderNotifications extends Command
{
    protected $signature = 'create:client_payment_reminders';
    protected $description = 'Notifica a clientes con APP cuando su pago a crédito está por vencer (3 días y 1 día antes)';

    public function handle()
    {
        $this->log("Se inicia proceso de recordatorios de pago para clientes.");

        $today = Carbon::now()->startOfDay();
        $date_in_3 = $today->copy()->addDays(3)->toDateString();
        $date_in_1 = $today->copy()->addDays(1)->toDateString();

        $this->log("Hoy: {$today->toDateString()} | Buscando pagos con fecha: {$date_in_3} (3 días) y {$date_in_1} (1 día)");

        // Buscar receipts a crédito activos cuya fecha de pago sea en 3 o 1 día
        // y cuyo cliente tenga user_id (app)
        $receipts = Receipt::with('client')
            ->where('credit', 1)
            ->where('credit_completed', 0)
            ->whereIn('credit_date_notification', [$date_in_3, $date_in_1])
            ->whereHas('client', function ($query) {
                $query->whereNotNull('user_id');
            })
            ->get();

        $this->log("Receipts encontrados: {$receipts->count()}");

        if ($receipts->isEmpty()) {
            $this->log("No hay pagos por notificar. Fin del proceso.");
            return;
        }

        $firebaseService = app(FirebaseService::class);
        $notified = 0;

        foreach ($receipts as $receipt) {
            $client = $receipt->client;
            $paymentDate = Carbon::parse($receipt->credit_date_notification);
            $days_left = (int) $today->diffInDays($paymentDate);

            $this->log("Procesando receipt {$receipt->id} | Cliente: {$client->name} (user_id: {$client->user_id}) | Pago: {$receipt->credit_date_notification} | Faltan: {$days_left} días | Tipo: {$receipt->credit_type}");

            $description = $this->buildMessage($days_left, $receipt->credit_date_notification, $receipt->credit_type);

            // 1. Notificación de BD
            $notification = new Notification();
            $notification->user_id     = $client->user_id;
            $notification->description = $description;
            $notification->type        = 'pago_cliente';
            $notification->action      = 'payment_reminder';
            $notification->data        = $receipt->id;
            $notification->read        = 0;
            $notification->visible     = 1;
            $notification->save();

            $this->log("Notificación BD creada para user {$client->user_id}");

            // 2. Push FCM
            try {
                $title = ($days_left === 1)
                    ? 'Tu pago vence mañana'
                    : "Tu pago vence en {$days_left} días";

                $firebaseService->sendToUser(
                    $client->user_id,
                    $title,
                    $description,
                    [
                        'type'       => 'payment_reminder',
                        'receipt_id' => (string) $receipt->id,
                        'days_left'  => (string) $days_left,
                        'action'     => 'open_payments',
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

    private function buildMessage(int $daysLeft, string $date, ?string $creditType): string
    {
        $formattedDate = Carbon::parse($date)->format('d/m/Y');
        $typeLabel = $this->getCreditTypeLabel($creditType);

        if ($daysLeft === 1) {
            return "Tu pago {$typeLabel}vence mañana ({$formattedDate}). No olvides realizar tu pago.";
        }

        return "Tu pago {$typeLabel}vence en {$daysLeft} días ({$formattedDate}). Recuerda preparar tu pago.";
    }

    private function getCreditTypeLabel(?string $creditType): string
    {
        return match ($creditType) {
            'semanal'   => 'semanal ',
            'quincenal' => 'quincenal ',
            'mensual'   => 'mensual ',
            default     => '',
        };
    }

    private function log(string $message): void
    {
        $text = "[" . now() . "] " . $message;
        Storage::append("log_ntf_client_payment_reminders.txt", $text);
        Log::info("ClientPaymentReminder: " . $message);
    }
}
