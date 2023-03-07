<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RentDetail;

class EquipmentController extends Controller
{
    public function index(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;
        if($buscar==''){
            $equipments = RentDetail::where('active',1)
                    ->where('rent_id',0)
                    ->where('shop_id',$shop->id)
                    ->orderBy('id','desc')
                    ->paginate(10);
        }else{
            $equipments = RentDetail::where('active',1)
                    ->where('rent_id',0)
                    ->where('shop_id',$shop->id)
                    ->where('trademark', 'like', '%'.$buscar.'%')
                    ->orWhere('model', 'like', '%'.$buscar.'%')
                    ->orWhere('serial_number', 'like', '%'.$buscar.'%')
                    ->orderBy('id','desc')
                    ->paginate(10);
        }

        return $equipments;
    }//index()
    
    public function store(Request $request){
        
        $user = $request->user();
        $shop = $user->shop;

        $now = now();

        $equipment = new RentDetail();
        $equipment->active = 1;
        $equipment->rent_id = 0;
        $equipment->shop_id = $shop->id;
        $equipment->trademark = $request->trademark;
        $equipment->model = $request->model;
        $equipment->serial_number = $request->serial_number;
        $equipment->rent_price = $request->rent_price;
        $equipment->monochrome = $request->monochrome;
        $equipment->pages_included_mono = $request->pages_included_mono;
        $equipment->extra_page_cost_mono = $request->extra_page_cost_mono;
        $equipment->counter_mono = $request->counter_mono;
        $equipment->update_counter_mono = $now;
        $equipment->color = $request->color;
        $equipment->pages_included_color = $request->pages_included_color;
        $equipment->extra_page_cost_color = $request->extra_page_cost_color;
        $equipment->counter_color = $request->counter_color;
        $equipment->update_counter_color = $now;
        $equipment->save();

        return response()->json([
            'ok'=>true,
            'equipment' => $equipment,
        ]);
    }//.store

    public function update(Request $request){
        $equipment = RentDetail::findOrFail($request->id);
        $now = now();
        $equipment->trademark = $request->trademark;
        $equipment->model    = $request->model;
        $equipment->serial_number = $request->serial_number;
        $equipment->rent_price = $request->rent_price;
        $equipment->monochrome = $request->monochrome;
        $equipment->pages_included_mono = $request->pages_included_mono;
        $equipment->extra_page_cost_mono = $request->extra_page_cost_mono;
        $equipment->counter_mono = $request->counter_mono;
        $equipment->update_counter_mono = $now;
        $equipment->color = $request->color;
        $equipment->pages_included_color = $request->pages_included_color;
        $equipment->extra_page_cost_color = $request->extra_page_cost_color;
        $equipment->counter_color = $request->counter_color;
        $equipment->update_counter_color = $now;
        $equipment->save();
        return response()->json([
            'ok'=>true,
            'equipment' => $equipment,
        ]);
    }//.update
    
    public function active(Request $request){
        $equipment = RentDetail::findOrFail($request->id);
        $equipment->active = 1;
        $equipment->save();
        return response()->json([
            'ok'=>true,
            'equipment' => $equipment,
        ]);
    }//.active
    public function inactive(Request $request){
        $equipment = RentDetail::findOrFail($request->id);
        $equipment->active = 0;
        $equipment->save();
        return response()->json([
            'ok'=>true,
            'equipment' => $equipment,
        ]);
    }//.inactive

    public function destroy(Request $request){
        $equipment = RentDetail::findOrFail($request->id);
        $equipment->delete();
        return response()->json([
            'ok'=>true
        ]);
    }//.destroy
}
