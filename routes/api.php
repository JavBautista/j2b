<?php

use App\Http\Controllers\uploadLocationImageClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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



Route::get('notifications/test','App\Http\Controllers\NotificationController@test');

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

        Route::get('client/verify-user-email','App\Http\Controllers\ClientController@verifyUserEmail');
        Route::post('client/store-user-app','App\Http\Controllers\ClientController@storeUserApp');

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
        Route::post('product/delete-image','App\Http\Controllers\ProductController@deleteImageProduct');

        /* SHOP */
        Route::get('/shop','App\Http\Controllers\ShopController@getShop');
        Route::post('shop/update','App\Http\Controllers\ShopController@update');

        /*RECIBOS*/
        Route::get('receipt/all','App\Http\Controllers\ReceiptController@getAll');
        Route::get('receipt/{client_id}','App\Http\Controllers\ReceiptController@index');
        Route::get('receipt/detail/{receipt_id}','App\Http\Controllers\ReceiptDetailController@getDetail');
        Route::get('receipt/detail/get-stock-current/{receipt_id}','App\Http\Controllers\ReceiptDetailController@getgetStockCurrentDetail');
        Route::post('receipt/store','App\Http\Controllers\ReceiptController@store');
        Route::post('receipt/edit/update-status','App\Http\Controllers\ReceiptController@updateStatus');
        Route::post('receipt/edit/cancel','App\Http\Controllers\ReceiptController@cancel');
        Route::post('receipt/edit/devolucion','App\Http\Controllers\ReceiptController@devolucion');
        Route::post('receipt/edit/update-info','App\Http\Controllers\ReceiptController@updateInfo');
        Route::post('receipt/edit/update-venta/','App\Http\Controllers\ReceiptController@updateReceiptVentas');
        Route::post('receipt/delete','App\Http\Controllers\ReceiptController@delete');
            /*PRINT PDF*/
            Route::get('receipt/pdf/print-receipt-rent', 'App\Http\Controllers\ReceiptController@printReceiptRent');
        Route::post('receipt/edit/update/quotation-to-sale','App\Http\Controllers\ReceiptController@updateQuotationToSale');
        /*PartialPayments*/
        Route::post('receipt/partial-payment/store','App\Http\Controllers\PartialPaymentsController@store');
        Route::post('receipt/partial-payment/delete','App\Http\Controllers\PartialPaymentsController@delete');

        /**EXTRA FIELDS*/

        Route::get('extra-fields/get','App\Http\Controllers\ExtraFieldsShopController@getApiExtraFieldsShop');
        Route::post('extra-fields/store','App\Http\Controllers\ExtraFieldsShopController@storeApiExtraFieldsShop');
        Route::post('extra-fields/update','App\Http\Controllers\ExtraFieldsShopController@updateApiExtraFieldsShop');
        Route::post('extra-fields/delete','App\Http\Controllers\ExtraFieldsShopController@destroyApiExtraFieldsShop');


        /* PRUCHASE ORDER*/
        Route::get('purchase-order/all','App\Http\Controllers\PurchaseOrderController@getAll');
        Route::post('purchase-order/store','App\Http\Controllers\PurchaseOrderController@store');
        Route::post('purchase-order/update','App\Http\Controllers\PurchaseOrderController@update');
        Route::get('purchase-order/detail/{purchase_order_id}','App\Http\Controllers\PurchaseOrderDetailController@getDetail');
        Route::post('purchase-order/edit/update-status','App\Http\Controllers\PurchaseOrderController@updateStatus');
        Route::post('purchase-order/edit/update/complete-purchase-order','App\Http\Controllers\PurchaseOrderController@updateCompletePurchaseOrder');
        Route::post('puchase-order/edit/cancel','App\Http\Controllers\PurchaseOrderController@cancel');
        Route::post('puchase-order/edit/porpagarpagar','App\Http\Controllers\PurchaseOrderController@updatePorpagarpagar');
        Route::post('purchase-order/partial-payment/store','App\Http\Controllers\PurchaseOrderPartialPaymentsController@store');
        Route::post('purchase-order/partial-payment/delete','App\Http\Controllers\PurchaseOrderPartialPaymentsController@delete');

        /*REPORTES*/
        Route::get('report/mes','App\Http\Controllers\ReportsController@mensual');
        Route::get('report/clientes-adeudos','App\Http\Controllers\ReportsController@clientesAdeudos');
        Route::get('report/rentas/mes','App\Http\Controllers\ReportsController@rentasMensual');

        /*NOTIFICATIONS*/
        Route::get('notifications/get','App\Http\Controllers\NotificationController@get');
        Route::post('notifications/read','App\Http\Controllers\NotificationController@read');
        Route::get('notifications/get/client/{client_id}','App\Http\Controllers\NotificationController@getClientxID');
        Route::get('notifications/get/task/{task_id}','App\Http\Controllers\NotificationController@getTaskxID');

        Route::get('notifications/get/client-service/{client_service_id}','App\Http\Controllers\NotificationController@getClientServicexID');

        Route::get('notifications/get/receipt/{receipt_id}','App\Http\Controllers\NotificationController@getReceiptxID');

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
         Route::get('services-clients','App\Http\Controllers\ServicesClientController@index');
         Route::get('services-clients/get-num-status','App\Http\Controllers\ServicesClientController@getNumPorEstatus');
         Route::post('services-clients/store','App\Http\Controllers\ServicesClientController@store');
         Route::post('services-clients/update','App\Http\Controllers\ServicesClientController@update');
         Route::post('services-clients/delete','App\Http\Controllers\ServicesClientController@destroy');
         Route::post('services-clients/active','App\Http\Controllers\ServicesClientController@active');
         Route::post('services-clients/inactive','App\Http\Controllers\ServicesClientController@inactive');
         Route::post('services-clients/update-status','App\Http\Controllers\ServicesClientController@updateEstatus');
         Route::post('services-clients/update-resena','App\Http\Controllers\ServicesClientController@updateResena');
         Route::post('services-clients/upload-image-client-service','App\Http\Controllers\ServicesClientController@uploadImageClientService');
         Route::post('services-clients/delete-main-image','App\Http\Controllers\ServicesClientController@deleteMainImage');
         Route::post('services-clients/delete-alt-image','App\Http\Controllers\ServicesClientController@deleteAltImage');

         /*TASKS*/
         Route::get('tasks','App\Http\Controllers\TaskController@index');
         Route::get('tasks/get-num-status','App\Http\Controllers\TaskController@getNumPorEstatus');
         Route::post('task/store','App\Http\Controllers\TaskController@store');
         Route::post('task/update','App\Http\Controllers\TaskController@update');
         Route::post('task/delete','App\Http\Controllers\TaskController@destroy');
         Route::post('task/active','App\Http\Controllers\TaskController@active');
         Route::post('task/inactive','App\Http\Controllers\TaskController@inactive');
         Route::post('task/update-status','App\Http\Controllers\TaskController@updateEstatus');
         Route::post('task/update-resena','App\Http\Controllers\TaskController@updateResena');
         Route::post('task/upload-image-task','App\Http\Controllers\TaskController@uploadImageTask');
         Route::post('task/delete-main-image','App\Http\Controllers\TaskController@deleteMainImage');
         Route::post('task/delete-alt-image','App\Http\Controllers\TaskController@deleteAltImage');

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
        Route::get('client-services','App\Http\Controllers\ClientServiceController@index');
        Route::post('client-services/store','App\Http\Controllers\ClientServiceController@store');
        Route::post('client-services/update','App\Http\Controllers\ClientServiceController@update');
        Route::post('client-services/delete-main-image','App\Http\Controllers\ClientServiceController@deleteMainImage');
        Route::post('client-services/upload-image','App\Http\Controllers\ClientServiceController@uploadImage');

        Route::get('app-client/catalogo/categories','App\Http\Controllers\CatalogoController@categories');
        Route::get('app-client/catalogo/products','App\Http\Controllers\CatalogoController@products');

        Route::get('app-client/pruchases/get','App\Http\Controllers\Clients\PurchaseController@getPurchasesClient');
        Route::post('app-client/pruchase/store','App\Http\Controllers\Clients\PurchaseController@store');
        Route::post('app-client/pruchase/update','App\Http\Controllers\Clients\PurchaseController@update');


    });
});
/*------------------------------------------------------------------
/* ./ END RUTAS PROTEGIDAS
/*------------------------------------------------------------------*/