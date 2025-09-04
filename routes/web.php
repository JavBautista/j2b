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
use App\Http\Controllers\Superadmin\ShopsController; 
use  App\Http\Controllers\Superadmin\PlansController;
use App\Http\Controllers\Superadmin\UsersController;
use App\Http\Controllers\Admin\ClientsController;


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
Route::get('/', [HomeController::class, 'index']);
Route::get('/pre-registro', [RequestsJ2bController::class, 'j2bSolicitar'])->name('solicitud');
Route::post('/pre-registro/create', [RequestsJ2bController::class, 'store'])->name('solicitud.store');
Route::get('/pre-registro/confirm/{xtoken}', [RequestsJ2bController::class, 'confirm'])->name('solicitud.confirm');
Route::get('/pre-registro/completar', [RequestsJ2bController::class, 'completar'])->name('solicitud.completar')->middleware('check.token');
Route::get('/pre-registro/error', [RequestsJ2bController::class, 'error'])->name('solicitud.error');
Route::post('/registro/create', [RequestsJ2bController::class, 'store'])->name('X.solicitud.store');
Route::get('/print-receipt-rent', [ReceiptController::class, 'printReceiptRent']);
Route::get('/print-purchase-order', [PurchaseOrderController::class,'printPurchaseOrder']);
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
    Route::get('/user/passwords/reset', [HomeController::class,'passwordReset'])->name('password.reset');
    Route::post('/user/passwords/update', [HomeController::class,'updatePassword'])->name('password.update');

    //====================RUTAS AUTH/SUPER ADMIN DE TODO====================
    Route::group(['middleware' => 'superadmin'], function () {
        //Index
        Route::get('/superadmin', [SuperadminPagesController::class,'index'])->name('superadmin.index');
        //Shops
        Route::get('/superadmin/shops', [SuperadminPagesController::class,'shops'])->name('superadmin.shops');

        Route::get('/superadmin/shops/get', [ShopsController::class,'get']);
        Route::post('/superadmin/shops/store', [ShopsController::class,'store']);
        Route::put('/superadmin/shops/update', [ShopsController::class,'update']);
        Route::put('/superadmin/shops/active', [ShopsController::class,'active']);
        Route::put('/superadmin/shops/deactive', [ShopsController::class,'deactive']);
        Route::post('/superadmin/shops/upload-logo', [ShopsController::class,'uploadLogo']);
        Route::put('/superadmin/shops/update-cutoff', [ShopsController::class,'updateCutoff']);

        //Plans
        Route::get('/superadmin/plans', [SuperadminPagesController::class,'plans'])->name('superadmin.plans');
        Route::get('/superadmin/plans/get', [PlansController::class,'get']);
        Route::post('/superadmin/plans/store', [PlansController::class,'store']);
        Route::put('/superadmin/plans/update', [PlansController::class,'update']);
        Route::put('/superadmin/plans/active', [PlansController::class,'active']);
        Route::put('/superadmin/plans/deactive', [PlansController::class,'deactive']);

        //Users
        Route::get('/superadmin/users', [SuperadminPagesController::class,'users'])->name('superadmin.users');
        Route::get('/superadmin/users/get', [UsersController::class,'get']);
        Route::post('/superadmin/users/store', [UsersController::class,'store']);
        Route::put('/superadmin/users/update', [UsersController::class,'updateInfo']);
        Route::put('/superadmin/users/active', [UsersController::class,'updateToActive']);
        Route::put('/superadmin/users/inactive', [UsersController::class,'updateToInactive']);

        //UPLOAD APK
        Route::get('/superadmin/upload', [SuperadminPagesController::class,'uploadApk'])->name('superadmin.upload_apk');
        Route::post('/superadmin/upload-apk/store', [UsersController::class,'storeApk'])->name('superadmin.store.apk');


        //Users
        Route::get('/superadmin/pre-registers', [SuperadminPagesController::class,'preRegisters'])->name('superadmin.pre-registers');
        Route::get('/superadmin/pre-registers/get', [App\Http\Controllers\Superadmin\RequestsJ2bController::class,'getRegisters']);
        Route::put('/superadmin/pre-registers/delete', [App\Http\Controllers\Superadmin\RequestsJ2bController::class,'destroy']);


    });//./Routes Middleware superadmin

    //====================RUTAS AUTH/ADMIN DE TIENDAS====================
    Route::group(['middleware' => ['admin', 'web.access']], function () {
        //Index
        Route::get('/admin', [AdminPagesController::class,'index'])->name('admin.index');
        
        // 🔥 TEMPORAL: Crear servicio de prueba para testing FCM
        Route::post('/admin/test-create-service', [AdminPagesController::class,'testCreateService'])->name('admin.test.create.service');
        //Shops
        Route::get('/admin/shop', [AdminPagesController::class,'shop'])->name('admin.shop');
        Route::get('/admin/shop/edit', [AdminPagesController::class,'shopEdit'])->name('admin.shop.edit');
        Route::put('/admin/shop/update', [ShopController::class,'updateWeb'])->name('admin.shop.update');
        Route::put('/admin/shop/{shop}/update-signature', [ShopController::class,'updateSignature'])->name('admin.shop.update-signature');
        Route::delete('/admin/shop/{shop}/delete-signature', [ShopController::class,'deleteSignature'])->name('admin.shop.delete-signature');

        Route::get('/admin/download', [AdminPagesController::class,'download'])->name('admin.download');
       
        Route::get('/admin/download-apk/{filename}', function ($filename) {
            // Verifica si el archivo existe en la carpeta de almacenamiento público
            if (Storage::disk('public')->exists('apk/' . $filename)) {
                // Si el archivo existe, devuelve una respuesta de descarga con los encabezados correctos
                return response()->download(storage_path('app/public/apk/' . $filename), $filename, [
                    'Content-Type' => 'application/vnd.android.package-archive',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]);
            } else {
                // Si el archivo no existe, devuelve una respuesta 404
                abort(404);
            }
        })->name('download.apk');


        Route::get('/admin/configurations', [AdminPagesController::class,'configurations'])->name('admin.configurations');

        Route::get('/admin/configurations/extra-fields-shop', [ExtraFieldsShopController::class,'index'])->name('admin.configurations.extra_fields');

        Route::get('/admin/configurations/extra-fields-shop/create', [ExtraFieldsShopController::class,'create'])->name('admin.configurations.extra_fields.create');

        Route::get('/admin/configurations/extra-fields-shop/edit/{id}', [ExtraFieldsShopController::class,'edit'])->name('admin.configurations.extra_fields.edit');

        Route::post('/admin/configurations/extra-fields/store', [ExtraFieldsShopController::class,'store'])->name('admin.configurations.extra-fields.store');

        Route::put('/admin/configurations/extra-fields-shop/{id}/toggle', [ExtraFieldsShopController::class,'toggleShow'])->name('admin.configurations.extra_fields.toggle');

        Route::delete('/admin/configurations/extra-fields/{id}', [ExtraFieldsShopController::class, 'destroy'])->name('admin.configurations.extra_fields.destroy');

        Route::put('/admin/configurations/extra-fields-shop/update/{id}', [ExtraFieldsShopController::class,'update'])->name('admin.configurations.extra-fields.update');

        Route::get('/admin/contracts', [AdminPagesController::class,'contracts'])->name('admin.contracts');
        
        // Rutas para plantillas de contratos
        Route::resource('contract-templates', ContractTemplateController::class);
        
        // Rutas para contratos
        Route::resource('contracts', ContractController::class);
        Route::get('contracts/{contract}/generate-pdf', [ContractController::class, 'generatePdf'])->name('contracts.generate-pdf');
        
        // Rutas para firmas de contratos
        Route::post('contracts/save-signature', [ContractController::class, 'saveSignature'])->name('contracts.save-signature');
        Route::post('contracts/update-signature', [ContractController::class, 'updateSignature'])->name('contracts.update-signature');
        Route::post('contracts/delete-signature', [ContractController::class, 'deleteSignature'])->name('contracts.delete-signature');


        Route::get('/admin/clients', [AdminPagesController::class,'clients'])->name('admin.clients');
        
        // Rutas AJAX para CRUD de clientes (admin web - separadas de Ionic)
        Route::get('/admin/clients/get', [ClientsController::class,'index'])->name('admin.clients.get');
        Route::post('/admin/clients/store', [ClientsController::class,'store'])->name('admin.clients.store');
        Route::put('/admin/clients/update', [ClientsController::class,'update'])->name('admin.clients.update');
        Route::put('/admin/clients/inactive', [ClientsController::class,'inactive'])->name('admin.clients.inactive');
        Route::put('/admin/clients/active', [ClientsController::class,'active'])->name('admin.clients.active');
        
        // Rutas para contratos desde clientes
        Route::get('/admin/clients/{client}/assign-contract', [ClientsController::class,'assignContractPage'])->name('admin.clients.assign-contract');
        Route::post('/admin/clients/{client}/create-contract', [ClientsController::class,'createContract'])->name('admin.clients.create-contract');
        Route::post('/admin/clients/{client}/contract-preview', [ClientsController::class,'getContractPreview'])->name('admin.clients.contract-preview');
        Route::get('/admin/clients/{client}/contracts', [ClientsController::class,'clientContracts'])->name('admin.clients.contracts');
        Route::get('/admin/clients/{client}/contracts/{contract}/edit', [ClientsController::class,'editContract'])->name('admin.clients.edit-contract');
        Route::put('/admin/clients/{client}/contracts/{contract}', [ClientsController::class,'updateContract'])->name('admin.clients.update-contract');
        Route::get('/admin/contracts/{contract}/view', [ClientsController::class,'viewContract'])->name('admin.contracts.view');
        Route::delete('/admin/contracts/{contract}', [ClientsController::class,'deleteContract'])->name('admin.contracts.delete');

        // Rutas AJAX para CRUD de direcciones de clientes (admin web - separadas de Ionic)
        Route::get('/admin/client-addresses/get', [App\Http\Controllers\Admin\ClientAddressController::class,'index'])->name('admin.client-addresses.get');
        Route::post('/admin/client-addresses/store', [App\Http\Controllers\Admin\ClientAddressController::class,'store'])->name('admin.client-addresses.store');
        Route::put('/admin/client-addresses/update', [App\Http\Controllers\Admin\ClientAddressController::class,'update'])->name('admin.client-addresses.update');
        Route::put('/admin/client-addresses/inactive', [App\Http\Controllers\Admin\ClientAddressController::class,'inactive'])->name('admin.client-addresses.inactive');
        Route::put('/admin/client-addresses/active', [App\Http\Controllers\Admin\ClientAddressController::class,'active'])->name('admin.client-addresses.active');
        Route::post('/admin/client-addresses/upload-location-image', [App\Http\Controllers\Admin\ClientAddressController::class,'uploadLocationImage'])->name('admin.client-addresses.upload-image');
        Route::delete('/admin/client-addresses/delete-location-image', [App\Http\Controllers\Admin\ClientAddressController::class,'deleteLocationImage'])->name('admin.client-addresses.delete-image');

        // Rutas para recibos de clientes (admin web)
        Route::get('/admin/clients/{client}/receipts', [App\Http\Controllers\Admin\ReceiptsController::class,'index'])->name('admin.clients.receipts');
        Route::get('/admin/clients/receipts/get', [App\Http\Controllers\Admin\ReceiptsController::class,'getReceipts'])->name('admin.clients.receipts.get');






    });//./Routes Middleware admin

});#./Middlware AUTH