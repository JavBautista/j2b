<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\SnmpAgentToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SnmpTokenController extends Controller
{
    private function authorizeClient(Client $client): void
    {
        $shop = Auth::user()->shop;
        if (!$shop || $client->shop_id !== $shop->id) {
            abort(403);
        }
    }

    public function getToken(Client $client)
    {
        $this->authorizeClient($client);

        $token = SnmpAgentToken::where('client_id', $client->id)
            ->orderBy('id', 'desc')
            ->first();

        return response()->json([
            'ok' => true,
            'token' => $token,
        ]);
    }

    public function regenerate(Client $client)
    {
        $this->authorizeClient($client);

        SnmpAgentToken::where('client_id', $client->id)->delete();

        $token = new SnmpAgentToken();
        $token->shop_id = $client->shop_id;
        $token->client_id = $client->id;
        $token->token = SnmpAgentToken::generateToken();
        $token->active = true;
        $token->save();

        return response()->json([
            'ok' => true,
            'token' => $token,
            'message' => 'Token generado correctamente.',
        ]);
    }

    public function toggle(Client $client)
    {
        $this->authorizeClient($client);

        $token = SnmpAgentToken::where('client_id', $client->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$token) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay token generado para este cliente.',
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
