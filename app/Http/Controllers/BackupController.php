<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ClientsExport;
use App\Exports\ProductsExport;

class BackupController extends Controller
{
    /**
     * Exportar todos los clientes de la tienda a Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportClients(Request $request)
    {
        $user = $request->user();
        $shop_id = $user->shop_id;

        $fileName = 'BD_Clientes_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new ClientsExport($shop_id), $fileName);
    }

    /**
     * Exportar todos los productos de la tienda a Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportProducts(Request $request)
    {
        $user = $request->user();
        $shop_id = $user->shop_id;

        $fileName = 'BD_Productos_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new ProductsExport($shop_id), $fileName);
    }
}
