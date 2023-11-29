<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Consumables;
use App\Models\Product;

class ConsumablesController extends Controller
{
    public function store(Request $request)
    {
        $consumable= new Consumables();
        $consumable->rent_detail_id    = $request->rent_detail_id;
        $consumable->product_id        = $request->product_id;
        $consumable->description        = $request->description;
        $consumable->counter  = $request->counter;
        $consumable->qty  = $request->qty;
        $consumable->observation  = $request->observation;
        $consumable->save();

        $product = Product::find($request->product_id);
        $new_stock = $product->stock - $request->qty;
        $product->stock = $new_stock;
        $product->save();

        return response()->json([
            'ok'=>true,
            'consumable' => $consumable
        ]);
    }

    public function getHistoryRendtDeatil(Request $request){

        $consumables = Consumables::where('rent_detail_id',$request->rent_detail_id)
                        ->orderBy('created_at','desc')
                        ->get();

        return response()->json([
            'ok'=>true,
            'consumables' => $consumables
        ]);
    }

    public function updateObservation(Request $request){
        $consumable_id   = $request->consumable_id;
        $new_observation = $request->new_observation;

        $consumable = Consumables::findOrFail($consumable_id);
        $consumable->observation = $new_observation;
        $consumable->save();

        return response()->json([
            'ok'=>true,
            'consumable' => $consumable,
        ]);


    }

    public function delete(Request $request)
    {
        try {
            //Eliminamos el consumible
            $consumable = Consumables::findOrFail($request->consumable_id);
            $qty        = $consumable->qty;
            $product_id = $consumable->product_id;
            //Regresamos el stock si es necesario
            if($request->return_stock){
                $product = Product::find($product_id);
                $new_stock = $product->stock + $qty;
                $product->stock = $new_stock;
                $product->save();
            }
            $consumable->delete();
            return response()->json([
                'ok'=>true
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Consumible no encontrado'], 404);
        }
    }
}
