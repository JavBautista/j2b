<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Shop;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ShopCutoffNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:shop_cutoff_notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creación de notificaciones de fecha de pago de las tiendas';

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
    public function handle()
    {
        $log_text = "[" . now() . "] Se inicia proceso de notificaciones de fecha pago.";
        Storage::append("log_ntf_shops_cutoff.txt", $log_text);

        // Fecha actual
        $today = Carbon::now();

        // Obtenemos las tiendas activas
        $shops = Shop::where('active', 1)->get();

        // Procesamos cada tienda
        foreach ($shops as $shop) {
            $shop_id = $shop->id;
            $cutoff_day = $shop->cutoff;

            // Calculamos los días restantes para la fecha de corte
            $cutoff_date = Carbon::createFromDate($today->year, $today->month, $cutoff_day);
            if ($cutoff_date->lt($today)) {
                $cutoff_date->addMonth(); // Si ya pasó este mes, ajustamos al próximo mes
            }
            $days_left = $cutoff_date->diffInDays($today);

            $log_text = "[" . now() . "] Tienda encontrada: ID {$shop_id}, días restantes: {$days_left}";
            Storage::append("log_ntf_shops_cutoff.txt", $log_text);

            // Definimos el mensaje según los días restantes
            $ntf_description = null;

            if ($days_left === 7) {
                $ntf_description = "Faltan 7 días para tu fecha de pago. No olvides prepararte.";
            } elseif ($days_left === 3) {
                $ntf_description = "Faltan 3 días para tu fecha de pago. No olvides prepararte";
            } elseif ($days_left === 0) {
                $ntf_description = "Hoy es tu fecha de pago. Por favor, ponte en contacto con nosotros.";
            }

            // Si hay un mensaje, generamos notificaciones
            if ($ntf_description) {
                // Generar notification_group_id único para esta tienda
                $notification_group_id = Notification::generateGroupId();
                
                $shop_users_admin = User::where('shop_id', $shop_id)
                    ->whereHas('roles', function ($query) {
                        $query->whereIn('role_user.role_id', [1, 2]);
                    })
                    ->where('active', 1)
                    ->get();

                foreach ($shop_users_admin as $user) {
                    $log_text = "[" . now() . "] Generando notificación para usuario ID {$user->id}";
                    Storage::append("log_ntf_shops_cutoff.txt", $log_text);

                    Notification::create([
                        'notification_group_id' => $notification_group_id,
                        'user_id' => $user->id,
                        'description' => $ntf_description,
                        'type' => 'tienda_fecha_pago',
                        'action' => 'shop_id',
                        'data' => $shop_id,
                        'read' => 0,
                        'visible' => 1,
                    ]);
                }
            }
        }

        $log_text = "[" . now() . "] Proceso de notificaciones de fecha pago finalizado.";
        Storage::append("log_ntf_shops_cutoff.txt", $log_text);
    }

    /*
    public function handle()
    {
        $log_text = "[" . now() . "] Se inicia proceso de notificaciones de fecha pago.";
        Storage::append("log_ntf_shops_cutoff.txt", $log_text);
        //Obtenemos el dia de la fecha actual
        $date_today     = Carbon::now();
        $dd_today       = $date_today->day;

        //Obtenemos las tiendas activas donde el cutoff sea igual al dia actual
        $shops = Shop::where('active',1)->where('cutoff',$dd_today)->get();

        //con cada tienda obtenida
        foreach ($shops as $shop) {
            $shop_id = $shop->id;
            $log_text = "[" . now() . "] Se encuentra tienda ".$shop_id;
            Storage::append("log_ntf_shops_cutoff.txt", $log_text);

            //Obtenemos los usuarios tipo admin o superadmin dela tienda a los que se les genrara una notificacion
            $shop_users_admin = User::where('shop_id', $shop_id)
                                ->whereHas('roles', function($query) {
                                    $query->whereIn('role_user.role_id', [1, 2]);
                                })
                                ->where('active', 1)
                                ->get();

            $ntf_description = "Hoy es tu fecha de pago. Por favor, ponte en contacto con nosotros.";

            //Creamos las notificaciones para los diferentes usuarios
            foreach($shop_users_admin as $user){
                $log_text = "[" . now() . "] Se genera ntf para user ".$user->id;
                Storage::append("log_ntf_shops_cutoff.txt", $log_text);

                $new_ntf = new Notification();
                $new_ntf->user_id = $user->id;
                $new_ntf->description = $ntf_description;
                $new_ntf->type = 'tienda_fecha_pago';
                $new_ntf->action = 'shop_id';
                $new_ntf->data = $shop_id;
                $new_ntf->read = 0;
                $new_ntf->save();
            }//.foreach($shop_users_admin as $user)
        }//.foreach ($shops as $shop)
        $log_text = "[" . now() . "] Termina proceso de ntf de las tiendas.";
        Storage::append("log_ntf_shops_cutoff.txt", $log_text);
    }//.handle()
    */
}
