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
            } elseif ($criterio === 'movil') {
                $query->where('movil', 'like', '%' . $buscar . '%');
            } elseif ($criterio === 'company') {
                $query->where('company', 'like', '%' . $buscar . '%');
            }
        }

        $clients = $query->withCount('rents')->orderBy('id', 'desc')->paginate(12);

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
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'level' => 'nullable|integer',
            'observations' => 'nullable|string'
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
        $client->phone = $request->phone;
        $client->address = $request->address;
        $client->city = $request->city;
        $client->state = $request->state;
        $client->level = $request->level ?? 1;
        $client->observations = $request->observations;
        $client->save();

        return response()->json([
            'ok' => true,
            'client' => $client,
            'message' => 'Cliente creado exitosamente'
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // Validar permisos: usuario limitado no puede editar
        if ($user->isLimitedAdmin()) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permisos para editar clientes.'
            ], 403);
        }

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'movil' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'level' => 'nullable|integer',
            'observations' => 'nullable|string'
        ]);

        $shop = $user->shop;

        $client = Client::where('id', $request->client_id)
                       ->where('shop_id', $shop->id)
                       ->firstOrFail();

        $client->name = $request->name;
        $client->company = $request->company;
        $client->email = $request->email;
        $client->movil = $request->movil;
        $client->phone = $request->phone;
        $client->address = $request->address;
        $client->city = $request->city;
        $client->state = $request->state;
        $client->level = $request->level ?? 1;
        $client->observations = $request->observations;
        $client->save();

        return response()->json([
            'ok' => true,
            'client' => $client,
            'message' => 'Cliente actualizado exitosamente'
        ]);
    }

    public function inactive(Request $request)
    {
        $user = Auth::user();

        // Validar permisos: usuario limitado no puede desactivar
        if ($user->isLimitedAdmin()) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permisos para desactivar clientes.'
            ], 403);
        }

        $request->validate([
            'id' => 'required|exists:clients,id'
        ]);

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
        $user = Auth::user();

        // Validar permisos: usuario limitado no puede activar
        if ($user->isLimitedAdmin()) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permisos para activar clientes.'
            ], 403);
        }

        $request->validate([
            'id' => 'required|exists:clients,id'
        ]);

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

    // =====================================================
    // USUARIO APP CLIENTE
    // =====================================================

    /**
     * Verificar si un email ya existe para usuario APP
     */
    public function verifyUserEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $exists = \App\Models\User::where('email', $request->email)->exists();

        return response()->json([
            'ok' => true,
            'exists' => $exists
        ]);
    }

    /**
     * Obtener datos del usuario APP de un cliente
     */
    public function getClientUserApp(Client $client)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        if (!$client->user_id) {
            return response()->json([
                'ok' => true,
                'has_user' => false,
                'user' => null
            ]);
        }

        $clientUser = \App\Models\User::find($client->user_id);

        return response()->json([
            'ok' => true,
            'has_user' => true,
            'user' => $clientUser ? [
                'id' => $clientUser->id,
                'name' => $clientUser->name,
                'email' => $clientUser->email
            ] : null
        ]);
    }

    /**
     * Crear usuario APP para un cliente
     */
    public function storeClientUserApp(Request $request, Client $client)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        // Verificar que el cliente no tenga ya un usuario
        if ($client->user_id) {
            return response()->json([
                'ok' => false,
                'message' => 'Este cliente ya tiene un usuario APP asignado'
            ], 400);
        }

        $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string|min:8'
        ]);

        // Generar email basado en username y shop slug
        $email = strtolower($request->username) . '@' . $shop->slug . '.app';

        // Verificar que el email no exista
        if (\App\Models\User::where('email', $email)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'El usuario "' . $request->username . '" ya existe. Elige otro nombre.'
            ], 400);
        }

        // Crear el usuario
        $newUser = \App\Models\User::create([
            'name' => $client->name,
            'email' => $email,
            'password' => bcrypt($request->password),
            'shop_id' => $shop->id,
            'rol' => 'client'
        ]);

        // Asignar usuario al cliente
        $client->user_id = $newUser->id;
        $client->save();

        return response()->json([
            'ok' => true,
            'message' => 'Usuario APP creado exitosamente',
            'user' => [
                'id' => $newUser->id,
                'email' => $newUser->email
            ],
            'client' => $client
        ]);
    }

    /**
     * Actualizar usuario APP de un cliente (email o password)
     */
    public function updateClientUserApp(Request $request, Client $client)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        // Verificar que el cliente tenga un usuario
        if (!$client->user_id) {
            return response()->json([
                'ok' => false,
                'message' => 'Este cliente no tiene un usuario APP'
            ], 400);
        }

        $clientUser = \App\Models\User::find($client->user_id);
        if (!$clientUser) {
            return response()->json([
                'ok' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $request->validate([
            'email' => 'nullable|email',
            'password' => 'nullable|string|min:8'
        ]);

        $updated = [];

        // Actualizar email si se proporcionó
        if ($request->filled('email') && $request->email !== $clientUser->email) {
            // Verificar que el nuevo email no exista
            if (\App\Models\User::where('email', $request->email)->where('id', '!=', $clientUser->id)->exists()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El email ya está en uso por otro usuario'
                ], 400);
            }
            $clientUser->email = $request->email;
            $updated[] = 'email';
        }

        // Actualizar password si se proporcionó
        if ($request->filled('password')) {
            $clientUser->password = bcrypt($request->password);
            $updated[] = 'contraseña';
        }

        if (empty($updated)) {
            return response()->json([
                'ok' => false,
                'message' => 'No se proporcionaron datos para actualizar'
            ], 400);
        }

        $clientUser->save();

        return response()->json([
            'ok' => true,
            'message' => 'Usuario actualizado: ' . implode(' y ', $updated),
            'user' => [
                'id' => $clientUser->id,
                'email' => $clientUser->email
            ]
        ]);
    }

    // =====================================================
    // IMAGEN DE UBICACIÓN
    // =====================================================

    /**
     * Subir imagen de ubicación/referencia del cliente
     */
    public function uploadLocationImage(Request $request, Client $client)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120' // 5MB max
        ]);

        // Eliminar imagen anterior si existe
        if ($client->location_image) {
            $oldPath = storage_path('app/public/' . $client->location_image);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        // Guardar nueva imagen
        $path = $request->file('image')->store('clients/locations', 'public');

        $client->location_image = $path;
        $client->save();

        return response()->json([
            'ok' => true,
            'message' => 'Imagen subida exitosamente',
            'client' => $client
        ]);
    }

    /**
     * Eliminar imagen de ubicación/referencia del cliente
     */
    public function deleteLocationImage(Client $client)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Validar permisos: usuario limitado no puede eliminar
        if ($user->isLimitedAdmin()) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permisos para eliminar imágenes.'
            ], 403);
        }

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        if ($client->location_image) {
            $path = storage_path('app/public/' . $client->location_image);
            if (file_exists($path)) {
                unlink($path);
            }
            $client->location_image = null;
            $client->save();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Imagen eliminada exitosamente',
            'client' => $client
        ]);
    }

    // =====================================================
    // GEOLOCALIZACIÓN GPS
    // =====================================================

    /**
     * Actualizar coordenadas GPS del cliente
     */
    public function updateLocation(Request $request, Client $client)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        $client->location_latitude = $request->latitude;
        $client->location_longitude = $request->longitude;
        $client->save();

        return response()->json([
            'ok' => true,
            'message' => 'Ubicación guardada exitosamente',
            'client' => $client
        ]);
    }

    /**
     * Eliminar coordenadas GPS del cliente
     */
    public function removeLocation(Client $client)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Validar permisos: usuario limitado no puede eliminar ubicación
        if ($user->isLimitedAdmin()) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permisos para eliminar ubicaciones.'
            ], 403);
        }

        // Verificar que el cliente pertenece a la tienda
        if ($client->shop_id !== $shop->id) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $client->location_latitude = null;
        $client->location_longitude = null;
        $client->save();

        return response()->json([
            'ok' => true,
            'message' => 'Ubicación eliminada exitosamente',
            'client' => $client
        ]);
    }

    // =====================================================
    // RENTAS DEL CLIENTE
    // =====================================================

}