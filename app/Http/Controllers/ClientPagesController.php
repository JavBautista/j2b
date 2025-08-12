<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Shop;

class ClientPagesController extends Controller
{
    public function index(){
        return view('client.index');
    }

    public function shop(){
         // Obtener el usuario autenticado
        $user = Auth::user();

        // Verificar si el usuario tiene una tienda asociada
        if ($user && $user->shop) {
            // Si el usuario tiene una tienda, obtener los detalles de la tienda
            $shop = $user->shop;

            // Ahora puedes pasar los detalles de la tienda a la vista
            return view('client.shop', compact('shop'));
        } else {
            // Si el usuario no tiene una tienda asociada, redirigir al dashboard
            return redirect()->route('client.index')->with('error', 'No tienes una tienda asignada.');
        }
    }

    public function shopEdit(){
         // Obtener el usuario autenticado
        $user = Auth::user();

        // Verificar si el usuario tiene una tienda asociada
        if ($user && $user->shop) {
            // Si el usuario tiene una tienda, obtener los detalles de la tienda
            $shop = $user->shop;

            // Ahora puedes pasar los detalles de la tienda a la vista
            return view('client.shop_edit', compact('shop'));
        } else {
            // Si el usuario no tiene una tienda asociada, redirigir al dashboard
            return redirect()->route('client.index')->with('error', 'No tienes una tienda asignada.');
        }
    }

    public function download(){
        return view('client.download');
    }

    // OBSOLETO: Las configuraciones ahora se manejan desde admin
    // public function configurations(){
    //     return view('client.configurations.index');
    // }
    
    
    public function contracts(){
        $user = Auth::user();
        $shop = $user->shop;
        
        $templates = \App\Models\ContractTemplate::where('shop_id', $shop->id)
                                                ->where('is_active', true)
                                                ->orderBy('created_at', 'desc')
                                                ->get();
                                                
        $defaultVariables = [
            'cliente_nombre',
            'cliente_email',
            'cliente_telefono',
            'cliente_direccion',
            'fecha_contrato',
            'fecha_vencimiento',
            'monto_total',
            'descripcion_servicios'
        ];

        return view('client.contracts.index', compact('templates', 'defaultVariables'));
    }
}
