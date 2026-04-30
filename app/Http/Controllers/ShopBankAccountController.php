<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShopBankAccountRequest;
use App\Models\ShopBankAccount;
use App\Support\SatCatalogos\Bancos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller compartido entre rutas web (admin sesión) y rutas API (Ionic JWT).
 * Resuelve la tienda desde $request->user()->shop, que funciona en ambos auth.
 *
 * Plan: xdev/facturacion/HUB CFDI/PLAN_HARDENING_PAGOS_20.md §3.5
 */
class ShopBankAccountController extends Controller
{
    /**
     * Renderiza la vista Blade que monta el componente Vue de gestión.
     * Solo aplica a rutas web (sesión Laravel). Las rutas API/Ionic no la usan.
     */
    public function page()
    {
        return view('admin.configuracion.cuentas-bancarias');
    }

    /**
     * Listar cuentas bancarias activas de la tienda autenticada.
     */
    public function index(Request $request): JsonResponse
    {
        $shop = $request->user()->shop;

        $accounts = ShopBankAccount::where('shop_id', $shop->id)
            ->orderByDesc('is_default')
            ->orderBy('alias')
            ->get();

        return response()->json([
            'ok' => true,
            'accounts' => $accounts,
            'catalogo_bancos' => Bancos::all(),
        ]);
    }

    /**
     * Crear cuenta bancaria. Si is_default=true, desmarca las demás.
     */
    public function store(ShopBankAccountRequest $request): JsonResponse
    {
        $shop = $request->user()->shop;
        $data = $request->validated();
        $data['shop_id'] = $shop->id;

        $account = DB::transaction(function () use ($shop, $data) {
            // Si se marca como default, desmarcar las anteriores
            if (!empty($data['is_default'])) {
                ShopBankAccount::where('shop_id', $shop->id)
                    ->update(['is_default' => false]);
            }

            // Si es la primera cuenta, forzar default
            $isFirst = ShopBankAccount::where('shop_id', $shop->id)->count() === 0;
            if ($isFirst) {
                $data['is_default'] = true;
            }

            return ShopBankAccount::create($data);
        });

        return response()->json([
            'ok' => true,
            'account' => $account,
            'message' => 'Cuenta bancaria registrada.',
        ], 201);
    }

    /**
     * Actualizar cuenta bancaria. Maneja cambio de default atómicamente.
     */
    public function update(ShopBankAccountRequest $request, int $id): JsonResponse
    {
        $shop = $request->user()->shop;
        $account = ShopBankAccount::where('shop_id', $shop->id)->findOrFail($id);
        $data = $request->validated();

        DB::transaction(function () use ($shop, $account, $data) {
            if (!empty($data['is_default'])) {
                ShopBankAccount::where('shop_id', $shop->id)
                    ->where('id', '!=', $account->id)
                    ->update(['is_default' => false]);
            }
            $account->update($data);
        });

        return response()->json([
            'ok' => true,
            'account' => $account->fresh(),
            'message' => 'Cuenta bancaria actualizada.',
        ]);
    }

    /**
     * Soft-delete. Si es la default, intenta promover otra activa como nueva default.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $shop = $request->user()->shop;
        $account = ShopBankAccount::where('shop_id', $shop->id)->findOrFail($id);

        DB::transaction(function () use ($shop, $account) {
            $eraDefault = $account->is_default;
            $account->delete();

            if ($eraDefault) {
                $nuevaDefault = ShopBankAccount::where('shop_id', $shop->id)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->first();
                if ($nuevaDefault) {
                    $nuevaDefault->update(['is_default' => true]);
                }
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Cuenta bancaria eliminada.',
        ]);
    }

    /**
     * Marcar una cuenta como predeterminada. Desmarca las demás.
     */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        $shop = $request->user()->shop;
        $account = ShopBankAccount::where('shop_id', $shop->id)->findOrFail($id);

        $account->setAsDefault();

        return response()->json([
            'ok' => true,
            'account' => $account->fresh(),
            'message' => 'Cuenta marcada como predeterminada.',
        ]);
    }
}
