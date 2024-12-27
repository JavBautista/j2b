<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Receipt;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PaymentsReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment_reminders:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creacion de recordatorios de pagos';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    private function calculateNextNotificationDate($currentDate, $creditType)
    {
        $date = Carbon::parse($currentDate);

        switch ($creditType) {
            case 'semanal':
                return $date->addWeek()->toDateString(); // Sumar 7 días
            case 'quincenal':
                return $date->addDays(15)->toDateString(); // Sumar 15 días
            case 'mensual':
                return $date->addMonth()->toDateString(); // Sumar 1 mes
            default:
                throw new \Exception("Tipo de crédito inválido: {$creditType}");
        }
    }

    public function handle()
    {
        $log_text = "[" . now() . "] Se inicia proceso de recordatorios.";
        Storage::append("log_payment_reminders.txt", $log_text);

        // Obtener los créditos activos
        $receipts = Receipt::with('client')->where('credit', 1)
            ->where('credit_completed', 0)
            ->get();
        // Fecha actual
        $date_today = Carbon::now()->startOfDay(); // Asegurarse de comparar solo la fecha
        foreach ($receipts as $receipt) {
            $log_text = "[" . now() . "] Se encuentra receipt ID: ".$receipt->id;
            Storage::append("log_payment_reminders.txt", $log_text);


            // Comprobar si la fecha actual coincide con la fecha de notificación
            if (Carbon::parse($receipt->credit_date_notification)->startOfDay()->eq($date_today)) {
                //Obtenemos los datos del Receipt
                $shop_id     = $receipt->shop_id;
                $receipt_id  = $receipt->id;
                $client_name = $receipt->client->name;
                //Obtenemos los usuarios tipo admin o superadmin dela tienda
                $shop_users_admin = User::where('shop_id', $shop_id)
                                        ->whereHas('roles', function($query) {
                                            $query->whereIn('role_user.role_id', [1, 2]);
                                        })
                                        ->where('active', 1)
                                        ->get();
                //Crea la notificaciones para los diferentes usuarios
                foreach($shop_users_admin as $user){
                    $new_ntf = new Notification();
                    $new_ntf->user_id = $user->id;
                    $new_ntf->description = 'Recordatorio de Pago: '.$client_name;
                    $new_ntf->type = 'payment_reminders';
                    $new_ntf->action = 'receipt_id';
                    $new_ntf->data = $receipt->id;
                    $new_ntf->read = 0;
                    $new_ntf->save();
                }

                // Calcular la siguiente fecha de notificación
                $next_date = $this->calculateNextNotificationDate($receipt->credit_date_notification, $receipt->credit_type);

                // Actualizar la fecha de notificación en el registro del recibo
                $receipt->credit_date_notification = $next_date;
                $receipt->save();
                // Registrar la operación en el log
                $log_text = "[" . now() . "] Se creó una notificación para el recibo ID {$receipt->id}. Próxima fecha: {$next_date}.";
                Storage::append("log_payment_reminders.txt", $log_text);
            }
        }
        $log_text = "[" . now() . "] Se termina proceso de recordatorios.";
        Storage::append("log_payment_reminders.txt", $log_text);
    }
}
