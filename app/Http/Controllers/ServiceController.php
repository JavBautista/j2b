<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::where('active',1)->orderBy('id','desc')->paginate(10);
        return $services;
    }

    public function store(Request $request)
    {
        $service = new Service;
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
