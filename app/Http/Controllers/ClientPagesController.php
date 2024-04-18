<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;

class ClientPagesController extends Controller
{
    public function index(){
        return view('client.index');
    }

    public function shop(){
         // Obtener el usuario autenticado
        $user = auth()->user();

        // Verificar si el usuario tiene una tienda asociada
        if ($user->shop) {
            // Si el usuario tiene una tienda, obtener los detalles de la tienda
            $shop = $user->shop;

            // Ahora puedes pasar los detalles de la tienda a la vista
            return view('client.shop', compact('shop'));
        } else {
            // Si el usuario no tiene una tienda asociada, puedes manejar este caso como desees
            return redirect()->route('no_shop_assigned');
        }
    }

    public function shopEdit(){
         // Obtener el usuario autenticado
        $user = auth()->user();

        // Verificar si el usuario tiene una tienda asociada
        if ($user->shop) {
            // Si el usuario tiene una tienda, obtener los detalles de la tienda
            $shop = $user->shop;

            // Ahora puedes pasar los detalles de la tienda a la vista
            return view('client.shop_edit', compact('shop'));
        } else {
            // Si el usuario no tiene una tienda asociada, puedes manejar este caso como desees
            return redirect()->route('no_shop_assigned');
        }
    }

    public function download(){
        return view('client.download');
    }

    public function configurations(){
        return view('client.configurations.index');
    }
}
