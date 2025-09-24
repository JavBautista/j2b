<?php

use App\Http\Controllers\uploadLocationImageClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ReportsController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseOrderDetailController;
use App\Http\Controllers\PurchaseOrderPartialPaymentsController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\EmailConfirmationController;
use App\Http\Controllers\ServicesClientController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\NotificationController;

use App\Http\Controllers\Clients\PurchaseController as ClientPurchaseController;
use App\Http\Controllers\Clients\LocationController as ClientLocationController;
use App\Http\Controllers\ClientServiceController as ClientServiceController;
use App\Http\Controllers\ReceiptDetailController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/api-pre-registro', [EmailConfirmationController::class, 'store']);


Route::get('notifications/test',[NotificationController::class,'test']);

/*RENTAS*/
Route::get('rents/{client_id}','App\Http\Controllers\RentController@index');
Route::get('rent/get/{id}','App\Http\Controllers\RentController@getRentByID');

Route::post('rent/store','App\Http\Controllers\RentController@store');
Route::post('rent/update','App\Http\Controllers\RentController@update');
Route::post('rent/inactive','App\Http\Controllers\RentController@inactive');
Route::post('rent/delete','App\Http\Controllers\RentController@destroy');
Route::post('rent/store-detail','App\Http\Controllers\RentController@storeDetail');
Route::post('rent/update-detail','App\Http\Controllers\RentController@updateDetail');
Route::post('rent/delete-detail','App\Http\Controllers\RentController@destroyDetail');
Route::post('rent/liberar-detail','App\Http\Controllers\RentController@liberarDetail');

Route::post('rent/equipment/update-rent-id','App\Http\Controllers\RentController@updateEquipmentRentID');

/*PLANS*/
Route::resource('plan','App\Http\Controllers\PlanController');
Route::get('/plan/get/all','App\Http\Controllers\PlanController@all');




/*CONSUMABLES*/
Route::get('consumables/get-history-rent-deatil/{rent_detail_id}','App\Http\Controllers\ConsumablesController@getHistoryRendtDeatil');
Route::post('consumables/store','App\Http\Controllers\ConsumablesController@store');
Route::post('consumables/update-observation','App\Http\Controllers\ConsumablesController@updateObservation');

Route::post('consumables/delete','App\Http\Controllers\ConsumablesController@delete');



/*
*-------------------------------------------------------------------
*ROUTES FOR CHATBOX
*---------------------------------------------------------------------
*/

/*----------------------------------------------------------------------*/

Route::post('chatbot/login', '\App\Http\Controllers\Chatbot\ChatbotAuthController@login');

Route::group([
    'prefix' => 'chatbot',
    'middleware' => ['auth:chatbot'] // Protege estas rutas con el guard chatbot
], function () {
    Route::get('get-products','App\Http\Controllers\Chatbot\ChatbotController@getProducts');
    Route::get('get-clients','App\Http\Controllers\Chatbot\ChatbotController@getClients');
    Route::post('clients-create','App\Http\Controllers\Chatbot\ChatbotController@clientStore');
    Route::post('receipt-create','App\Http\Controllers\Chatbot\ChatbotController@receiptStore');
});



