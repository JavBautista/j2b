<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;


class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->buscar;
        if($buscar==''){
            $suppliers = Supplier::where('active',1)
                    ->orderBy('id','desc')
                    ->paginate(10);
        }else{
            $suppliers = Supplier::where('active',1)
                    ->where('name', 'like', '%'.$buscar.'%')
                    ->orWhere('company', 'like', '%'.$buscar.'%')
                    ->orderBy('id','desc')
                    ->paginate(10);
        }
        return $suppliers;

    }

    public function store(Request $request)
    {
        $supplier = new Supplier;
        $supplier->active=1;
        $supplier->name=$request->name;
        $supplier->company=$request->company;
        $supplier->email=$request->email;
        $supplier->movil=$request->movil;
        $supplier->address=$request->address;
        $supplier->observations=$request->observations;
        $supplier->bank_number_main=$request->bank_number_main;
        $supplier->save();
        return response()->json([
                'ok'=>true,
                'supplier' => $supplier,
        ]);
    }

    public function update(Request $request)
    {
        $supplier = Supplier::findOrFail($request->id);
        $supplier->name=$request->name;
        $supplier->company=$request->company;
        $supplier->email=$request->email;
        $supplier->movil=$request->movil;
        $supplier->address=$request->address;
        $supplier->observations=$request->observations;
        $supplier->bank_number_main=$request->bank_number_main;
        $supplier->save();
        return response()->json([
                'ok'=>true,
                'supplier' => $supplier,
        ]);
    }

    public function inactive(Request $request)
    {
        $supplier = Supplier::findOrFail($request->id);
        $supplier->active = 0;
        $supplier->save();
        return response()->json([
            'ok'=>true
        ]);
    }

    public function active(Request $request)
    {
        $supplier = Supplier::findOrFail($request->id);
        $supplier->active = 1;
        $supplier->save();
        return response()->json([
            'ok'=>true
        ]);
    }
}
