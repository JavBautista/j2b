<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('id','desc')->paginate(10);
        return $plans;
    }

    public function store(Request $request)
    {
        $plan = new Plan;
        $plan->active = 1;
        $plan->name = $request->name;
        $plan->description = $request->description;
        $plan->price = $request->price;
        $plan->save();
        return response()->json([
            'ok'=>true,
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, Plan $plan)
    {
        $plan = Plan::findOrFail($request->id);
        $plan->name = $request->name;
        $plan->description = $request->description;
        $plan->price = $request->price;
        $plan->save();
        return response()->json([
            'ok'=>true,
            'plan' => $plan,
        ]);
    }

    public function destroy(Plan $plan)
    {
        //
    }
}