/*------------------------------------------------------------------
/* BEGIN RUTAS PROTEGIDAS
/*------------------------------------------------------------------*/
Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('login', '\App\Http\Controllers\AuthController@login');
    Route::post('signup', '\App\Http\Controllers\AuthController@signUp');

    Route::group([
      'middleware' => 'auth:api'
    ], function() {
        /*USER*/



        Route::get('logout', '\App\Http\Controllers\AuthController@logout');
        Route::get('user', '\App\Http\Controllers\AuthController@user');
        Route::post('user', '\App\Http\Controllers\AuthController@update');

        Route::post('user/update-terminos', '\App\Http\Controllers\AuthController@updateTerminos');

        /*CLIENTES*/
        Route::resource('client','App\Http\Controllers\ClientController')->except(['show']);
        Route::post('client/edit/update','App\Http\Controllers\ClientController@update');
        Route::post('client/delete','App\Http\Controllers\ClientController@inactive');
        Route::post('client/upload-location-image','App\Http\Controllers\ClientController@uploadLocationImageClient');
        Route::post('client/delete-location-image','App\Http\Controllers\ClientController@deleteLocationImage');
        Route::post('client/update-location','App\Http\Controllers\ClientController@updateLocation');
        Route::post('client/remove-location','App\Http\Controllers\ClientController@removeLocation');

        Route::get('client/verify-user-email','App\Http\Controllers\ClientController@verifyUserEmail');
        Route::post('client/store-user-app','App\Http\Controllers\ClientController@storeUserApp');

        /*DIRECCIONES DE CLIENTES*/
        Route::get('client-address','App\Http\Controllers\ClientAddressController@index');
        Route::post('client-address','App\Http\Controllers\ClientAddressController@store');
        Route::post('client-address/update','App\Http\Controllers\ClientAddressController@update');
        Route::post('client-address/delete','App\Http\Controllers\ClientAddressController@inactive');
        Route::post('client-address/upload-location-image','App\Http\Controllers\ClientAddressController@uploadLocationImage');
        Route::post('client-address/delete-location-image','App\Http\Controllers\ClientAddressController@deleteLocationImage');
        Route::post('client-address/update-location','App\Http\Controllers\ClientAddressController@updateLocation');
        Route::post('client-address/remove-location','App\Http\Controllers\ClientAddressController@removeLocation');

        /*PROVEEDORES*/
        Route::get('supplier','App\Http\Controllers\SupplierController@index');
        Route::post('supplier/store','App\Http\Controllers\SupplierController@store');
        Route::post('supplier/update','App\Http\Controllers\SupplierController@update');
        Route::post('supplier/delete','App\Http\Controllers\SupplierController@inactive');
        Route::post('supplier/inactive','App\Http\Controllers\SupplierController@inactive');
        Route::post('supplier/active','App\Http\Controllers\SupplierController@active');

        /*CATEGORIAS*/
        Route::resource('category','App\Http\Controllers\CategoryController');
        Route::get('/category/get/all','App\Http\Controllers\CategoryController@all');
        Route::post('category/edit/update','App\Http\Controllers\CategoryController@update');
        Route::post('category/delete','App\Http\Controllers\CategoryController@inactive');

        /*SERVICIOS*/
        Route::resource('service','App\Http\Controllers\ServiceController');
        Route::post('service/edit/update','App\Http\Controllers\ServiceController@update');
        Route::post('service/delete','App\Http\Controllers\ServiceController@inactive');

        /*PRODUCTOS*/
        Route::resource('product','App\Http\Controllers\ProductController');
        Route::post('product/edit/update','App\Http\Controllers\ProductController@update');
        Route::post('product/edit/update-stock','App\Http\Controllers\ProductController@updateStock');
        Route::post('product/delete','App\Http\Controllers\ProductController@inactive');

        Route::post('product/upload-image','App\Http\Controllers\ProductController@uploadImageProduct');
        Route::post('product/delete-main-image','App\Http\Controllers\ProductController@deleteMainImage');
        Route::post('product/delete-alt-image','App\Http\Controllers\ProductController@deleteAltImage');

        /* SHOP */
        Route::get('/shop','App\Http\Controllers\ShopController@getShop');
        Route::post('shop/update','App\Http\Controllers\ShopController@update');
        Route::post('shop/upload-logo','App\Http\Controllers\ShopController@uploadLogo');
        Route::post('shop/delete-logo','App\Http\Controllers\ShopController@deleteLogo');

        /*RECIBOS*/
        Route::get('receipt/all',[ReceiptController::class,'getAll']);
        Route::get('receipt/{client_id}',[ReceiptController::class,'index']);
        Route::get('receipt/detail/{receipt_id}','App\Http\Controllers\ReceiptDetailController@getDetail');
        Route::get('receipt/detail/get-stock-current/{receipt_id}','App\Http\Controllers\ReceiptDetailController@getgetStockCurrentDetail');
        Route::post('receipt/store',[ReceiptController::class,'store']);
        Route::post('receipt/edit/update-status',[ReceiptController::class,'updateStatus']);
        Route::post('receipt/edit/cancel',[ReceiptController::class,'cancel']);
        Route::post('receipt/edit/devolucion',[ReceiptController::class,'devolucion']);
        Route::post('receipt/edit/update-info',[ReceiptController::class,'updateInfo']);
        Route::post('receipt/edit/update-venta/',[ReceiptController::class,'updateReceiptVentas']);
        
        Route::patch('receipt/{id}/update-invoiced/',[ReceiptController::class,'updateInvoiced']);
        
        Route::post('receipt/delete',[ReceiptController::class,'delete']);
            /*PRINT PDF*/
            Route::get('receipt/pdf/print-receipt-rent', [ReceiptController::class,'printReceiptRent']);
            /*NUEVO PDF PARA COMPARTIR DESDE APP CON AUTENTICACIÓN*/
            Route::get('receipt/{id}/pdf', [ReceiptController::class, 'createPDFReceiptRent']);

        Route::post('receipt/edit/update/quotation-to-sale',[ReceiptController::class,'updateQuotationToSale']);
        /*PartialPayments*/
        Route::post('receipt/partial-payment/store','App\Http\Controllers\PartialPaymentsController@store');
        Route::post('receipt/partial-payment/delete','App\Http\Controllers\PartialPaymentsController@delete');

        /**EXTRA FIELDS*/

        Route::get('extra-fields/get','App\Http\Controllers\ExtraFieldsShopController@getApiExtraFieldsShop');
        Route::post('extra-fields/store','App\Http\Controllers\ExtraFieldsShopController@storeApiExtraFieldsShop');
        Route::post('extra-fields/update','App\Http\Controllers\ExtraFieldsShopController@updateApiExtraFieldsShop');
        Route::post('extra-fields/delete','App\Http\Controllers\ExtraFieldsShopController@destroyApiExtraFieldsShop');


        /* PRUCHASE ORDER*/
        Route::get('purchase-order/all',[PurchaseOrderController::class,'getAll']);
        Route::post('purchase-order/store',[PurchaseOrderController::class,'store']);
        Route::post('purchase-order/update',[PurchaseOrderController::class,'update']);
        Route::get('purchase-order/detail/{purchase_order_id}',[PurchaseOrderDetailController::class,'getDetail']);
        Route::post('purchase-order/edit/update-status',[PurchaseOrderController::class,'updateStatus']);
        Route::post('purchase-order/edit/update/complete-purchase-order',[PurchaseOrderController::class,'updateCompletePurchaseOrder']);
        Route::post('puchase-order/edit/cancel',[PurchaseOrderController::class,'cancel']);
        Route::post('puchase-order/edit/porpagarpagar',[PurchaseOrderController::class,'updatePorpagarpagar']);
        Route::post('purchase-order/partial-payment/store',[PurchaseOrderPartialPaymentsController::class,'store']);
        Route::post('purchase-order/partial-payment/delete',[PurchaseOrderPartialPaymentsController::class,'delete']);
        /*NUEVO PDF PARA COMPARTIR DESDE APP CON AUTENTICACIÓN*/
        Route::get('purchase-order/{id}/pdf', [PurchaseOrderController::class, 'createPDFPurchaseOrder']);

        Route::patch('purchase-order/{id}/update-invoiced/',[PurchaseOrderController::class,'updateInvoiced']);

        /*REPORTES*/
        Route::get('report/mes', [ReportsController::class, 'mensual']);
        Route::get('report/clientes-adeudos', [ReportsController::class, 'clientesAdeudos']);
        Route::get('report/rentas/mes', [ReportsController::class, 'rentasMensual']);
        //REPORTES / ingresos
        Route::get('report/ingresos-xfechas', [ReportsController::class, 'ingresosxFechas']);
        Route::get('report/ingresos-xfechas/excel', [ReportsController::class, 'descargarIngresosExcel']);
        //REPORTES / egresos
        Route::get('report/egresos-xfechas', [ReportsController::class, 'egresosxFechas']);
        Route::get('report/egresos-xfechas/excel', [ReportsController::class, 'descargarEgresosExcel']);

        Route::post('reports/diferencias-mensual', [ReportsController::class, 'diferenciasMensual']);




        

        /*NOTIFICATIONS*/
        Route::get('notifications/get',[NotificationController::class,'get']);
        Route::post('notifications/read',[NotificationController::class,'read']);
        Route::get('notifications/get/client/{client_id}',[NotificationController::class,'getClientxID']);
        Route::get('notifications/get/task/{task_id}',[NotificationController::class,'getTaskxID']);

        Route::get('notifications/get/client-service/{client_service_id}',[NotificationController::class,'getClientServicexID']);

        Route::get('notifications/get/receipt/{receipt_id}',[NotificationController::class,'getReceiptxID']);

        /*NOTIFICATIONS - FUNCIONALIDAD GRUPAL*/
        Route::post('notifications/mark-all-read',[NotificationController::class,'markAllAsRead']);
        Route::post('notifications/delete-for-all',[NotificationController::class,'deleteForAll']);
        Route::get('notifications/group-stats',[NotificationController::class,'getGroupStats']);

        /*FCM PUSH NOTIFICATIONS*/
        Route::post('fcm/register-token', function (Request $request) {
            \Log::info('📱 FCM Register Token - Request received', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'user' => $request->user() ? $request->user()->id : 'NO USER'
            ]);
            
            try {
                $request->validate([
                    'token' => 'required|string',
                    'device_type' => 'in:android,ios'
                ]);
                
                $user = $request->user();
                
                if (!$user) {
                    \Log::error('❌ FCM Register Token - No authenticated user');
                    return response()->json(['error' => 'Unauthenticated'], 401);
                }
                
                \Log::info('📱 FCM Register Token - Saving token for user', [
                    'user_id' => $user->id,
                    'token' => substr($request->token, 0, 20) . '...',
                    'device_type' => $request->device_type ?? 'android'
                ]);
                
                \App\Models\FcmToken::updateOrCreate(
                    ['user_id' => $user->id, 'token' => $request->token],
                    [
                        'device_type' => $request->device_type ?? 'android',
                        'last_used_at' => now()
                    ]
                );
                
                \Log::info('✅ FCM Register Token - Token saved successfully');
                
                return response()->json(['success' => true, 'message' => 'FCM token registered']);
            } catch (\Exception $e) {
                \Log::error('❌ FCM Register Token - Error:', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        Route::post('fcm/test-push', function (Request $request) {
            $user = $request->user();
            $firebaseService = app(\App\Services\FirebaseService::class);
            
            $sent = $firebaseService->sendToUser(
                $user->id,
                'Test Push J2B',
                'Esta es una notificación push de prueba',
                ['type' => 'test']
            );
            
            return response()->json([
                'success' => $sent,
                'message' => $sent ? 'Push notification sent' : 'No tokens found'
            ]);
        });

        Route::post('fcm/unregister-token', function (Request $request) {
            $user = $request->user();
            
            \App\Models\FcmToken::where('user_id', $user->id)->delete();
            
            return response()->json(['success' => true, 'message' => 'FCM tokens removed']);
        });
        
        // 🧪 TEMPORAL: Test FCM directo (sin tokens reales)
        Route::post('fcm/test-direct', function (Request $request) {
            try {
                $user = $request->user();
                $firebaseService = app(\App\Services\FirebaseService::class);
                
                // Simular token de prueba (este token NO existe, solo para test)
                $fakeToken = 'test-token-' . $user->id . '-' . time();
                
                \Log::info("🧪 Test FCM Directo - Usuario: {$user->id}, Token fake: {$fakeToken}");
                
                // Test directo usando Firebase SDK
                $factory = (new \Kreait\Firebase\Factory)->withServiceAccount(config('firebase.credentials'));
                $messaging = $factory->createMessaging();
                
                \Log::info("✅ Firebase Factory creado correctamente");
                
                // Test con token fake (fallará pero confirmará configuración)
                try {
                    $notification = \Kreait\Firebase\Messaging\Notification::create(
                        'Test FCM Directo J2B',
                        'Este es un test directo de Firebase desde backend'
                    );
                    
                    $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $fakeToken)
                        ->withNotification($notification)
                        ->withData(['type' => 'test_direct', 'user_id' => (string)$user->id]);
                    
                    $result = $messaging->send($message);
                    
                    \Log::info("🚀 Mensaje FCM enviado (fake): " . json_encode($result));
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'FCM Test directo ejecutado correctamente',
                        'firebase_config' => 'OK',
                        'fake_token' => $fakeToken,
                        'result' => 'Enviado a token fake (esperado fallo en entrega)'
                    ]);
                    
                } catch (\Exception $fcmError) {
                    \Log::error("❌ Error FCM específico: " . $fcmError->getMessage());
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'FCM configurado pero falló envío (normal con token fake)',
                        'firebase_config' => 'OK',
                        'fcm_error' => $fcmError->getMessage(),
                        'note' => 'Error esperado con token fake - configuración correcta'
                    ]);
                }
                
            } catch (\Exception $e) {
                \Log::error("💥 Error Firebase Config: " . $e->getMessage());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error de configuración Firebase',
                    'error' => $e->getMessage(),
                    'firebase_config' => 'ERROR'
                ]);
            }
        });

        /*RENTAS*/
        Route::get('rents/get/by-cutoff','App\Http\Controllers\RentController@getByCutoff');
        /*Usuario Reset Pass*/
        Route::post('user/reset-password', '\App\Http\Controllers\UsuarioController@resetPassword');

         /*EQUIPMENT*/
         Route::get('equipment','App\Http\Controllers\EquipmentController@index');
         Route::post('equipment/store','App\Http\Controllers\EquipmentController@store');
         Route::post('equipment/update','App\Http\Controllers\EquipmentController@update');
         Route::post('equipment/delete','App\Http\Controllers\EquipmentController@destroy');
         Route::post('equipment/active','App\Http\Controllers\EquipmentController@active');
         Route::post('equipment/inactive','App\Http\Controllers\EquipmentController@inactive');
         //nuevas para imagenes
         Route::post('equipment/upload-image','App\Http\Controllers\EquipmentController@uploadImage');
         Route::post('equipment/delete-image','App\Http\Controllers\EquipmentController@deleteImage');

         /*SERVICES CLIENTS*/
         Route::get('services-clients',[ServicesClientController::class,'index']);
         Route::get('services-clients/get-num-status',[ServicesClientController::class,'getNumPorEstatus']);
         Route::post('services-clients/store',[ServicesClientController::class,'store']);
         Route::post('services-clients/update',[ServicesClientController::class,'update']);
         Route::post('services-clients/delete',[ServicesClientController::class,'destroy']);
         Route::post('services-clients/active',[ServicesClientController::class,'active']);
         Route::post('services-clients/inactive',[ServicesClientController::class,'inactive']);
         Route::post('services-clients/update-status',[ServicesClientController::class,'updateEstatus']);
         Route::post('services-clients/update-resena',[ServicesClientController::class,'updateResena']);
         Route::post('services-clients/upload-image-client-service',[ServicesClientController::class,'uploadImageClientService']);
         Route::post('services-clients/delete-main-image',[ServicesClientController::class,'deleteMainImage']);
         Route::post('services-clients/delete-alt-image',[ServicesClientController::class,'deleteAltImage']);
         Route::post('services-clients/sign',[ServicesClientController::class,'signService']);
         Route::post('services-clients/delete-signature',[ServicesClientController::class,'deleteSignature']);

         /*TASKS*/
         Route::get('tasks',[TaskController::class,'index']);
         Route::get('tasks/get-num-status',[TaskController::class,'getNumPorEstatus']);
         Route::post('task/store',[TaskController::class,'store']);
         Route::post('task/update',[TaskController::class,'update']);
         Route::post('task/delete',[TaskController::class,'destroy']);
         Route::post('task/active',[TaskController::class,'active']);
         Route::post('task/inactive',[TaskController::class,'inactive']);
         Route::post('task/update-status',[TaskController::class,'updateEstatus']);
         Route::post('task/update-resena',[TaskController::class,'updateResena']);
         Route::post('task/upload-image-task',[TaskController::class,'uploadImageTask']);
         Route::post('task/delete-main-image',[TaskController::class,'deleteMainImage']);
         Route::post('task/delete-alt-image',[TaskController::class,'deleteAltImage']);
         Route::post('task/save-signature',[TaskController::class,'saveSignature']);
         Route::post('task/update-signature',[TaskController::class,'updateSignature']);
         Route::post('task/delete-signature',[TaskController::class,'deleteSignature']);

        /*COLLABORATORS*/
        Route::get('collaborators','App\Http\Controllers\CollaboratorController@index');
        Route::get('collaborator/verify-user-email','App\Http\Controllers\CollaboratorController@verifyUserEmail');
        Route::post('collaborator/store','App\Http\Controllers\CollaboratorController@store');
        Route::post('collaborator/update','App\Http\Controllers\CollaboratorController@update');
        Route::post('collaborator/active','App\Http\Controllers\CollaboratorController@active');
        Route::post('collaborator/inactive','App\Http\Controllers\CollaboratorController@inactive');

        /*ADMINISTRATORS*/
        Route::get('administrators','App\Http\Controllers\AdministratorController@index');
        Route::get('administrator/verify-user-email','App\Http\Controllers\AdministratorController@verifyUserEmail');
        Route::post('administrator/store','App\Http\Controllers\AdministratorController@store');
        Route::post('administrator/update','App\Http\Controllers\AdministratorController@update');
        Route::post('administrator/active','App\Http\Controllers\AdministratorController@active');
        Route::post('administrator/inactive','App\Http\Controllers\AdministratorController@inactive');
        Route::post('administrator/update-lmited','App\Http\Controllers\AdministratorController@updateLimited');

        /*ROUTES APP-CLIENTS*/
        Route::get('client-services',[ClientServiceController::class,'index']);
        Route::post('client-services/store',[ClientServiceController::class,'store']);
        Route::post('client-services/update',[ClientServiceController::class,'update']);
        Route::post('client-services/delete-main-image',[ClientServiceController::class,'deleteMainImage']);
        Route::post('client-services/upload-image',[ClientServiceController::class,'uploadImage']);

        Route::get('app-client/catalogo/categories','App\Http\Controllers\CatalogoController@categories');
        Route::get('app-client/catalogo/products','App\Http\Controllers\CatalogoController@products');

        Route::get('app-client/pruchases/get',  [ ClientPurchaseController::class, 'getPurchasesClient']);
        Route::post('app-client/pruchase/store',  [ ClientPurchaseController::class, 'store']);
        Route::post('app-client/pruchase/update',  [ ClientPurchaseController::class, 'update']);

        // RUTAS PARA UBICACIÓN DEL CLIENTE AUTENTICADO (my-location)
        Route::get('app-client/location/my', [ClientLocationController::class, 'getMyLocation']);
        Route::post('app-client/location/save', [ClientLocationController::class, 'saveMyLocation']);
        Route::get('app-client/location/can-save', [ClientLocationController::class, 'canSaveLocation']);



        Route::get('expenses', [ExpenseController::class, 'index']);
        Route::post('expenses', [ExpenseController::class, 'store']);
        Route::match(['put', 'patch'], 'expenses/{id}', [ExpenseController::class, 'update']);

        Route::patch('expenses/{id}/activate', [ExpenseController::class, 'active']);
        Route::patch('expenses/{id}/deactivate', [ExpenseController::class, 'inactive']);
        Route::patch('expenses/{id}/facturado', [ExpenseController::class, 'facturado']);
        Route::patch('expenses/{id}/no-facturado', [ExpenseController::class, 'noFacturado']);
        Route::patch('expenses/{id}/status', [ExpenseController::class, 'updateStatus']);
        Route::patch('expenses/{id}/fecha', [ExpenseController::class, 'updateFecha']);
        Route::patch('expenses/{id}/total', [ExpenseController::class, 'updateTotal']);
        Route::get('expenses/{id}/logs', [ExpenseController::class, 'logs']);

        Route::get('expenses/{id}/attachments', [ExpenseController::class, 'getAttachments']);
        Route::post('expenses/{id}/attachments', [ExpenseController::class, 'uploadAttachment']);
        Route::delete('attachments/{id}', [ExpenseController::class, 'deleteAttachment']);

        Route::post('expenses/{id}/attachments-image', [ExpenseController::class, 'uploadImageExpense']);
        Route::delete('attachments-image/{id}', [ExpenseController::class, 'deleteImageExpense']);

        /*------------------------------------------------------------------
        /* CONTRACTS MODULE - Sistema de Contratos
        /* Flujo: Admin crea plantillas -> Asigna a clientes -> Cliente firma
        /*------------------------------------------------------------------*/
        
        /* ADMIN FUNCTIONS - Para administradores en la APP */
        Route::get('contract-templates', 'App\Http\Controllers\ContractTemplateController@apiIndex');           // Ver plantillas de su tienda
        Route::post('contracts/assign', 'App\Http\Controllers\ContractController@assignToClient');             // Asignar plantilla a cliente específico
        Route::get('contracts/admin', 'App\Http\Controllers\ContractController@adminIndex');                   // Ver todos los contratos de su tienda
        Route::get('contracts/{id}/status', 'App\Http\Controllers\ContractController@getStatus');              // Ver estado del contrato (firmado/pendiente)
        
        /* CLIENT FUNCTIONS - Para clientes en la APP */  
        Route::get('contracts/my', 'App\Http\Controllers\ContractController@getMyContracts');                 // Ver mis contratos (cliente autenticado)
        Route::get('client/{client_id}/contracts', 'App\Http\Controllers\ContractController@getClientContracts');    // Ver contratos del cliente
        Route::get('contracts/{contract}/view', 'App\Http\Controllers\ContractController@viewContract');     // Ver contenido de contrato específico
        Route::post('contracts/{contract}/sign', 'App\Http\Controllers\ContractController@saveSignature');   // Firmar contrato digitalmente
        Route::post('contracts/delete-signature', 'App\Http\Controllers\ContractController@deleteSignature'); // Eliminar firma de contrato
        
        /* SHARED FUNCTIONS - Para ambos roles */
        Route::get('contracts/{contract}/pdf', 'App\Http\Controllers\ContractController@generatePdf');       // Descargar PDF del contrato
        Route::get('contracts/{contract}/preview', 'App\Http\Controllers\ContractController@show');          // Preview del contrato con datos

        /* PUSHER NOTIFICATIONS - Ruta de prueba para notificaciones J2B */
        Route::post('/test-notification-j2b', function (Request $request) {
            $user = $request->user();
            $shop_id = $user->shop_id;
            
            // Crear notificación de prueba
            $notification = new \App\Models\Notification();
            $notification->user_id = $user->id;
            $notification->description = 'Notificación de prueba en tiempo real J2B';
            $notification->type = 'test';
            $notification->action = 'test';
            $notification->data = 0;
            $notification->read = 0;
            $notification->save();
            
            // Disparar evento
            event(new \App\Events\ClientServiceNotification($notification, $shop_id));
            
            return response()->json(['success' => true, 'message' => 'Notificación J2B enviada']);
        });





    });
});
/*------------------------------------------------------------------
/* ./ END RUTAS PROTEGIDAS
/*------------------------------------------------------------------*/