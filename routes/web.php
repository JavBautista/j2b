<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\EmailConfirmationController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\AdminPagesController;
use App\Http\Controllers\Auth\UnauthorizedController;
use App\Http\Controllers\RequestsJ2bController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ExtraFieldsShopController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractTemplateController;
use App\Http\Controllers\SuperadminPagesController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\Superadmin\ShopsController;
use  App\Http\Controllers\Superadmin\PlansController;
use App\Http\Controllers\Superadmin\UsersController as SuperadminUsersController;
use App\Http\Controllers\Admin\ClientsController;
use App\Http\Controllers\Admin\SuppliersController;
use App\Http\Controllers\Admin\TasksController;
use App\Http\Controllers\Admin\UsersController as AdminUsersController;
use App\Http\Controllers\Admin\RentsController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\Superadmin\ContactMessagesController;
use App\Http\Controllers\Superadmin\LegalDocumentsController;
use App\Http\Controllers\Superadmin\CfdiController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('confirmar-email/{token}', [EmailConfirmationController::class, 'confirmar'])->name('email.confirmar');

// Password reset routes (public)
Route::get('reset-password/{token}', [\App\Http\Controllers\AuthController::class, 'showResetForm'])->name('password.reset.form');
Route::post('reset-password', [\App\Http\Controllers\AuthController::class, 'processResetPassword'])->name('password.reset.process');

Route::get('/', [HomeController::class, 'index']);

// Formulario de Contacto (público)
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Descarga directa APK (público)
Route::get('/descargar', [DownloadController::class, 'showForm'])->name('download.form');
Route::post('/descargar', [DownloadController::class, 'processDownload'])->name('download.process');

// Documentos legales (público)
Route::get('/terminos', [LegalPageController::class, 'terms'])->name('legal.terms');
Route::get('/privacidad', [LegalPageController::class, 'privacy'])->name('legal.privacy');

Route::get('/pre-registro', [RequestsJ2bController::class, 'j2bSolicitar'])->name('solicitud');
Route::post('/pre-registro/create', [RequestsJ2bController::class, 'store'])->name('solicitud.store');
Route::get('/pre-registro/confirm/{xtoken}', [RequestsJ2bController::class, 'confirm'])->name('solicitud.confirm');
Route::get('/pre-registro/completar', [RequestsJ2bController::class, 'completar'])->name('solicitud.completar')->middleware('check.token');
Route::get('/pre-registro/error', [RequestsJ2bController::class, 'error'])->name('solicitud.error');
Route::post('/registro/create', [RequestsJ2bController::class, 'store'])->name('X.solicitud.store');
Route::get('/print-receipt-rent', [ReceiptController::class, 'printReceiptRent']);
Route::get('/print-purchase-order', [PurchaseOrderController::class, 'printPurchaseOrder']);
Route::get('/print-contract', [ContractController::class, 'printContract']);


Auth::routes([
    'login'    => true,
    'logout'   => true,
    'register' => true,
    'reset'    => false,
    'confirm'  => false,
    'verify'   => false,
]);

// Ruta para autorización de broadcasting (Pusher)
Broadcast::routes(['middleware' => ['auth:api']]);

// Ruta para acceso no autorizado
Route::get('/unauthorized', [UnauthorizedController::class, 'index'])->name('unauthorized');
Route::post('/unauthorized/logout', [UnauthorizedController::class, 'logout'])->name('unauthorized.logout');

