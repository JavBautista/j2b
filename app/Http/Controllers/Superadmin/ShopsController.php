<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\User;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\Task;
use App\Models\SubscriptionSetting;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageService;

class ShopsController extends Controller
{
    public function get(Request $request){
       if(!$request->ajax()) return redirect('/');

        $shop = Shop::orderBy('id', 'desc')
                ->when($request->buscar!='', function ($query) use ($request) {
                        return $query->where($request->criterio, 'like', '%'.$request->buscar.'%');
                    })
                ->when($request->estatus != '', function ($query) use ($request) {
                        // Filtrar por estatus
                        if ($request->estatus === 'active') {
                            return $query->where('active', 1);
                        } elseif ($request->estatus === 'inactive') {
                            return $query->where('active', 0);
                        }
                    })
                ->paginate(15);

        return [
            'pagination'=>[
                'total'=> $shop->total(),
                'current_page'=> $shop->currentPage(),
                'per_page'=> $shop->perPage(),
                'last_page'=> $shop->lastPage(),
                'from'=> $shop->firstItem(),
                'to'=> $shop->lastItem(),
            ],
            'shops'=>$shop,
        ];
    }

    public function store(Request $request)
    {
        if(!$request->ajax()) return redirect('/');

        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        //Desde la app validamos en wl whatsapp asi:
        //$wa = (isset($request['whatsapp']))?$request['whatsapp']:0;

        // Obtener configuración de trial desde BD
        $trialDays = SubscriptionSetting::get('trial_days', 30);

        // Obtener plan BASIC y sus precios de referencia
        $defaultPlanId = 2; // Plan BASIC
        $plan = \App\Models\Plan::find($defaultPlanId);

        $shop= new Shop();
        $shop->plan_id = $defaultPlanId;
        $shop->active = 1;
        $shop->name = $request->name; // Solo name es obligatorio
        $shop->is_trial = true;
        $shop->trial_ends_at = now()->addDays($trialDays);
        $shop->subscription_status = 'trial';
        // Tomar precios del plan como referencia (quedan fijos para esta tienda)
        $shop->monthly_price = $plan?->price;
        $shop->yearly_price = $plan?->yearly_price;
        $shop->billing_cycle = 'monthly'; // Por defecto mensual
        $shop->description = $request->description;
        $shop->zip_code = $request->zip_code;
        $shop->address = $request->address;
        $shop->number_out = $request->number_out;
        $shop->number_int = $request->number_int;
        $shop->district = $request->district;
        $shop->city = $request->city;
        $shop->state = $request->state;
        $shop->whatsapp = $request->whatsapp;
        $shop->phone = $request->phone;
        $shop->email = $request->email;
        $shop->bank_name = $request->bank_name;
        $shop->bank_number = $request->bank_number;
        $shop->web = $request->web;
        $shop->facebook = $request->facebook;
        $shop->twitter = $request->twitter;
        $shop->instagram = $request->instagram;
        $shop->pinterest = $request->pinterest;
        $shop->video_channel = $request->video_channel;
        $shop->slogan = $request->slogan;
        $shop->presentation = $request->presentation;
        $shop->mission = $request->mission;
        $shop->vision = $request->vision;
        $shop->values = $request->values;
        $shop->bank_number_secondary = $request->bank_number_secondary;
        $shop->owner_name = $request->owner_name;
        $shop->cutoff = 1;
        $shop->save();
    }//store()

    public function update(Request $request)
    {
        if(!$request->ajax()) return redirect('/');
        $shop= Shop::findOrFail($request->id);
        $shop->description = $request->description;
        $shop->zip_code = $request->zip_code;
        $shop->address = $request->address;
        $shop->number_out = $request->number_out;
        $shop->number_int = $request->number_int;
        $shop->district = $request->district;
        $shop->city = $request->city;
        $shop->state = $request->state;
        $shop->whatsapp = $request->whatsapp;
        $shop->phone = $request->phone;
        $shop->email = $request->email;
        $shop->bank_name = $request->bank_name;
        $shop->bank_number = $request->bank_number;
        $shop->web = $request->web;
        $shop->facebook = $request->facebook;
        $shop->twitter = $request->twitter;
        $shop->instagram = $request->instagram;
        $shop->pinterest = $request->pinterest;
        $shop->video_channel = $request->video_channel;
        $shop->slogan = $request->slogan;
        $shop->presentation = $request->presentation;
        $shop->mission = $request->mission;
        $shop->vision = $request->vision;
        $shop->values = $request->values;
        $shop->bank_number_secondary = $request->bank_number_secondary;
        $shop->owner_name = $request->owner_name;
        $shop->save();
    }//update()

    public function updateCutoff(Request $request)
    {
        if(!$request->ajax()) return redirect('/');
        $shop= Shop::findOrFail($request->id);
        $shop->cutoff = $request->cutoff;

        $shop->save();
    }//updateCutoff()

    public function active(Request $request)
    {
        if(!$request->ajax()) return redirect('/');
        $shop= Shop::findOrFail($request->id);
        $shop->active= 1;
        $shop->save();

        // Reactivar todos los usuarios de esta tienda
        User::where('shop_id', $shop->id)->update(['active' => 1]);
    }//active()

    public function deactive(Request $request)
    {
        if(!$request->ajax()) return redirect('/');
        $shop= Shop::findOrFail($request->id);
        $shop->active= 0;
        $shop->save();

        // Desactivar todos los usuarios de esta tienda en cascada
        User::where('shop_id', $shop->id)->update(['active' => 0]);
    }//deactive()

