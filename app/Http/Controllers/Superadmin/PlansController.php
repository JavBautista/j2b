<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;

class PlansController extends Controller
{
    public function get(Request $request){
       if(!$request->ajax()) return redirect('/');

        $plans = Plan::orderBy('id', 'desc')
                ->when($request->buscar!='', function ($query) use ($request) {
                        return $query->where($request->criterio, 'like', '%'.$request->buscar.'%');
                    })
                ->paginate(10);

        return [
            'pagination'=>[
                'total'=> $plans->total(),
                'current_page'=> $plans->currentPage(),
                'per_page'=> $plans->perPage(),
                'last_page'=> $plans->lastPage(),
                'from'=> $plans->firstItem(),
                'to'=> $plans->lastItem(),
            ],
            'plans'=>$plans,
        ];
    }

    public function store(Request $request)
    {
        if(!$request->ajax()) return redirect('/');
        $request->validate([
            'name' => 'required|string|max:255'
        ]);
        $plan= new Plan();
        $plan->active = 1;
        $plan->name = $request->name;
        $plan->description = $request->description;
        $plan->price = $request->price;
        $plan->save();
    }//store()

    public function update(Request $request)
    {
        if(!$request->ajax()) return redirect('/');
        $plan= Plan::findOrFail($request->id);
        $plan->name = $request->name;
        $plan->description = $request->description;
        $plan->price = $request->price;
        $plan->save();
    }//update()

    public function active(Request $request)
    {
        if(!$request->ajax()) return redirect('/');
        $plan= Plan::findOrFail($request->id);
        $plan->active= 1;
        $plan->save();
    }//active()

    public function deactive(Request $request)
    {
        if(!$request->ajax()) return redirect('/');
        $plan= Plan::findOrFail($request->id);
        $plan->active= 0;
        $plan->save();
    }//deactive()
}
