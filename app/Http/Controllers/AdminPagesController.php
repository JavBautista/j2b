<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Shop;
use App\Models\ClientService;
use App\Models\User;
use App\Http\Controllers\ClientServiceController;

class AdminPagesController extends Controller
{
    public function index(){
        return view('admin.index');
    }

    public function shop(){
         // Obtener el usuario autenticado
        $user = Auth::user();

        // Verificar si el usuario tiene una tienda asociada
        if ($user && $user->shop) {
            // Si el usuario tiene una tienda, obtener los detalles de la tienda
            $shop = $user->shop;

            // Ahora puedes pasar los detalles de la tienda a la vista
            return view('admin.shop', compact('shop'));
        } else {
            // Si el usuario no tiene una tienda asociada, redirigir al dashboard
            return redirect()->route('admin.index')->with('error', 'No tienes una tienda asignada.');
        }
    }

    public function shopEdit(){
         // Obtener el usuario autenticado
        $user = Auth::user();

        // Verificar si el usuario tiene una tienda asociada
        if ($user && $user->shop) {
            // Si el usuario tiene una tienda, obtener los detalles de la tienda
            $shop = $user->shop;

            // Ahora puedes pasar los detalles de la tienda a la vista
            return view('admin.shop_edit', compact('shop'));
        } else {
            // Si el usuario no tiene una tienda asociada, redirigir al dashboard
            return redirect()->route('admin.index')->with('error', 'No tienes una tienda asignada.');
        }
    }

    public function download(){
        return view('admin.download');
    }

    public function configurations(){
        return view('admin.configurations.index');
    }
    
    
    public function contracts(){
        $user = Auth::user();
        $shop = $user->shop;
        
        $templates = \App\Models\ContractTemplate::where('shop_id', $shop->id)
                                                ->where('is_active', true)
                                                ->orderBy('created_at', 'desc')
                                                ->get();
                                                
        $defaultVariables = [
            'cliente_nombre',
            'cliente_email',
            'cliente_telefono',
            'cliente_direccion',
            'fecha_contrato',
            'fecha_vencimiento',
            'monto_total',
            'descripcion_servicios'
        ];

        return view('admin.contracts.index', compact('templates', 'defaultVariables'));
    }

    public function clients(){
        $user = Auth::user();
        $shop = $user->shop;
        $isLimitedUser = $user->isLimitedAdmin();
        return view('admin.clients.index', compact('shop', 'isLimitedUser'));
    }

    /**
     * 🔥 TEMPORAL: Crear servicio de prueba para testing FCM
     * Este método crea un servicio usando el cliente papelitos@me
     * para disparar notificaciones push y probar el sistema FCM
     */
    public function testCreateService(Request $request)
    {
        try {
            // Datos del cliente de prueba: papelitos@me (ID: 48, Shop: 26)
            $testClientUser = User::find(48);
            
            if (!$testClientUser) {
                return back()->with('error', '❌ Usuario de prueba papelitos@me no encontrado');
            }

            // Obtener el cliente asociado al usuario papelitos@me
            $testClient = $testClientUser->client;
            if (!$testClient) {
                return back()->with('error', '❌ Cliente asociado a papelitos@me no encontrado');
            }

            // Crear servicio de prueba con datos mínimos
            $clientService = new ClientService();
            $clientService->client_id = $testClient->id;
            $clientService->shop_id = $testClient->shop_id; // Shop 26
            $clientService->title = '🔥 Servicio de Prueba FCM';
            $clientService->description = 'Servicio creado automáticamente para probar notificaciones push FCM. Usuario: ' . auth()->user()->name;
            $clientService->status = 'NUEVO';
            $clientService->priority = 1; // 1 = alta prioridad
            $clientService->active = 1;
            $clientService->save();

            // Disparar las notificaciones (Pusher + FCM)
            $clientServiceController = new ClientServiceController();
            $clientServiceController->storeNotificationsForShop($clientService);

            return back()->with('success', "✅ Servicio de prueba FCM creado exitosamente! 
                                           <br>📱 Revisa tu app móvil para la notificación push
                                           <br>🔧 Servicio ID: {$clientService->id}
                                           <br>🏪 Tienda destino: {$testClient->shop_id}
                                           <br>👤 Cliente: {$testClient->name}");

        } catch (\Exception $e) {
            \Log::error('Error creando servicio de prueba FCM: ' . $e->getMessage());
            return back()->with('error', '❌ Error creando servicio de prueba: ' . $e->getMessage());
        }
    }

    /**
     * 🔥 TEMPORAL: Crear servicio de prueba para testing FCM con cliente específico
     * Este método crea un servicio usando el cliente seleccionado dinámicamente
     */
    public function testCreateServiceClient(Request $request)
    {
        try {
            $request->validate([
                'client_id' => 'required|integer|exists:clients,id'
            ]);

            $clientId = $request->client_id;
            
            // Obtener el cliente seleccionado
            $testClient = \App\Models\Client::find($clientId);
            if (!$testClient) {
                return response()->json([
                    'success' => false, 
                    'message' => '❌ Cliente no encontrado'
                ]);
            }

            // Crear servicio de prueba con datos del cliente seleccionado
            $clientService = new ClientService();
            $clientService->client_id = $testClient->id;
            $clientService->shop_id = $testClient->shop_id;
            $clientService->title = '🔥 Servicio de Prueba FCM - ' . $testClient->name;
            $clientService->description = 'Servicio creado automáticamente para probar notificaciones push FCM. Cliente: ' . $testClient->name . '. Usuario admin: ' . auth()->user()->name;
            $clientService->status = 'NUEVO';
            $clientService->priority = 1;
            $clientService->active = 1;
            $clientService->save();

            // Disparar las notificaciones (Pusher + FCM)
            $clientServiceController = new ClientServiceController();
            $clientServiceController->storeNotificationsForShop($clientService);

            return response()->json([
                'success' => true,
                'message' => "✅ Servicio de prueba FCM creado exitosamente!<br>📱 Revisa tu app móvil para la notificación push<br>🔧 Servicio ID: {$clientService->id}<br>🏪 Tienda destino: {$testClient->shop_id}<br>👤 Cliente: {$testClient->name}"
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creando servicio de prueba FCM por cliente: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '❌ Error creando servicio de prueba: ' . $e->getMessage()
            ]);
        }
    }
}
