<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Rent;
use App\Models\RentDetail;

class RentController extends Controller
{
    /* RENT  */
    public function index(Request $request){
        $client_id = $request->client_id;
        $rents = Rent::with('rentDetail')
                        ->where('client_id',$client_id)
                        ->orderBy('id','desc')
                        ->paginate(10);
        return $rents;
    }//.index

    public function getByCutoff(Request $request){
        $date_today     = Carbon::now();
        $date_tomorrow  = new Carbon('tomorrow');

        $dd_today = $date_today->day;
        $dd_tomorrow = $date_tomorrow->day;

        $rents_today    = Rent::with(['client'=>function($query){
                                $query->where('active',1);
                        }])
                        ->where('active',1)
                        ->where('cutoff',$dd_today)
                        ->get();
        $rents_tomorrow = Rent::with(['client'=>function($query){
                                $query->where('active',1);
                        }])
                        ->where('active',1)
                        ->where('cutoff',$dd_tomorrow)
                        ->get();
        return [
            'dd_today'      =>$dd_today,
            'dd_tomorrow'   =>$dd_tomorrow,
            'date_today'    =>$date_today,
            'date_tomorrow' =>$date_tomorrow,
            'rents_today'   =>$rents_today,
            'rents_tomorrow'=>$rents_tomorrow
        ];
    }//.index

    public function store(Request $request)
    {
        $rent =new Rent();
        $rent->active = 1;
        $rent->client_id = $request->client_id;
        $rent->cutoff    = $request->cutoff;
        $rent->location_phone   = $request->location_phone;
        $rent->location_email   = $request->location_email;
        $rent->location_address = $request->location_address;
        $rent->location_descripcion = $request->location_descripcion;

        $rent->save();
        return response()->json([
            'ok'=>true,
            'rent' => $rent,
        ]);
    }//.store

    public function update(Request $request)
    {
        $rent =Rent::find($request->id);
        $rent->cutoff               = $request->cutoff;
        $rent->location_phone       = $request->location_phone;
        $rent->location_email       = $request->location_email;
        $rent->location_address     = $request->location_address;
        $rent->location_descripcion = $request->location_descripcion;
        $rent->save();

        return response()->json([
            'ok'=>true,
            'rent' => $rent
        ]);
    }//.update

    public function destroy(Request $request){

        RentDetail::where('rent_id',$request->id)->delete();

        $rent = Rent::findOrFail($request->id);
        $rent->delete();
        return response()->json([
            'ok'=>true
        ]);
    }//.destroy

    /* RENT DETAIL */

    public function storeDetail(Request $request)
    {
        $now = now();

        $rent_detail = new RentDetail();

        $rent_detail->active = 1;
        $rent_detail->rent_id = $request->rent_id;

        $rent_detail->trademark = $request->trademark;
        $rent_detail->model = $request->model;
        $rent_detail->serial_number = $request->serial_number;
        $rent_detail->rent_price = $request->rent_price;

        $rent_detail->monochrome = $request->monochrome;
        $rent_detail->pages_included_mono = $request->pages_included_mono;
        $rent_detail->extra_page_cost_mono = $request->extra_page_cost_mono;
        $rent_detail->counter_mono = $request->counter_mono;
        $rent_detail->update_counter_mono = $now;

        $rent_detail->color = $request->color;
        $rent_detail->pages_included_color = $request->pages_included_color;
        $rent_detail->extra_page_cost_color = $request->extra_page_cost_color;
        $rent_detail->counter_color = $request->counter_color;
        $rent_detail->update_counter_color = $now;

        $rent_detail->save();
        return response()->json([
            'ok'=>true,
            'rent_detail' => $rent_detail,
        ]);
    }//.storeDetail

    public function updateDetail(Request $request){
        $rent_detail = RentDetail::findOrFail($request->id);
        $now = now();
        $rent_detail->trademark = $request->trademark;
        $rent_detail->model    = $request->model;
        $rent_detail->serial_number = $request->serial_number;
        $rent_detail->rent_price = $request->rent_price;
        $rent_detail->monochrome = $request->monochrome;
        $rent_detail->pages_included_mono = $request->pages_included_mono;
        $rent_detail->extra_page_cost_mono = $request->extra_page_cost_mono;
        $rent_detail->counter_mono = $request->counter_mono;
        $rent_detail->update_counter_mono = $now;
        $rent_detail->color = $request->color;
        $rent_detail->pages_included_color = $request->pages_included_color;
        $rent_detail->extra_page_cost_color = $request->extra_page_cost_color;
        $rent_detail->counter_color = $request->counter_color;
        $rent_detail->update_counter_color = $now;
        $rent_detail->save();
        return response()->json([
            'ok'=>true,
            'rent_detail' => $rent_detail,
        ]);
    }//.updateDetail

    public function destroyDetail(Request $request){
        $rent_detail = RentDetail::findOrFail($request->id);
        $rent_detail->delete();
        return response()->json([
            'ok'=>true
        ]);
    }//.destroyDetail

}