    public function uploadLogo(Request $request){
        $request->validate([
            'logo' => 'required|file|mimes:jpeg,png,jpg,gif,svg',
        ]);

        $shop= Shop::findOrFail($request->shop_id);
        $file = $shop->logo;
        if($file){
            $existe = Storage::disk('public')->exists($file);
            if($existe){
                Storage::disk('public')->delete($file);
            }
        }
        // Procesar y optimizar la imagen
        $imageService = new ImageService();
        $shop->logo = $imageService->processAndStore($request->file('logo'), 'shop_logos');
        $shop->save();

    }//uploadLogo()

    /**
     * Obtener info básica de una tienda (para modal reutilizable)
     */
    public function getInfo($id)
    {
        $shop = Shop::with('plan', 'owner')->findOrFail($id);

        return response()->json([
            'ok' => true,
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'description' => $shop->description,
                'owner_name' => $shop->owner_name,
                'active' => $shop->active,
                'created_at' => $shop->created_at ? $shop->created_at->format('d/m/Y') : null,

                // Contacto
                'email' => $shop->email,
                'phone' => $shop->phone,
                'whatsapp' => $shop->whatsapp,

                // Dirección
                'address' => $shop->address,
                'number_out' => $shop->number_out,
                'number_int' => $shop->number_int,
                'district' => $shop->district,
                'city' => $shop->city,
                'state' => $shop->state,
                'zip_code' => $shop->zip_code,

                // Bancario
                'bank_name' => $shop->bank_name,
                'bank_number' => $shop->bank_number,

                // Redes
                'web' => $shop->web,
                'facebook' => $shop->facebook,
                'instagram' => $shop->instagram,

                // Suscripción
                'plan_name' => $shop->plan ? $shop->plan->name : null,
                'subscription_status' => $shop->subscription_status,
                'is_trial' => $shop->is_trial,
                'monthly_price' => $shop->monthly_price,

                // Owner asignado
                'owner' => $shop->owner ? [
                    'id' => $shop->owner->id,
                    'name' => $shop->owner->name,
                    'email' => $shop->owner->email,
                ] : null,
            ]
        ]);
    }

    /**
     * Obtener estadísticas de actividad de una tienda
     */
    public function getStats($id)
    {
        $shop = Shop::findOrFail($id);

        // Usuarios por rol
        $users = User::where('shop_id', $id)
            ->with('roles')
            ->get();

        $userStats = [
            'total' => $users->count(),
            'activos' => $users->where('active', 1)->count(),
            'inactivos' => $users->where('active', 0)->count(),
            'admins_full' => 0,
            'admins_limitados' => 0,
            'colaboradores' => 0,
            'clientes' => 0,
            'otros' => 0,
        ];

        foreach ($users as $user) {
            if ($user->roles->isEmpty()) {
                $userStats['otros']++;
                continue;
            }

            $roleName = strtolower($user->roles->first()->name);

            if ($roleName === 'admin') {
                if ($user->limited) {
                    $userStats['admins_limitados']++;
                } else {
                    $userStats['admins_full']++;
                }
            } elseif ($roleName === 'colaborador') {
                $userStats['colaboradores']++;
            } elseif ($roleName === 'cliente') {
                $userStats['clientes']++;
            } else {
                $userStats['otros']++;
            }
        }

        // Clientes registrados
        $clientsCount = Client::where('shop_id', $id)->count();
        $clientsActivos = Client::where('shop_id', $id)->where('active', 1)->count();

        // Notas de venta (receipts)
        $receiptsTotal = Receipt::where('shop_id', $id)->count();
        $receiptsUltimos30Dias = Receipt::where('shop_id', $id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $ultimaVenta = Receipt::where('shop_id', $id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Tareas
        $tasksTotal = Task::where('shop_id', $id)->count();
        $tasksPendientes = Task::where('shop_id', $id)
            ->where('status', 'PENDIENTE')
            ->count();
        $tasksUltimos30Dias = Task::where('shop_id', $id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Determinar nivel de actividad
        $actividadScore = 0;
        if ($clientsCount > 0) $actividadScore += 1;
        if ($receiptsTotal > 0) $actividadScore += 1;
        if ($receiptsUltimos30Dias > 0) $actividadScore += 2;
        if ($tasksTotal > 0) $actividadScore += 1;

        $nivelActividad = 'sin_actividad';
        if ($actividadScore >= 4) {
            $nivelActividad = 'alta';
        } elseif ($actividadScore >= 2) {
            $nivelActividad = 'media';
        } elseif ($actividadScore >= 1) {
            $nivelActividad = 'baja';
        }

        return response()->json([
            'ok' => true,
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'created_at' => $shop->created_at,
                'subscription_status' => $shop->subscription_status,
                'plan_id' => $shop->plan_id,
            ],
            'usuarios' => $userStats,
            'clientes' => [
                'total' => $clientsCount,
                'activos' => $clientsActivos,
            ],
            'ventas' => [
                'total' => $receiptsTotal,
                'ultimos_30_dias' => $receiptsUltimos30Dias,
                'ultima_venta' => $ultimaVenta ? $ultimaVenta->created_at->format('d/m/Y H:i') : null,
            ],
            'tareas' => [
                'total' => $tasksTotal,
                'pendientes' => $tasksPendientes,
                'ultimos_30_dias' => $tasksUltimos30Dias,
            ],
            'nivel_actividad' => $nivelActividad,
        ]);
    }
}
