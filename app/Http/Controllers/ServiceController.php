<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('id','desc')->paginate(10);
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

    public function update(Request $request, Service $service)
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

    public function destroy(Service $service)
    {
        //
    }
}
