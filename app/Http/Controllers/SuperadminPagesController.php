<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SuperadminPagesController extends Controller
{
    public function index(){
        return view('superadmin.index');
    }

    public function shops(){
        return view('superadmin.shops');
    }

    public function plans(){
        return view('superadmin.plans');
    }

    public function users(){
        return view('superadmin.users');
    }
}
