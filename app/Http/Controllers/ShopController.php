<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;
use Illuminate\Support\Facades\Session;

class ShopController extends Controller
{
    public function getShop(Request $request){
       $user = $request->user();
       $shop = $user->shop;
       $shop= Shop::findOrFail($shop->id);
       return $shop;
    }



    public function update(Request $request)
    {
        $wa = (isset($request['whatsapp']))?$request['whatsapp']:0;

        $shop= Shop::findOrFail($request->id);
        $shop->description = $request->description;
        $shop->zip_code = $request->zip_code;
        $shop->address = $request->address;
        $shop->number_out = $request->number_out;
        $shop->number_int = $request->number_int;
        $shop->district = $request->district;
        $shop->city = $request->city;
        $shop->state = $request->state;
        $shop->whatsapp = $wa;
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

        return response()->json([
            'ok'=>true,
            'shop' => $shop,
        ]);
    }

    public function updateWeb(Request $request)
    {

        $wa = (isset($request['whatsapp']))?$request['whatsapp']:0;

        $shop= Shop::findOrFail($request->id);

        $shop->description = $request->description;
        $shop->zip_code = $request->zip_code;
        $shop->address = $request->address;
        $shop->number_out = $request->number_out;
        $shop->number_int = $request->number_int;
        $shop->district = $request->district;
        $shop->city = $request->city;
        $shop->state = $request->state;
        $shop->whatsapp = $wa;
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

        return redirect()->route('client.shop')->with('success', 'Actualización exitosa');

        //return view('client.shop', ['shop' => $shop])->with('success', 'Actualización exitosa');

    }

}
