<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientAccountMovement;
use App\Services\ClientAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Cuenta corriente del cliente (saldo a favor): estado de cuenta + altas de saldo.
 * Toda escritura del saldo pasa por ClientAccountService.
 */
class ClientAccountController extends Controller
{
    public function __construct(private ClientAccountService $accountService)
    {
    }

    /** GET — Saldo actual + estado de cuenta (movimientos paginados). */
    public function index(Request $request, Client $client)
    {
        $this->authorizeShop($client);

        $movements = $client->accountMovements()
            ->with('creator:id,name')
            ->paginate(15);

        return response()->json([
            'ok'              => true,
            'account_balance' => (float) $client->account_balance,
            'movements'       => $movements,
        ]);
    }

    /** POST — Registra un anticipo/depósito directo a la cuenta (+). */
    public function deposito(Request $request, Client $client)
    {
        $this->authorizeShop($client);

        $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $movement = $this->accountService->registrarMovimiento(
            $client,
            ClientAccountMovement::TYPE_DEPOSITO_ANTICIPO,
            (float) $request->amount,
            [
                'description' => $request->description,
                'created_by'  => Auth::id(),
            ]
        );

        return response()->json([
            'ok'              => true,
            'message'         => 'Anticipo registrado. El saldo a favor del cliente se actualizó.',
            'account_balance' => (float) $client->account_balance,
            'movement'        => $movement,
        ]);
    }

    /** POST — Ajuste manual del admin (±). Motivo obligatorio. */
    public function ajuste(Request $request, Client $client)
    {
        $this->authorizeShop($client);

        $request->validate([
            'amount'         => 'required|numeric',
            'description'    => 'required|string|max:255',
            'allow_negative' => 'nullable|boolean',
        ]);

        $amount = round((float) $request->amount, 2);

        if ($amount == 0.0) {
            return response()->json([
                'ok'      => false,
                'message' => 'El monto del ajuste no puede ser cero.',
            ], 422);
        }

        // Un ajuste que deja la cuenta en negativo (adeudo) requiere confirmación explícita.
        $saldoProyectado = round((float) $client->account_balance + $amount, 2);
        if ($saldoProyectado < 0 && !$request->boolean('allow_negative')) {
            return response()->json([
                'ok'                => false,
                'requires_confirm'  => true,
                'projected_balance' => $saldoProyectado,
                'message'           => 'Este ajuste dejaría la cuenta en negativo (adeudo de $'
                                       . number_format(abs($saldoProyectado), 2) . '). ¿Confirmar?',
            ], 422);
        }

        $movement = $this->accountService->registrarMovimiento(
            $client,
            ClientAccountMovement::TYPE_AJUSTE_MANUAL,
            $amount,
            [
                'description' => $request->description,
                'created_by'  => Auth::id(),
            ]
        );

        return response()->json([
            'ok'              => true,
            'message'         => 'Ajuste registrado.',
            'account_balance' => (float) $client->account_balance,
            'movement'        => $movement,
        ]);
    }

    /** El cliente debe pertenecer a la tienda del admin autenticado. */
    private function authorizeShop(Client $client): void
    {
        if ($client->shop_id !== Auth::user()->shop->id) {
            abort(404);
        }
    }
}
