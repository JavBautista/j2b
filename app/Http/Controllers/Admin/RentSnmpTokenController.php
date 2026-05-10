<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rent;
use App\Models\SnmpAgentToken;
use Illuminate\Support\Facades\Auth;

class RentSnmpTokenController extends Controller
{
    private function authorizeRent(Rent $rent): void
    {
        $shop = Auth::user()->shop;
        $rent->loadMissing('client');
        if (!$shop || !$rent->client || $rent->client->shop_id !== $shop->id) {
            abort(403);
        }
    }

    public function getToken(Rent $rent)
    {
        $this->authorizeRent($rent);

        $token = SnmpAgentToken::where('rent_id', $rent->id)
            ->orderBy('id', 'desc')
            ->first();

        return response()->json([
            'ok' => true,
            'token' => $token,
        ]);
    }

    public function regenerate(Rent $rent)
    {
        $this->authorizeRent($rent);

        SnmpAgentToken::where('rent_id', $rent->id)->delete();

        $token = new SnmpAgentToken();
        $token->shop_id = $rent->client->shop_id;
        $token->client_id = $rent->client_id;
        $token->rent_id = $rent->id;
        $token->token = SnmpAgentToken::generateToken();
        $token->active = true;
        $token->save();

        return response()->json([
            'ok' => true,
            'token' => $token,
            'message' => 'Token generado correctamente.',
        ]);
    }

    public function toggle(Rent $rent)
    {
        $this->authorizeRent($rent);

        $token = SnmpAgentToken::where('rent_id', $rent->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$token) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay token generado para esta renta.',
            ], 404);
        }

        $token->active = !$token->active;
        $token->save();

        return response()->json([
            'ok' => true,
            'token' => $token,
            'message' => $token->active ? 'Token activado.' : 'Token desactivado.',
        ]);
    }
}
