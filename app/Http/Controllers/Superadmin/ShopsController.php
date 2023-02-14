<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;
use Illuminate\Support\Facades\Storage;

class ShopsController extends Controller
{
    public function get(Request $request){
       if(!$request->ajax()) return redirect('/');

        $shop = Shop::orderBy('id', 'desc')
                ->when($request->buscar!='', function ($query) use ($request) {
                        return $query->where($request->criterio, 'like', '%'.$request->buscar.'%');
                    })
                ->paginate(10);

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

        $shop= new Shop();
        $shop->plan_id = 1; //De momento los planes es 1
        $shop->active = 1;
        $shop->name = $request->name; // Solo name es obligatorio
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

    public function active(Request $request)
    {
        if(!$request->ajax()) return redirect('/');
        $shop= Shop::findOrFail($request->id);
        $shop->active= 1;
        $shop->save();
    }//active()

    public function deactive(Request $request)
    {
        if(!$request->ajax()) return redirect('/');
        $shop= Shop::findOrFail($request->id);
        $shop->active= 0;
        $shop->save();
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
        $shop->logo = $request->file('logo')->store('shop_logos', 'public');
        $shop->save();

    }//uploadLogo()
}
