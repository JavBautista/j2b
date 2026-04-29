<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\ClientFiscalDataRequest;
use App\Models\Client;
use App\Models\ClientFiscalData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientFiscalDataController extends Controller
{
    /**
     * GET /clients/{clientId}/fiscal-data
     * Lista los perfiles fiscales activos del cliente. Default primero, luego por id asc.
     */
    public function index(Request $request, int $clientId): JsonResponse
    {
        $client = $this->resolveClient($request, $clientId);

        $perfiles = ClientFiscalData::where('client_id', $client->id)
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return response()->json($perfiles);
    }

    /**
     * POST /clients/{clientId}/fiscal-data
     * Crea un perfil fiscal. Si es el primero del cliente, se marca default automatico.
     */
    public function store(ClientFiscalDataRequest $request, int $clientId): JsonResponse
    {
        $client = $this->resolveClient($request, $clientId);
        $data   = $request->validated();

        $perfil = DB::transaction(function () use ($client, $data) {
            $countActivos  = ClientFiscalData::where('client_id', $client->id)->active()->count();
            $isDefaultFlag = (bool) ($data['is_default'] ?? false);

            if ($countActivos === 0) {
                $isDefaultFlag = true;
            }

            if ($isDefaultFlag) {
                ClientFiscalData::where('client_id', $client->id)->update(['is_default' => false]);
            }

            $data['client_id']  = $client->id;
            $data['is_default'] = $isDefaultFlag;
            $data['active']     = true;

            return ClientFiscalData::create($data);
        });

        return response()->json($perfil, 201);
    }

    /**
     * PUT /fiscal-data/{id}
     * Actualiza un perfil. Si is_default=true, mueve el flag.
     */
    public function update(ClientFiscalDataRequest $request, int $id): JsonResponse
    {
        $perfil = $this->resolvePerfil($request, $id);
        $data   = $request->validated();

        DB::transaction(function () use ($perfil, $data) {
            if (array_key_exists('is_default', $data) && $data['is_default']) {
                ClientFiscalData::where('client_id', $perfil->client_id)
                    ->where('id', '!=', $perfil->id)
                    ->update(['is_default' => false]);
            }

            $perfil->fill($data)->save();
        });

        return response()->json($perfil->fresh());
    }

    /**
     * DELETE /fiscal-data/{id}
     * Soft-delete (active=false) si tiene facturas relacionadas; hard-delete si no.
     * Si era default, mueve el flag al siguiente perfil activo.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $perfil = $this->resolvePerfil($request, $id);

        $tieneFacturas = $perfil->invoices()->exists();

        $resultado = DB::transaction(function () use ($perfil, $tieneFacturas) {
            $eraDefault = $perfil->is_default;

            if ($tieneFacturas) {
                $perfil->active     = false;
                $perfil->is_default = false;
                $perfil->save();
            } else {
                $perfil->delete();
            }

            if ($eraDefault) {
                $siguiente = ClientFiscalData::where('client_id', $perfil->client_id)
                    ->active()
                    ->orderBy('id')
                    ->first();
                if ($siguiente) {
                    $siguiente->is_default = true;
                    $siguiente->save();
                }
            }

            return ['ok' => true, 'soft' => $tieneFacturas];
        });

        return response()->json($resultado);
    }

    /**
     * PATCH /fiscal-data/{id}/set-default
     * Marca este perfil como default; quita el flag al resto del cliente.
     */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        $perfil = $this->resolvePerfil($request, $id);

        DB::transaction(function () use ($perfil) {
            ClientFiscalData::where('client_id', $perfil->client_id)
                ->where('id', '!=', $perfil->id)
                ->update(['is_default' => false]);

            $perfil->is_default = true;
            $perfil->active     = true;
            $perfil->save();
        });

        return response()->json($perfil->fresh());
    }

    private function resolveClient(Request $request, int $clientId): Client
    {
        $shop = $request->user()->shop;

        return Client::where('id', $clientId)
            ->where('shop_id', $shop->id)
            ->firstOrFail();
    }

    private function resolvePerfil(Request $request, int $id): ClientFiscalData
    {
        $shop = $request->user()->shop;

        return ClientFiscalData::where('id', $id)
            ->whereHas('client', fn ($q) => $q->where('shop_id', $shop->id))
            ->firstOrFail();
    }
}
