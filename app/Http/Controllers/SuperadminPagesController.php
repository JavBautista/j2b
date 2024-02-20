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

    public function uploadApk(){
        return view('superadmin.upload_apk');
    }

    public function storeApk(Request $request)
    {
        // Validar el archivo APK
        $request->validate([
            'apkFile' => 'required|mimes:apk|max:25600', // Max 10MB
        ]);

        // Subir el archivo APK al almacenamiento
        if ($request->hasFile('apkFile')) {
            $apkFileName = $request->file('apkFile')->getClientOriginalName();
            $request->file('apkFile')->storeAs('public/apk', $apkFileName);

            // Alternativamente, puedes almacenar el archivo en una subcarpeta de storage/app
            // $request->file('apkFile')->storeAs('apk', $apkFileName);
        }

        return redirect()->back()->with('success', 'Archivo APK subido exitosamente.');
    }
}
