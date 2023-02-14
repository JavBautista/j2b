<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        if($buscar==''){
            $services = Service::where('active',1)
                    ->where('shop_id',$shop->id)
                    ->orderBy('id','desc')
                    ->paginate(10);
        }else{
            $services = Service::where('active',1)
                    ->where('shop_id',$shop->id)
                    ->where('name', 'like', '%'.$buscar.'%')
                    ->orderBy('id','desc')
                    ->paginate(10);
        }

        return $services;
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        $service = new Service;
        $service->shop_id=$shop->id;
        $service->active = 1;
        $service->name = $request->name;
        $service->description = $request->description;
        $service->price = $request->price;
        $service->save();
        return response()->json([
            'ok'=>true,
            'service' => $service,
        ]);
    }

    public function update(Request $request)
    {
        $service = Service::findOrFail($request->id);
        $service->name = $request->name;
        $service->description = $request->description;
        $service->price = $request->price;
        $service->save();
        return response()->json([
            'ok'=>true,
            'service' => $service,
        ]);
    }

    public function inactive(Request $request)
    {
        $service = Service::findOrFail($request->id);
        $service->active = 0;
        $service->save();
        return response()->json([
            'ok'=>true
        ]);
    }
}
