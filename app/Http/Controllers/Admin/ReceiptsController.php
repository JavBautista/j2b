<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceiptsController extends Controller
{
    /**
     * Mostrar vista de recibos de un cliente
     */
    public function index(Request $request, $clientId)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece al shop del usuario
        $client = Client::where('id', $clientId)
                       ->where('shop_id', $shop->id)
                       ->firstOrFail();

        return view('admin.receipts.index', compact('client'));
    }

    /**
     * API para obtener recibos de un cliente (AJAX)
     */
    public function getReceipts(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;
        
        $clientId = $request->get('client_id');
        $buscar = $request->get('buscar', '');

        // Verificar que el cliente pertenece al shop del usuario
        $client = Client::where('id', $clientId)
                       ->where('shop_id', $shop->id)
                       ->firstOrFail();

        $query = Receipt::with(['partialPayments', 'shop', 'detail', 'client'])
                        ->where('client_id', $clientId)
                        ->where('shop_id', $shop->id);

        // Filtro de búsqueda por folio o número de recibo
        if (!empty($buscar)) {
            $query->where(function($q) use ($buscar) {
                $q->where('folio', 'like', '%' . $buscar . '%')
                  ->orWhere('id', 'like', '%' . $buscar . '%');
            });
        }

        $receipts = $query->orderBy('id', 'desc')->paginate(10);

        $response = $receipts->toArray();
        $response['pagination'] = [
            'total' => $receipts->total(),
            'current_page' => $receipts->currentPage(),
            'per_page' => $receipts->perPage(),
            'last_page' => $receipts->lastPage(),
            'from' => $receipts->firstItem(),
            'to' => $receipts->lastItem()
        ];

        return response()->json([
            'success' => true,
            'receipts' => $response['data'],
            'pagination' => $response['pagination'],
            'client' => $client
        ]);
    }
}
