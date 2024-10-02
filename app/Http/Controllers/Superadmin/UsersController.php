<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Role;
use App\Models\Shop;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function get(Request $request)
    {
        if(!$request->ajax()) return redirect('/');

        $shops = Shop::where('active',1)->get();

        $users = User::with('shop')
                ->when($request->buscar!='',function ($query) use ($request){
                    return $query->where($request->criterio,'like','%'.$request->buscar.'%');
                })
                ->when($request->estatus != '', function ($query) use ($request) {
                        // Filtrar por estatus
                        if ($request->estatus === 'active') {
                            return $query->where('active', 1);
                        } elseif ($request->estatus === 'inactive') {
                            return $query->where('active', 0);
                        }
                    })
                ->orderBy('id','desc')
                ->paginate(20);

        return [
            'pagination'=>[
                'total'=> $users->total(),
                'current_page'=> $users->currentPage(),
                'per_page'=> $users->perPage(),
                'last_page'=> $users->lastPage(),
                'from'=> $users->firstItem(),
                'to'=> $users->lastItem(),
            ],
            'users'=>$users,
            'shops'=>$shops,
        ];
    }//.get()

    public function store(Request $request)
    {
        if(!$request->ajax()) return redirect('/');

        $role_user= Role::where('name', 'admin')->first();

        $user = new User();
        $user->active = 1;
        $user->shop_id = $request->shop_id;
        $user->name     = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();
        $user->roles()->attach($role_user);
    }//store()

    public function updateInfo(Request $request)
    {
        if(!$request->ajax()) return redirect('/');
        $user = User::findOrFail($request->user_id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();
    }//./updateInfo()

    public function updateToActive(Request $request){
        if(!$request->ajax()) return redirect('/');
        $user = User::findOrFail($request->id);
        $user->active = 1;
        $user->save();
    }//updateToActive()

    public function updateToInactive(Request $request){
        if(!$request->ajax()) return redirect('/');
        $user = User::findOrFail($request->id);
        $user->active = 0;
        $user->save();
    }//updateToInactive


}
