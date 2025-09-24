<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ContractTemplate;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        $buscar = $request->get('buscar', '');
        $criterio = $request->get('criterio', 'name');
        $estatus = $request->get('estatus', 'active');

        $query = Client::where('shop_id', $shop->id);

        // Filtro por estatus
        if ($estatus === 'active') {
            $query->where('active', 1);
        } elseif ($estatus === 'inactive') {
            $query->where('active', 0);
        }

        // Filtro de búsqueda
        if (!empty($buscar)) {
            if ($criterio === 'name') {
                $query->where('name', 'like', '%' . $buscar . '%');
            } elseif ($criterio === 'email') {
                $query->where('email', 'like', '%' . $buscar . '%');
            }
        }

        $clients = $query->orderBy('id', 'desc')->paginate(10);

        $response = $clients->toArray();
        $response['pagination'] = [
            'total' => $clients->total(),
            'current_page' => $clients->currentPage(),
            'per_page' => $clients->perPage(),
            'last_page' => $clients->lastPage(),
            'from' => $clients->firstItem(),
            'to' => $clients->lastItem()
        ];

        return response()->json([
            'clients' => $clients,
            'pagination' => $response['pagination']
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'movil' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'level' => 'nullable|integer'
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        $client = new Client();
        $client->shop_id = $shop->id;
        $client->active = 1;
        $client->name = $request->name;
        $client->company = $request->company;
        $client->email = $request->email;
        $client->movil = $request->movil;
        $client->address = $request->address;
        $client->level = $request->level ?? 1;
        $client->save();

        return response()->json([
            'ok' => true,
            'client' => $client,
            'message' => 'Cliente creado exitosamente'
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'movil' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'level' => 'nullable|integer'
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        $client = Client::where('id', $request->client_id)
                       ->where('shop_id', $shop->id)
                       ->firstOrFail();

        $client->name = $request->name;
        $client->company = $request->company;
        $client->email = $request->email;
        $client->movil = $request->movil;
        $client->address = $request->address;
        $client->level = $request->level ?? 1;
        $client->save();

        return response()->json([
            'ok' => true,
            'client' => $client,
            'message' => 'Cliente actualizado exitosamente'
        ]);
    }

    public function inactive(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:clients,id'
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        $client = Client::where('id', $request->id)
                       ->where('shop_id', $shop->id)
                       ->firstOrFail();

        $client->active = 0;
        $client->save();

        return response()->json([
            'ok' => true,
            'message' => 'Cliente desactivado exitosamente'
        ]);
    }

    public function active(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:clients,id'
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        $client = Client::where('id', $request->id)
                       ->where('shop_id', $shop->id)
                       ->firstOrFail();

        $client->active = 1;
        $client->save();

        return response()->json([
            'ok' => true,
            'message' => 'Cliente activado exitosamente'
        ]);
    }

    public function assignContractPage(Client $client)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            abort(404);
        }

        $templates = ContractTemplate::where('shop_id', $shop->id)
                                   ->where('is_active', true)
                                   ->orderBy('name')
                                   ->get();

        return view('admin.clients.assign-contract', [
            'client' => $client,
            'templates' => $templates
        ]);
    }

    public function createContract(Request $request, Client $client)
    {
        $request->validate([
            'contract_template_id' => 'required|exists:contract_templates,id',
            'contract_content' => 'required|string',
            'start_date' => 'required|date',
            'expiration_date' => 'required|date|after:start_date'
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            abort(404);
        }

        // Verificar que la plantilla pertenece a la tienda
        $template = ContractTemplate::where('id', $request->contract_template_id)
                                   ->where('shop_id', $shop->id)
                                   ->firstOrFail();

        // Crear contrato con contenido personalizado desde el editor Quill
        $contract = Contract::create([
            'client_id' => $client->id,
            'contract_template_id' => $template->id,
            'contract_data' => [], // Array vacío para cumplir con la BD
            'contract_content' => $request->contract_content, // HTML personalizado del editor
            'start_date' => $request->start_date,
            'expiration_date' => $request->expiration_date,
            'status' => 'draft'
        ]);

        // Log: Contrato creado
        \App\Models\ContractLog::log(
            $contract->id,
            'created',
            "Contrato creado con plantilla '{$template->name}' para cliente '{$client->name}'",
            null,
            [
                'template' => $template->name,
                'client' => $client->name,
                'start_date' => $request->start_date,
                'expiration_date' => $request->expiration_date
            ]
        );

        return redirect()->route('admin.clients.contracts', $client)
                        ->with('success', 'Contrato personalizado creado exitosamente para ' . $client->name);
    }

    public function editContract(Client $client, Contract $contract)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            abort(404);
        }

        // Verificar que el contrato pertenece al cliente
        if ($contract->client_id !== $client->id) {
            abort(404);
        }

        $templates = ContractTemplate::where('shop_id', $shop->id)
                                   ->where('is_active', true)
                                   ->orderBy('name')
                                   ->get();

        return view('admin.clients.edit-contract', [
            'client' => $client,
            'contract' => $contract,
            'templates' => $templates
        ]);
    }

    public function updateContract(Request $request, Client $client, Contract $contract)
    {
        $request->validate([
            'contract_content' => 'required|string',
            'start_date' => 'required|date',
            'expiration_date' => 'required|date|after:start_date'
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            abort(404);
        }

        // Verificar que el contrato pertenece al cliente
        if ($contract->client_id !== $client->id) {
            abort(404);
        }

        // Capturar valores anteriores para el log
        $oldValues = [
            'start_date' => $contract->start_date?->format('Y-m-d'),
            'expiration_date' => $contract->expiration_date?->format('Y-m-d'),
            'status' => $contract->status
        ];

        // Actualizar contrato
        $contract->update([
            'contract_content' => $request->contract_content,
            'start_date' => $request->start_date,
            'expiration_date' => $request->expiration_date,
            'status' => 'draft' // Volver a borrador después de editar
        ]);

        // Log: Contrato actualizado
        \App\Models\ContractLog::log(
            $contract->id,
            'updated',
            'Contrato editado - contenido y fechas actualizadas',
            $oldValues,
            [
                'start_date' => $request->start_date,
                'expiration_date' => $request->expiration_date,
                'status' => 'draft'
            ]
        );

        return redirect()->route('admin.clients.contracts', $client)
                        ->with('success', 'Contrato actualizado exitosamente');
    }

    public function getContractPreview(Request $request, Client $client)
    {
        $request->validate([
            'template_id' => 'required|exists:contract_templates,id',
            'contract_data' => 'sometimes|array'
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            abort(404);
        }

        // Verificar que la plantilla pertenece a la tienda
        $template = ContractTemplate::where('id', $request->template_id)
                                   ->where('shop_id', $shop->id)
                                   ->firstOrFail();

        // Preparar datos automáticos del cliente
        $clientData = [
            'cliente_nombre' => $client->name,
            'cliente_empresa' => $client->company ?: 'N/A',
            'cliente_email' => $client->email,
            'cliente_telefono' => $client->phone ?? $client->movil ?: 'N/A',
            'cliente_movil' => $client->movil ?: 'N/A',
            'cliente_direccion' => $client->address ?: 'N/A',
            'cliente_ciudad' => $client->city ?: 'N/A',
            'cliente_estado' => $client->state ?: 'N/A',
            'cliente_cp' => $client->zip_code ?: 'N/A',
            'fecha_contrato' => now()->format('d/m/Y')
        ];

        // Combinar con datos manuales si existen
        $contractData = $request->contract_data ?: [];
        
        // Para el editor, devolver la plantilla original con variables {{}}
        // NO reemplazar las variables, solo devolver el contenido HTML original
        $previewHtml = $template->html_content;
        
        // Agregar estilos CSS si existen
        if ($template->css_styles) {
            $previewHtml = '<style>' . $template->css_styles . '</style>' . $previewHtml;
        }

        return response()->json([
            'ok' => true,
            'preview_html' => $previewHtml,
            'template_name' => $template->name
        ]);
    }

    public function clientContracts(Client $client)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            abort(404);
        }

        $contracts = Contract::where('client_id', $client->id)
                           ->with(['template'])
                           ->orderBy('created_at', 'desc')
                           ->get();

        return view('admin.clients.contracts', [
            'client' => $client,
            'contracts' => $contracts
        ]);
    }

    public function viewContract(Contract $contract)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el contrato pertenece a la tienda a través del cliente
        if ($contract->client->shop_id !== $shop->id) {
            abort(404);
        }

        // Log: Contrato visualizado (comentado - poco relevante por ahora)
        // \App\Models\ContractLog::log(
        //     $contract->id,
        //     'viewed',
        //     'Contrato visualizado en detalle',
        //     null,
        //     [
        //         'template' => $contract->template->name,
        //         'client' => $contract->client->name,
        //         'status' => $contract->status
        //     ]
        // );

        return view('admin.contracts.view', [
            'contract' => $contract
        ]);
    }

    public function deleteContract(Contract $contract)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el contrato pertenece a la tienda a través del cliente
        if ($contract->client->shop_id !== $shop->id) {
            abort(404);
        }

        $clientId = $contract->client_id;
        $contractData = [
            'template' => $contract->template->name,
            'client' => $contract->client->name,
            'status' => $contract->status,
            'created_at' => $contract->created_at->format('Y-m-d H:i:s')
        ];

        // Log: Contrato eliminado (antes de eliminarlo)
        \App\Models\ContractLog::log(
            $contract->id,
            'deleted',
            "Contrato eliminado - Template: {$contract->template->name}",
            $contractData,
            null
        );

        $contract->delete();

        return redirect()->route('admin.clients.contracts', $clientId)
                        ->with('success', 'Contrato eliminado correctamente');
    }

    public function cancelContract(Request $request, Client $client, Contract $contract)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:1000'
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            abort(404);
        }

        // Verificar que el contrato pertenece al cliente
        if ($contract->client_id !== $client->id) {
            abort(404);
        }

        // Verificar que el contrato se puede cancelar
        if (!$contract->canBeCancelled()) {
            return redirect()->back()->with('error', 'Este contrato no se puede cancelar. Estado actual: ' . $contract->status);
        }

        try {
            $oldStatus = $contract->status;

            $contract->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_by' => $user->id
            ]);

            // Log: Contrato cancelado
            \App\Models\ContractLog::log(
                $contract->id,
                'cancelled',
                "Contrato cancelado. Motivo: {$request->cancellation_reason}",
                ['status' => $oldStatus],
                [
                    'status' => 'cancelled',
                    'cancelled_at' => now()->format('Y-m-d H:i:s'),
                    'cancellation_reason' => $request->cancellation_reason,
                    'cancelled_by' => $user->name
                ]
            );

            return redirect()->back()->with('success', 'Contrato cancelado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al cancelar el contrato: ' . $e->getMessage());
        }
    }

    public function contractLogs(Client $client, Contract $contract)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            abort(404);
        }

        // Verificar que el contrato pertenece al cliente
        if ($contract->client_id !== $client->id) {
            abort(404);
        }

        $logs = $contract->logs()->with('user')->paginate(20);

        return view('admin.clients.contract-logs', [
            'client' => $client,
            'contract' => $contract,
            'logs' => $logs
        ]);
    }
}