<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RequestsJ2b;

class RequestsJ2bController extends Controller
{
    public function getRegisters(Request $request){
        if(!$request->ajax()) return redirect('/');

        $registers = RequestsJ2b::orderBy('id', 'desc')
                ->when($request->buscar!='', function ($query) use ($request) {
                        return $query->where($request->criterio, 'like', '%'.$request->buscar.'%');
                    })
                ->paginate(20);

        return [
            'pagination'=>[
                'total'=> $registers->total(),
                'current_page'=> $registers->currentPage(),
                'per_page'=> $registers->perPage(),
                'last_page'=> $registers->lastPage(),
                'from'=> $registers->firstItem(),
                'to'=> $registers->lastItem(),
            ],
            'registers'=>$registers,
        ];
    }//getRegisters()

    public function destroy(Request $request){
        if(!$request->ajax()) return redirect('/');
        $register = RequestsJ2b::findOrFail($request->id);
        $register->delete();
        return response()->json([
            'ok'=>true
        ]);
    }//.destroy
}
