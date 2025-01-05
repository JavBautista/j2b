<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Rent;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class RentsNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:rents_notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creación de notificaciones de rentas';

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
        $log_text = "[" . now() . "] Se inicia proceso de notificaciones de renta.";
        Storage::append("log_ntf_rents.txt", $log_text);
        //Obtenemos el dia de la fecha actual
        $date_today     = Carbon::now();
        $dd_today       = $date_today->day;

        //Obtenemos las rentas activas donde el cutoff sea igual al dia actual
        $rents_today = Rent::with('client')
                    ->where('active',1)
                    ->where('cutoff',$dd_today)
                    ->get();
        //con cada renta obtenida
        foreach ($rents_today as $rent) {


            //Aqui hay que verificar, ya que la renta tiene relacion al cliente, y el cliente es el que tiene la relacion a shop
            $shop = $rent->client->shop;
            $shop_id = $shop->id;
            $ntf_description = trim(($rent->client->name ?? '') . ' ' . ($rent->client->company ?? ''));

            $log_text = "[" . now() . "] Se encuentra renta ".$rent->id." de Shop ".$shop_id." (".$ntf_description.")";
            Storage::append("log_ntf_rents.txt", $log_text);

            //Obtenemos los usuarios tipo admin o superadmin dela tienda a los que se les genrara una notificacion
            $shop_users_admin = User::where('shop_id', $shop_id)
                                ->whereHas('roles', function($query) {
                                    $query->whereIn('role_user.role_id', [1, 2]);
                                })
                                ->where('active', 1)
                                ->get();
            //Creamos las notificaciones para los diferentes usuarios
            foreach($shop_users_admin as $user){
                $log_text = "[" . now() . "] Se genera ntf para user ".$user->id;
                Storage::append("log_ntf_rents.txt", $log_text);

                $new_ntf = new Notification();
                $new_ntf->user_id = $user->id;
                $new_ntf->description = $ntf_description;
                $new_ntf->type = 'renta';
                $new_ntf->action = 'client_id';
                $new_ntf->data = $rent->client->id;
                $new_ntf->read = 0;
                $new_ntf->save();
            }//.foreach($shop_users_admin as $user)
        }//.foreach ($rents_today as $rent)
        $log_text = "[" . now() . "] Termina proceso de ntf de rentas.";
        Storage::append("log_ntf_rents.txt", $log_text);
    }//.handle()
}
