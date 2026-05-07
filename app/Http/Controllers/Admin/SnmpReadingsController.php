<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\EquipmentCounterReading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SnmpReadingsController extends Controller
{
    public function index()
    {
        $shop = Auth::user()->shop;
        return view('admin.snmp-readings.index', [
            'shop' => $shop,
        ]);
    }

    public function getReadings(Request $request)
    {
        $shop = Auth::user()->shop;
        $perPage = (int) $request->input('per_page', 20);
        $clientId = $request->input('client_id');
        $matched = $request->input('matched');

        $query = EquipmentCounterReading::with([
            'client:id,name',
            'rentDetail:id,trademark,model,serial_number',
        ])
            ->where('shop_id', $shop->id)
            ->orderBy('read_at', 'desc');

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        if ($matched === 'true' || $matched === '1') {
            $query->where('matched', true);
        } elseif ($matched === 'false' || $matched === '0') {
            $query->where('matched', false);
        }

        return response()->json($query->paginate($perPage));
    }

    public function getClients()
    {
        $shop = Auth::user()->shop;

        $clients = Client::where('shop_id', $shop->id)
            ->whereIn('id', function ($q) use ($shop) {
                $q->select('client_id')
                    ->from('snmp_agent_tokens')
                    ->where('shop_id', $shop->id);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'ok' => true,
            'clients' => $clients,
        ]);
    }
}