Route::group(['middleware' => ['auth', 'web.access']], function () {
    Route::get('/user/passwords/reset', [HomeController::class, 'passwordReset'])->name('password.reset');
    Route::post('/user/passwords/update', [HomeController::class, 'updatePassword'])->name('password.update');

    //====================RUTAS AUTH/SUPER ADMIN DE TODO====================
    Route::group(['middleware' => 'superadmin'], function () {
        //Index
        Route::get('/superadmin', [SuperadminPagesController::class, 'index'])->name('superadmin.index');
        //Shops
        Route::get('/superadmin/shops', [SuperadminPagesController::class, 'shops'])->name('superadmin.shops');

        Route::get('/superadmin/shops/get', [ShopsController::class, 'get']);
        Route::post('/superadmin/shops/store', [ShopsController::class, 'store']);
        Route::put('/superadmin/shops/update', [ShopsController::class, 'update']);
        Route::put('/superadmin/shops/active', [ShopsController::class, 'active']);
        Route::put('/superadmin/shops/deactive', [ShopsController::class, 'deactive']);
        Route::post('/superadmin/shops/upload-logo', [ShopsController::class, 'uploadLogo']);
        Route::put('/superadmin/shops/update-cutoff', [ShopsController::class, 'updateCutoff']);
        Route::get('/superadmin/shops/{id}/info', [ShopsController::class, 'getInfo']);
        Route::get('/superadmin/shops/{id}/stats', [ShopsController::class, 'getStats']);

        //Plans
        Route::get('/superadmin/plans', [SuperadminPagesController::class, 'plans'])->name('superadmin.plans');
        Route::get('/superadmin/plans/get', [PlansController::class, 'get']);
        Route::post('/superadmin/plans/store', [PlansController::class, 'store']);
        Route::put('/superadmin/plans/update', [PlansController::class, 'update']);
        Route::put('/superadmin/plans/active', [PlansController::class, 'active']);
        Route::put('/superadmin/plans/deactive', [PlansController::class, 'deactive']);

        //Users
        Route::get('/superadmin/users', [SuperadminPagesController::class, 'users'])->name('superadmin.users');
        Route::get('/superadmin/users/get', [SuperadminUsersController::class, 'get']);
        Route::post('/superadmin/users/store', [SuperadminUsersController::class, 'store']);
        Route::put('/superadmin/users/update', [SuperadminUsersController::class, 'updateInfo']);
        Route::put('/superadmin/users/active', [SuperadminUsersController::class, 'updateToActive']);
        Route::put('/superadmin/users/inactive', [SuperadminUsersController::class, 'updateToInactive']);
        Route::put('/superadmin/users/reset-password', [SuperadminUsersController::class, 'resetPassword']);
        Route::put('/superadmin/users/update-email', [SuperadminUsersController::class, 'updateEmail']);
        Route::put('/superadmin/users/toggle-ai', [SuperadminUsersController::class, 'toggleAI']);

        //Pre Registers
        Route::get('/superadmin/pre-registers', [SuperadminPagesController::class, 'preRegisters'])->name('superadmin.pre-registers');
        Route::get('/superadmin/pre-registers/get', [App\Http\Controllers\Superadmin\RequestsJ2bController::class, 'getRegisters']);

        //Subscription Settings
        Route::get('/superadmin/subscription-settings', [SuperAdminController::class, 'subscriptionSettings'])->name('superadmin.subscription-settings');
        Route::post('/superadmin/subscription-settings/update', [SuperAdminController::class, 'updateSubscriptionSettings'])->name('superadmin.subscription-settings.update');

        //Subscription Management (Shops)
        Route::get('/superadmin/subscription-management', [SuperAdminController::class, 'subscriptionManagement'])->name('superadmin.subscription-management');
        Route::post('/superadmin/shops/{id}/extend-trial', [SuperAdminController::class, 'extendTrial'])->name('superadmin.shops.extend-trial');
        Route::post('/superadmin/shops/{id}/change-plan', [SuperAdminController::class, 'changePlan'])->name('superadmin.shops.change-plan');
        Route::post('/superadmin/shops/{id}/toggle-active', [SuperAdminController::class, 'toggleShopActive'])->name('superadmin.shops.toggle-active');
        Route::get('/superadmin/shops/{id}/subscription-info', [SuperAdminController::class, 'getSubscriptionInfo'])->name('superadmin.shops.subscription-info');
        Route::post('/superadmin/shops/{id}/assign-owner', [SuperAdminController::class, 'assignOwner'])->name('superadmin.shops.assign-owner');
        Route::post('/superadmin/shops/{id}/update-config', [SuperAdminController::class, 'updateShopConfig'])->name('superadmin.shops.update-config');
        Route::get('/superadmin/shops/{id}/users', [SuperAdminController::class, 'getShopUsers'])->name('superadmin.shops.users');
        Route::put('/superadmin/pre-registers/delete', [App\Http\Controllers\Superadmin\RequestsJ2bController::class, 'destroy']);

        //Subscription Management Vue.js
        Route::get('/superadmin/subscription-management/get', [SuperAdminController::class, 'get']);
        Route::get('/superadmin/subscription-management/num-status', [SuperAdminController::class, 'getNumStatus']);
        Route::get('/superadmin/subscription-management/plans', [SuperAdminController::class, 'getPlans']);
        Route::get('/superadmin/subscription-management/{id}/stats', [SuperAdminController::class, 'getShopStats']);
        Route::put('/superadmin/subscription-management/{id}/extend', [SuperAdminController::class, 'extendTrialJson']);
        Route::put('/superadmin/subscription-management/{id}/change-plan', [SuperAdminController::class, 'changePlanJson']);
        Route::put('/superadmin/subscription-management/{id}/toggle-active', [SuperAdminController::class, 'toggleShopActiveJson']);
        Route::put('/superadmin/subscription-management/{id}/toggle-exempt', [SuperAdminController::class, 'toggleExemptJson']);
        Route::put('/superadmin/subscription-management/{id}/update-config', [SuperAdminController::class, 'updateShopConfigJson']);
        Route::put('/superadmin/subscription-management/{id}/assign-owner', [SuperAdminController::class, 'assignOwnerJson']);
        Route::post('/superadmin/subscription-management/{id}/register-payment', [SuperAdminController::class, 'registerPaymentJson']);
        Route::get('/superadmin/subscription-management/{id}/payment-history', [SuperAdminController::class, 'getPaymentHistoryJson']);
        Route::get('/superadmin/subscription-management/{id}/next-period', [SuperAdminController::class, 'getNextPeriodInfo']);

        // Shop Payments Page (Página dedicada de pagos)
        Route::get('/superadmin/subscription-management/{id}/payments', [SuperAdminController::class, 'shopPaymentsPage'])->name('superadmin.shop-payments');
        Route::get('/superadmin/subscription-management/{id}/payments/get', [SuperAdminController::class, 'getShopPayments']);
        Route::get('/superadmin/subscription-management/{id}/payments/{paymentId}', [SuperAdminController::class, 'getPaymentDetail']);
        Route::put('/superadmin/subscription-management/{id}/payments/{paymentId}', [SuperAdminController::class, 'updatePayment']);
        Route::delete('/superadmin/subscription-management/{id}/payments/{paymentId}', [SuperAdminController::class, 'deletePayment']);
        Route::get('/superadmin/subscription-management/{id}/payments/{paymentId}/pdf', [SuperAdminController::class, 'generatePaymentPdf']);

        // Contact Messages (Mensajes de contacto del landing)
        Route::get('/superadmin/contact-messages', [ContactMessagesController::class, 'index'])->name('superadmin.contact-messages');
        Route::get('/superadmin/contact-messages/get', [ContactMessagesController::class, 'get']);
        Route::get('/superadmin/contact-messages/unread-count', [ContactMessagesController::class, 'getUnreadCount']);
        Route::get('/superadmin/contact-messages/{id}', [ContactMessagesController::class, 'show']);
        Route::put('/superadmin/contact-messages/{id}/read', [ContactMessagesController::class, 'markAsRead']);
        Route::put('/superadmin/contact-messages/{id}/unread', [ContactMessagesController::class, 'markAsUnread']);
        Route::put('/superadmin/contact-messages/mark-multiple-read', [ContactMessagesController::class, 'markMultipleAsRead']);
        Route::delete('/superadmin/contact-messages/{id}', [ContactMessagesController::class, 'destroy']);
        Route::post('/superadmin/contact-messages/delete-multiple', [ContactMessagesController::class, 'destroyMultiple']);

        // Facturación CFDI
        Route::get('/superadmin/cfdi', [SuperadminPagesController::class, 'cfdi'])->name('superadmin.cfdi');
        Route::get('/superadmin/cfdi/shops', [CfdiController::class, 'getShops']);
        Route::post('/superadmin/cfdi/toggle', [CfdiController::class, 'toggleCfdi']);
        Route::post('/superadmin/cfdi/asignar-timbres-shop', [CfdiController::class, 'asignarTimbresShop']);
        Route::get('/superadmin/cfdi/get', [CfdiController::class, 'get']);
        Route::get('/superadmin/cfdi/timbres-globales', [CfdiController::class, 'getTimbresGlobales']);

        // Legal Documents (Términos y Condiciones, Aviso de Privacidad)
        Route::get('/superadmin/legal-documents', [LegalDocumentsController::class, 'index'])->name('superadmin.legal-documents');
        Route::get('/superadmin/legal-documents/get', [LegalDocumentsController::class, 'get']);
        Route::post('/superadmin/legal-documents/store', [LegalDocumentsController::class, 'store']);
        Route::put('/superadmin/legal-documents/update', [LegalDocumentsController::class, 'update']);
        Route::delete('/superadmin/legal-documents/{id}', [LegalDocumentsController::class, 'destroy']);
    }); //./Routes Middleware superadmin

    //====================RUTAS AUTH/ADMIN DE TIENDAS====================
    Route::group(['middleware' => ['admin', 'web.access']], function () {
        //Index
        Route::get('/admin', [AdminPagesController::class, 'index'])->name('admin.index');

        // 🔥 TEMPORAL: Crear servicio de prueba para testing FCM
        Route::post('/admin/test-create-service', [AdminPagesController::class, 'testCreateService'])->name('admin.test.create.service');
        Route::post('/admin/test-create-service-client', [AdminPagesController::class, 'testCreateServiceClient'])->name('admin.test.create.service.client');

        // Asistente IA
        Route::post('/admin/asistente/chat', [App\Http\Controllers\Admin\AdminAIChatController::class, 'chat'])
            ->name('admin.asistente.chat');
        //Shops
        Route::get('/admin/shop', [AdminPagesController::class, 'shop'])->name('admin.shop');
        Route::get('/admin/shop/edit', [AdminPagesController::class, 'shopEdit'])->name('admin.shop.edit');
        Route::put('/admin/shop/update', [ShopController::class, 'updateWeb'])->name('admin.shop.update');
        Route::put('/admin/shop/{shop}/update-signature', [ShopController::class, 'updateSignature'])->name('admin.shop.update-signature');
        Route::delete('/admin/shop/{shop}/delete-signature', [ShopController::class, 'deleteSignature'])->name('admin.shop.delete-signature');

        // Documentos legales (solo lectura)
        Route::get('/admin/legal/terms', [AdminPagesController::class, 'legalTerms'])->name('admin.legal.terms');
        Route::get('/admin/legal/privacy', [AdminPagesController::class, 'legalPrivacy'])->name('admin.legal.privacy');

        Route::get('/admin/configurations', [AdminPagesController::class, 'configurations'])->name('admin.configurations');

        Route::get('/admin/configurations/extra-fields-shop', [ExtraFieldsShopController::class, 'index'])->name('admin.configurations.extra_fields');

        Route::get('/admin/configurations/extra-fields-shop/create', [ExtraFieldsShopController::class, 'create'])->name('admin.configurations.extra_fields.create');

        Route::get('/admin/configurations/extra-fields-shop/edit/{id}', [ExtraFieldsShopController::class, 'edit'])->name('admin.configurations.extra_fields.edit');

        Route::post('/admin/configurations/extra-fields/store', [ExtraFieldsShopController::class, 'store'])->name('admin.configurations.extra-fields.store');

        Route::put('/admin/configurations/extra-fields-shop/{id}/toggle', [ExtraFieldsShopController::class, 'toggleShow'])->name('admin.configurations.extra_fields.toggle');

        Route::delete('/admin/configurations/extra-fields/{id}', [ExtraFieldsShopController::class, 'destroy'])->name('admin.configurations.extra_fields.destroy');

        Route::put('/admin/configurations/extra-fields-shop/update/{id}', [ExtraFieldsShopController::class, 'update'])->name('admin.configurations.extra-fields.update');

        // Configuraciones IA
        Route::get('/admin/configurations/ai-settings', [App\Http\Controllers\Admin\AiSettingsController::class, 'index'])->name('admin.configurations.ai_settings');
        // Prompt / Contexto de tienda
        Route::get('/admin/configurations/ai-settings/prompt', [App\Http\Controllers\Admin\AiSettingsController::class, 'prompt'])->name('admin.configurations.ai_settings.prompt');
        Route::get('/admin/configurations/ai-settings/prompt/get', [App\Http\Controllers\Admin\AiSettingsController::class, 'get']);
        Route::post('/admin/configurations/ai-settings/prompt/save', [App\Http\Controllers\Admin\AiSettingsController::class, 'save']);
        Route::post('/admin/configurations/ai-settings/prompt/reset', [App\Http\Controllers\Admin\AiSettingsController::class, 'resetPrompt']);
        // Indexación de productos
        Route::get('/admin/configurations/ai-settings/indexing', [App\Http\Controllers\Admin\AiSettingsController::class, 'indexing'])->name('admin.configurations.ai_settings.indexing');
        Route::get('/admin/configurations/ai-settings/indexing/status', [App\Http\Controllers\Admin\AiSettingsController::class, 'getIndexStatus']);
        Route::post('/admin/configurations/ai-settings/indexing/products', [App\Http\Controllers\Admin\AiSettingsController::class, 'indexProducts']);
        Route::post('/admin/configurations/ai-settings/indexing/services', [App\Http\Controllers\Admin\AiSettingsController::class, 'indexServices']);
        Route::post('/admin/configurations/ai-settings/indexing/all', [App\Http\Controllers\Admin\AiSettingsController::class, 'indexAll']);
        Route::post('/admin/configurations/ai-settings/indexing/clients', [App\Http\Controllers\Admin\AiSettingsController::class, 'indexClients']);

        // Facturación CFDI - Configuración Emisor
        Route::get('/admin/facturacion/configuracion', [App\Http\Controllers\Admin\CfdiConfigController::class, 'index'])->name('admin.cfdi.config');
        Route::get('/admin/facturacion/configuracion/get', [App\Http\Controllers\Admin\CfdiConfigController::class, 'get']);
        Route::post('/admin/facturacion/configuracion/save', [App\Http\Controllers\Admin\CfdiConfigController::class, 'save']);
        Route::post('/admin/facturacion/configuracion/upload-csd', [App\Http\Controllers\Admin\CfdiConfigController::class, 'uploadCsd']);
        Route::post('/admin/facturacion/configuracion/registrar', [App\Http\Controllers\Admin\CfdiConfigController::class, 'registrar']);

        // Facturación CFDI - Facturas Emitidas
        Route::get('/admin/facturacion/facturas', [App\Http\Controllers\Admin\CfdiInvoiceController::class, 'indexFacturas'])->name('admin.cfdi.facturas');
        Route::get('/admin/facturacion/facturas/get', [App\Http\Controllers\Admin\CfdiInvoiceController::class, 'getFacturas']);
        Route::get('/admin/facturacion/facturas/export', [App\Http\Controllers\Admin\CfdiInvoiceController::class, 'exportFacturas']);

        // Facturación CFDI - Timbrado de Notas
        Route::get('/admin/facturacion/receipt/{id}/data', [App\Http\Controllers\Admin\CfdiInvoiceController::class, 'getReceiptData']);
        Route::post('/admin/facturacion/timbrar', [App\Http\Controllers\Admin\CfdiInvoiceController::class, 'timbrar']);
        Route::post('/admin/facturacion/cancelar', [App\Http\Controllers\Admin\CfdiInvoiceController::class, 'cancelar']);
        Route::get('/admin/facturacion/descargar/{id}/{formato}', [App\Http\Controllers\Admin\CfdiInvoiceController::class, 'descargar']);

        Route::get('/admin/contracts', [AdminPagesController::class, 'contracts'])->name('admin.contracts');

        // Rutas para plantillas de contratos
        Route::resource('contract-templates', ContractTemplateController::class);

        // Rutas para contratos
        Route::resource('contracts', ContractController::class);
        Route::get('contracts/{contract}/generate-pdf', [ContractController::class, 'generatePdf'])->name('contracts.generate-pdf');

        // Rutas para firmas de contratos
        Route::post('contracts/save-signature', [ContractController::class, 'saveSignature'])->name('contracts.save-signature');
        Route::post('contracts/update-signature', [ContractController::class, 'updateSignature'])->name('contracts.update-signature');
        Route::post('contracts/delete-signature', [ContractController::class, 'deleteSignature'])->name('contracts.delete-signature');


        Route::get('/admin/clients', [AdminPagesController::class, 'clients'])->name('admin.clients');
        Route::get('/admin/clients/{client}/rentas', [AdminPagesController::class, 'clientRentas'])->name('admin.clients.rentas.page');

        // Rutas AJAX para CRUD de clientes (admin web - separadas de Ionic)
        Route::get('/admin/clients/get', [ClientsController::class, 'index'])->name('admin.clients.get');
        Route::post('/admin/clients/store', [ClientsController::class, 'store'])->name('admin.clients.store');
        Route::put('/admin/clients/update', [ClientsController::class, 'update'])->name('admin.clients.update');
        Route::put('/admin/clients/inactive', [ClientsController::class, 'inactive'])->name('admin.clients.inactive');
        Route::put('/admin/clients/active', [ClientsController::class, 'active'])->name('admin.clients.active');

        // Rutas para contratos desde clientes
        Route::get('/admin/clients/{client}/assign-contract', [ClientsController::class, 'assignContractPage'])->name('admin.clients.assign-contract');
        Route::post('/admin/clients/{client}/create-contract', [ClientsController::class, 'createContract'])->name('admin.clients.create-contract');
        Route::post('/admin/clients/{client}/contract-preview', [ClientsController::class, 'getContractPreview'])->name('admin.clients.contract-preview');
        Route::get('/admin/clients/{client}/contracts', [ClientsController::class, 'clientContracts'])->name('admin.clients.contracts');
        Route::get('/admin/clients/{client}/contracts/{contract}/edit', [ClientsController::class, 'editContract'])->name('admin.clients.edit-contract');
        Route::put('/admin/clients/{client}/contracts/{contract}', [ClientsController::class, 'updateContract'])->name('admin.clients.update-contract');
        Route::get('/admin/contracts/{contract}/view', [ClientsController::class, 'viewContract'])->name('admin.contracts.view');
        Route::delete('/admin/contracts/{contract}', [ClientsController::class, 'deleteContract'])->name('admin.contracts.delete');
        Route::post('/admin/clients/{client}/contracts/{contract}/cancel', [ClientsController::class, 'cancelContract'])->name('admin.clients.cancel-contract');
        Route::get('/admin/clients/{client}/contracts/{contract}/logs', [ClientsController::class, 'contractLogs'])->name('admin.clients.contract-logs');

        // Rutas AJAX para CRUD de direcciones de clientes (admin web - separadas de Ionic)
        Route::get('/admin/client-addresses/get', [App\Http\Controllers\Admin\ClientAddressController::class, 'index'])->name('admin.client-addresses.get');
        Route::post('/admin/client-addresses/store', [App\Http\Controllers\Admin\ClientAddressController::class, 'store'])->name('admin.client-addresses.store');
        Route::put('/admin/client-addresses/update', [App\Http\Controllers\Admin\ClientAddressController::class, 'update'])->name('admin.client-addresses.update');
        Route::put('/admin/client-addresses/inactive', [App\Http\Controllers\Admin\ClientAddressController::class, 'inactive'])->name('admin.client-addresses.inactive');
        Route::put('/admin/client-addresses/active', [App\Http\Controllers\Admin\ClientAddressController::class, 'active'])->name('admin.client-addresses.active');
        Route::post('/admin/client-addresses/upload-location-image', [App\Http\Controllers\Admin\ClientAddressController::class, 'uploadLocationImage'])->name('admin.client-addresses.upload-image');
        Route::delete('/admin/client-addresses/delete-location-image', [App\Http\Controllers\Admin\ClientAddressController::class, 'deleteLocationImage'])->name('admin.client-addresses.delete-image');

        // Rutas AJAX para proveedores (modal de selección)
        Route::get('/admin/suppliers/search', [SuppliersController::class, 'search'])->name('admin.suppliers.search');

        // Usuario APP Cliente
        Route::get('/admin/clients/verify-user-email', [ClientsController::class, 'verifyUserEmail'])->name('admin.clients.verify-user-email');
        Route::get('/admin/clients/{client}/get-user-app', [ClientsController::class, 'getClientUserApp'])->name('admin.clients.get-user-app');
        Route::post('/admin/clients/{client}/store-user-app', [ClientsController::class, 'storeClientUserApp'])->name('admin.clients.store-user-app');
        Route::put('/admin/clients/{client}/update-user-app', [ClientsController::class, 'updateClientUserApp'])->name('admin.clients.update-user-app');

        // Imagen de ubicación del cliente
        Route::post('/admin/clients/{client}/upload-location-image', [ClientsController::class, 'uploadLocationImage'])->name('admin.clients.upload-location-image');
        Route::delete('/admin/clients/{client}/delete-location-image', [ClientsController::class, 'deleteLocationImage'])->name('admin.clients.delete-location-image');

        // Geolocalización GPS del cliente
        Route::put('/admin/clients/{client}/update-location', [ClientsController::class, 'updateLocation'])->name('admin.clients.update-location');
        Route::delete('/admin/clients/{client}/remove-location', [ClientsController::class, 'removeLocation'])->name('admin.clients.remove-location');

        // Rentas del cliente
        Route::get('/admin/clients/{client}/rents', [RentsController::class, 'getClientRents'])->name('admin.clients.rents');

        // Rutas para gestión de Rentas (admin web)
        Route::get('/admin/rents/{rent}/details', [RentsController::class, 'getRentDetails'])->name('admin.rents.details');
        Route::post('/admin/rents/store', [RentsController::class, 'store'])->name('admin.rents.store');
        Route::put('/admin/rents/update', [RentsController::class, 'update'])->name('admin.rents.update');
        Route::put('/admin/rents/{id}/inactive', [RentsController::class, 'inactive'])->name('admin.rents.inactive');
        Route::put('/admin/rents/{id}/active', [RentsController::class, 'active'])->name('admin.rents.active');

        // Rutas para Equipos (RentDetail) - admin web
        Route::post('/admin/rents/details/store', [RentsController::class, 'storeDetail'])->name('admin.rents.details.store');
        Route::put('/admin/rents/details/update', [RentsController::class, 'updateDetail'])->name('admin.rents.details.update');
        Route::put('/admin/rents/details/{id}/liberar', [RentsController::class, 'liberarDetail'])->name('admin.rents.details.liberar');
        Route::post('/admin/rents/details/assign', [RentsController::class, 'assignEquipment'])->name('admin.rents.details.assign');
        Route::put('/admin/rents/details/{id}/url-monitor', [RentsController::class, 'updateUrlMonitor'])->name('admin.rents.details.url-monitor');
        Route::get('/admin/rents/equipments/available', [RentsController::class, 'getAvailableEquipments'])->name('admin.rents.equipments.available');

        // Rutas para Consumibles - admin web
        Route::get('/admin/rents/details/{detail}/consumables', [RentsController::class, 'getConsumables'])->name('admin.rents.consumables');
        Route::post('/admin/rents/details/{detail}/consumables/store', [RentsController::class, 'storeConsumable'])->name('admin.rents.consumables.store');

        // Rutas para recibos de clientes (admin web)
        Route::get('/admin/clients/{client}/receipts', [App\Http\Controllers\Admin\ReceiptsController::class, 'index'])->name('admin.clients.receipts');
        Route::get('/admin/clients/receipts/get', [App\Http\Controllers\Admin\ReceiptsController::class, 'getReceipts'])->name('admin.clients.receipts.get');

        // Rutas para Notas de Venta (admin web)
        Route::get('/admin/receipts', [App\Http\Controllers\Admin\ReceiptsController::class, 'list'])->name('admin.receipts');
        Route::get('/admin/receipts/list/get', [App\Http\Controllers\Admin\ReceiptsController::class, 'getList'])->name('admin.receipts.list.get');
        Route::get('/admin/receipts/create', [App\Http\Controllers\Admin\ReceiptsController::class, 'create'])->name('admin.receipts.create');
        Route::post('/admin/receipts/store', [App\Http\Controllers\Admin\ReceiptsController::class, 'store'])->name('admin.receipts.store');
        Route::get('/admin/receipts/extra-fields', [App\Http\Controllers\Admin\ReceiptsController::class, 'getExtraFields'])->name('admin.receipts.extra-fields');
        Route::get('/admin/receipts/{id}/detail', [App\Http\Controllers\Admin\ReceiptsController::class, 'getDetail'])->name('admin.receipts.detail');
        Route::get('/admin/receipts/{id}/edit', [App\Http\Controllers\Admin\ReceiptsController::class, 'edit'])->name('admin.receipts.edit');
        Route::get('/admin/receipts/{id}/show', [App\Http\Controllers\Admin\ReceiptsController::class, 'show'])->name('admin.receipts.show');
        Route::post('/admin/receipts/{id}/update', [App\Http\Controllers\Admin\ReceiptsController::class, 'update'])->name('admin.receipts.update');
        Route::get('/admin/receipts/{id}/stock-detail', [App\Http\Controllers\Admin\ReceiptsController::class, 'getStockCurrentDetail'])->name('admin.receipts.stock-detail');

        // Rutas auxiliares para modales shared
        Route::get('/admin/services/get', [App\Http\Controllers\Admin\ReceiptsController::class, 'getServices'])->name('admin.services.get');
        Route::get('/admin/equipment/get', [App\Http\Controllers\Admin\ReceiptsController::class, 'getEquipment'])->name('admin.equipment.get');

        // Rutas para Órdenes de Compra (admin web)
        Route::get('/admin/purchase-orders', [App\Http\Controllers\Admin\PurchaseOrdersController::class, 'list'])->name('admin.purchase-orders');
        Route::get('/admin/purchase-orders/list/get', [App\Http\Controllers\Admin\PurchaseOrdersController::class, 'getList'])->name('admin.purchase-orders.list.get');
        Route::get('/admin/purchase-orders/create', [App\Http\Controllers\Admin\PurchaseOrdersController::class, 'create'])->name('admin.purchase-orders.create');
        Route::get('/admin/purchase-orders/{id}/show', [App\Http\Controllers\Admin\PurchaseOrdersController::class, 'show'])->name('admin.purchase-orders.show');
        Route::get('/admin/purchase-orders/{id}/edit', [App\Http\Controllers\Admin\PurchaseOrdersController::class, 'edit'])->name('admin.purchase-orders.edit');
        Route::get('/admin/purchase-orders/{id}/detail', [App\Http\Controllers\Admin\PurchaseOrdersController::class, 'getDetail'])->name('admin.purchase-orders.detail');
        Route::post('/admin/purchase-orders/store', [App\Http\Controllers\Admin\PurchaseOrdersController::class, 'store'])->name('admin.purchase-orders.store');
        Route::post('/admin/purchase-orders/{id}/complete', [App\Http\Controllers\Admin\PurchaseOrdersController::class, 'complete'])->name('admin.purchase-orders.complete');
        Route::post('/admin/purchase-orders/{id}/cancel', [App\Http\Controllers\Admin\PurchaseOrdersController::class, 'cancel'])->name('admin.purchase-orders.cancel');
        Route::post('/admin/purchase-orders/{id}/toggle-payable', [App\Http\Controllers\Admin\PurchaseOrdersController::class, 'togglePayable'])->name('admin.purchase-orders.toggle-payable');
        Route::post('/admin/purchase-orders/{id}/toggle-invoiced', [App\Http\Controllers\Admin\PurchaseOrdersController::class, 'toggleInvoiced'])->name('admin.purchase-orders.toggle-invoiced');
        Route::post('/admin/purchase-orders/{id}/partial-payment', [App\Http\Controllers\Admin\PurchaseOrdersController::class, 'storePartialPayment'])->name('admin.purchase-orders.partial-payment');
        Route::delete('/admin/purchase-orders/partial-payment/{paymentId}', [App\Http\Controllers\Admin\PurchaseOrdersController::class, 'deletePartialPayment'])->name('admin.purchase-orders.delete-partial-payment');

        // Rutas para recibos de Tareas(admin web)
        Route::get('/admin/tasks', [TasksController::class, 'index'])->name('admin.tasks');
        Route::get('/admin/tasks/get', [TasksController::class, 'get'])->name('admin.tasks.get');
        Route::get('/admin/tasks/get-num-status', [TasksController::class, 'getNumStatus'])->name('admin.tasks.get-num-status');
        Route::get('/admin/tasks/collaborators', [TasksController::class, 'getCollaborators'])->name('admin.tasks.collaborators');

        Route::get('/admin/tasks/clients', [TasksController::class, 'getClients'])->name('admin.tasks.clients');

        Route::post('/admin/tasks/store', [TasksController::class, 'store'])->name('admin.tasks.store');

        Route::put('/admin/tasks/update', [TasksController::class, 'update'])->name('admin.tasks.update');
        Route::put('/admin/tasks/update-status', [TasksController::class, 'updateStatus'])->name('admin.tasks.update-status');
        Route::put('/admin/tasks/update-review', [TasksController::class, 'updateReview'])->name('admin.tasks.update-review');

        Route::delete('/admin/tasks/{id}', [TasksController::class, 'delete'])->name('admin.tasks.delete');
        Route::post('/admin/tasks/{id}/assign', [TasksController::class, 'assignUser'])->name('admin.tasks.assign');
        Route::post('/admin/tasks/{id}/unassign', [TasksController::class, 'unassignUser'])->name('admin.tasks.unassign');
        Route::put('/admin/tasks/{id}/activate', [TasksController::class, 'activate'])->name('admin.tasks.activate');
        Route::put('/admin/tasks/{id}/deactivate', [TasksController::class, 'deactivate'])->name('admin.tasks.deactivate');

        // Rutas para imágenes de tarea
        Route::post('/admin/tasks/{id}/upload-image', [TasksController::class, 'uploadImage'])->name('admin.tasks.upload-image');
        Route::delete('/admin/tasks/{id}/delete-main-image', [TasksController::class, 'deleteMainImage'])->name('admin.tasks.delete-main-image');
        Route::delete('/admin/tasks/delete-alt-image/{imageId}', [TasksController::class, 'deleteAltImage'])->name('admin.tasks.delete-alt-image');

        // Rutas para productos de tarea
        Route::get('/admin/tasks/products', [TasksController::class, 'getProducts'])->name('admin.tasks.products');
        Route::get('/admin/tasks/{taskId}/products', [TasksController::class, 'getTaskProducts'])->name('admin.tasks.task-products');
        Route::post('/admin/tasks/{taskId}/products', [TasksController::class, 'addTaskProduct'])->name('admin.tasks.add-product');
        Route::put('/admin/tasks/{taskId}/products/{taskProductId}', [TasksController::class, 'updateTaskProduct'])->name('admin.tasks.update-product');
        Route::delete('/admin/tasks/{taskId}/products/{taskProductId}', [TasksController::class, 'removeTaskProduct'])->name('admin.tasks.remove-product');
        Route::get('/admin/tasks/{taskId}/products-for-receipt', [TasksController::class, 'getUsedProductsForReceipt'])->name('admin.tasks.products-for-receipt');
        Route::get('/admin/tasks-with-pending-products', [TasksController::class, 'getTasksWithPendingProducts'])->name('admin.tasks.with-pending-products');

        // Rutas para Productos (CRUD admin)
        Route::get('/admin/products', [\App\Http\Controllers\Admin\ProductsController::class, 'index'])->name('admin.products');
        Route::get('/admin/products/get', [\App\Http\Controllers\Admin\ProductsController::class, 'get'])->name('admin.products.get');
        Route::get('/admin/products/categories', [\App\Http\Controllers\Admin\ProductsController::class, 'getCategories'])->name('admin.products.categories');
        Route::post('/admin/products/store', [\App\Http\Controllers\Admin\ProductsController::class, 'store'])->name('admin.products.store');
        Route::put('/admin/products/update', [\App\Http\Controllers\Admin\ProductsController::class, 'update'])->name('admin.products.update');
        Route::put('/admin/products/{id}/activate', [\App\Http\Controllers\Admin\ProductsController::class, 'activate'])->name('admin.products.activate');
        Route::put('/admin/products/{id}/deactivate', [\App\Http\Controllers\Admin\ProductsController::class, 'deactivate'])->name('admin.products.deactivate');
        Route::put('/admin/products/{id}/stock', [\App\Http\Controllers\Admin\ProductsController::class, 'updateStock'])->name('admin.products.stock');
        Route::delete('/admin/products/{id}', [\App\Http\Controllers\Admin\ProductsController::class, 'delete'])->name('admin.products.delete');
        // Rutas de imágenes de productos
        Route::post('/admin/products/{id}/upload-image', [\App\Http\Controllers\Admin\ProductsController::class, 'uploadImage'])->name('admin.products.upload-image');
        Route::delete('/admin/products/{id}/delete-main-image', [\App\Http\Controllers\Admin\ProductsController::class, 'deleteMainImage'])->name('admin.products.delete-main-image');
        Route::delete('/admin/products/delete-alt-image/{imageId}', [\App\Http\Controllers\Admin\ProductsController::class, 'deleteAltImage'])->name('admin.products.delete-alt-image');
        // Rutas de importación masiva de productos
        Route::get('/admin/products/import', [\App\Http\Controllers\Admin\ProductImportController::class, 'index'])->name('admin.products.import');
        Route::get('/admin/products/import/template', [\App\Http\Controllers\Admin\ProductImportController::class, 'downloadTemplate'])->name('admin.products.import.template');
        Route::post('/admin/products/import/preview', [\App\Http\Controllers\Admin\ProductImportController::class, 'preview'])->name('admin.products.import.preview');
        Route::post('/admin/products/import/execute', [\App\Http\Controllers\Admin\ProductImportController::class, 'import'])->name('admin.products.import.execute');
        Route::get('/admin/products/categories', [\App\Http\Controllers\Admin\ProductImportController::class, 'getCategories'])->name('admin.products.categories');

        // Rutas para Categorías (CRUD admin)
        Route::get('/admin/categories', [\App\Http\Controllers\Admin\CategoriesController::class, 'index'])->name('admin.categories');
        Route::get('/admin/categories/get', [\App\Http\Controllers\Admin\CategoriesController::class, 'get'])->name('admin.categories.get');
        Route::post('/admin/categories/store', [\App\Http\Controllers\Admin\CategoriesController::class, 'store'])->name('admin.categories.store');
        Route::put('/admin/categories/update', [\App\Http\Controllers\Admin\CategoriesController::class, 'update'])->name('admin.categories.update');
        Route::put('/admin/categories/{id}/activate', [\App\Http\Controllers\Admin\CategoriesController::class, 'activate'])->name('admin.categories.activate');
        Route::put('/admin/categories/{id}/deactivate', [\App\Http\Controllers\Admin\CategoriesController::class, 'deactivate'])->name('admin.categories.deactivate');
        Route::delete('/admin/categories/{id}', [\App\Http\Controllers\Admin\CategoriesController::class, 'delete'])->name('admin.categories.delete');

        // Rutas para Servicios (CRUD admin)
        Route::get('/admin/services', [\App\Http\Controllers\Admin\ServicesController::class, 'index'])->name('admin.services');
        Route::get('/admin/services/get', [\App\Http\Controllers\Admin\ServicesController::class, 'get'])->name('admin.services.get');
        Route::post('/admin/services/store', [\App\Http\Controllers\Admin\ServicesController::class, 'store'])->name('admin.services.store');
        Route::put('/admin/services/update', [\App\Http\Controllers\Admin\ServicesController::class, 'update'])->name('admin.services.update');
        Route::put('/admin/services/{id}/activate', [\App\Http\Controllers\Admin\ServicesController::class, 'activate'])->name('admin.services.activate');
        Route::put('/admin/services/{id}/deactivate', [\App\Http\Controllers\Admin\ServicesController::class, 'deactivate'])->name('admin.services.deactivate');
        Route::delete('/admin/services/{id}', [\App\Http\Controllers\Admin\ServicesController::class, 'delete'])->name('admin.services.delete');

        // Rutas para Equipos (CRUD admin)
        Route::get('/admin/equipments', [\App\Http\Controllers\Admin\EquipmentsController::class, 'index'])->name('admin.equipments');
        Route::get('/admin/equipments/get', [\App\Http\Controllers\Admin\EquipmentsController::class, 'get'])->name('admin.equipments.get');
        Route::post('/admin/equipments/store', [\App\Http\Controllers\Admin\EquipmentsController::class, 'store'])->name('admin.equipments.store');
        Route::put('/admin/equipments/update', [\App\Http\Controllers\Admin\EquipmentsController::class, 'update'])->name('admin.equipments.update');
        Route::put('/admin/equipments/{id}/activate', [\App\Http\Controllers\Admin\EquipmentsController::class, 'activate'])->name('admin.equipments.activate');
        Route::put('/admin/equipments/{id}/deactivate', [\App\Http\Controllers\Admin\EquipmentsController::class, 'deactivate'])->name('admin.equipments.deactivate');
        Route::delete('/admin/equipments/{id}', [\App\Http\Controllers\Admin\EquipmentsController::class, 'delete'])->name('admin.equipments.delete');
        Route::post('/admin/equipments/{id}/upload-image', [\App\Http\Controllers\Admin\EquipmentsController::class, 'uploadImage'])->name('admin.equipments.upload-image');
        Route::delete('/admin/equipments/delete-image/{imageId}', [\App\Http\Controllers\Admin\EquipmentsController::class, 'deleteImage'])->name('admin.equipments.delete-image');

        // Users (Admins y Colaboradores de la tienda)
        Route::get('/admin/users', [AdminUsersController::class, 'index'])->name('admin.users');
        Route::get('/admin/users/shop-slug', [AdminUsersController::class, 'getShopSlug']);
        Route::get('/admin/users/counters', [AdminUsersController::class, 'getCounters']);
        Route::get('/admin/users/verify-email', [AdminUsersController::class, 'verifyEmail']);
        // Administradores
        Route::get('/admin/users/administrators', [AdminUsersController::class, 'getAdministrators']);
        Route::post('/admin/users/administrators/store', [AdminUsersController::class, 'storeAdministrator']);
        Route::put('/admin/users/administrators/update', [AdminUsersController::class, 'updateAdministrator']);
        Route::put('/admin/users/administrators/{id}/activate', [AdminUsersController::class, 'activateAdministrator']);
        Route::put('/admin/users/administrators/{id}/deactivate', [AdminUsersController::class, 'deactivateAdministrator']);
        Route::put('/admin/users/administrators/{id}/toggle-limited', [AdminUsersController::class, 'toggleLimitedAdministrator']);
        // Colaboradores
        Route::get('/admin/users/collaborators', [AdminUsersController::class, 'getCollaborators']);
        Route::post('/admin/users/collaborators/store', [AdminUsersController::class, 'storeCollaborator']);
        Route::put('/admin/users/collaborators/update', [AdminUsersController::class, 'updateCollaborator']);
        Route::put('/admin/users/collaborators/{id}/activate', [AdminUsersController::class, 'activateCollaborator']);
        Route::put('/admin/users/collaborators/{id}/deactivate', [AdminUsersController::class, 'deactivateCollaborator']);

        // Gastos
        Route::get('/admin/gastos', [\App\Http\Controllers\Admin\GastosController::class, 'index'])->name('admin.gastos');
        Route::get('/admin/gastos/get', [\App\Http\Controllers\Admin\GastosController::class, 'getExpenses']);
        Route::get('/admin/gastos/counters', [\App\Http\Controllers\Admin\GastosController::class, 'getCounters']);
        Route::post('/admin/gastos/store', [\App\Http\Controllers\Admin\GastosController::class, 'store']);
        Route::put('/admin/gastos/{id}', [\App\Http\Controllers\Admin\GastosController::class, 'update']);
        Route::patch('/admin/gastos/{id}/status', [\App\Http\Controllers\Admin\GastosController::class, 'updateStatus']);
        Route::patch('/admin/gastos/{id}/total', [\App\Http\Controllers\Admin\GastosController::class, 'updateTotal']);
        Route::patch('/admin/gastos/{id}/fecha', [\App\Http\Controllers\Admin\GastosController::class, 'updateFecha']);
        Route::patch('/admin/gastos/{id}/toggle-active', [\App\Http\Controllers\Admin\GastosController::class, 'toggleActive']);
        Route::patch('/admin/gastos/{id}/toggle-facturado', [\App\Http\Controllers\Admin\GastosController::class, 'toggleFacturado']);
        Route::get('/admin/gastos/{id}/logs', [\App\Http\Controllers\Admin\GastosController::class, 'getLogs']);
        Route::get('/admin/gastos/{id}/attachments', [\App\Http\Controllers\Admin\GastosController::class, 'getAttachments']);
        Route::post('/admin/gastos/{id}/attachments', [\App\Http\Controllers\Admin\GastosController::class, 'uploadAttachment']);
        Route::delete('/admin/gastos/attachments/{id}', [\App\Http\Controllers\Admin\GastosController::class, 'deleteAttachment']);

        // Reportes (Solo Admin Full - limited = 0)
        Route::middleware(['full.admin'])->group(function () {
            Route::get('/admin/reports', [\App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('admin.reports');
            Route::get('/admin/reports/ventas-resumen', [\App\Http\Controllers\Admin\ReportsController::class, 'ventasResumen']);
            Route::get('/admin/reports/ventas-utilidad', [\App\Http\Controllers\Admin\ReportsController::class, 'ventasUtilidad']);
            Route::get('/admin/reports/inventario', [\App\Http\Controllers\Admin\ReportsController::class, 'inventario']);
            Route::get('/admin/reports/ingresos-egresos', [\App\Http\Controllers\Admin\ReportsController::class, 'ingresosEgresos']);
            Route::get('/admin/reports/clientes-adeudos', [\App\Http\Controllers\Admin\ReportsController::class, 'clientesAdeudos']);
            Route::get('/admin/reports/top-productos', [\App\Http\Controllers\Admin\ReportsController::class, 'topProductos']);
            Route::get('/admin/reports/categorias', [\App\Http\Controllers\Admin\ReportsController::class, 'getCategorias']);
            Route::get('/admin/reports/diferencias-mensual', [\App\Http\Controllers\Admin\ReportsController::class, 'diferenciasMensual']);
            Route::get('/admin/reports/ventas-periodo', [\App\Http\Controllers\Admin\ReportsController::class, 'ventasPeriodo']);
        });
    }); //./Routes Middleware admin
});#./Middlware AUTH