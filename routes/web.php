<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;


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

Route::get('/', [App\Http\Controllers\HomeController::class, 'index']);
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index']);

Route::get('/pre-registro', [App\Http\Controllers\RequestsJ2bController::class, 'j2bSolicitar'])->name('solicitud');

Route::post('/pre-registro/create', [App\Http\Controllers\RequestsJ2bController::class, 'store'])->name('solicitud.store');

Route::get('/pre-registro/confirm/{xtoken}', [App\Http\Controllers\RequestsJ2bController::class, 'confirm'])->name('solicitud.confirm');

Route::get('/pre-registro/completar', [App\Http\Controllers\RequestsJ2bController::class, 'completar'])->name('solicitud.completar')->middleware('check.token');

Route::get('/pre-registro/error', [App\Http\Controllers\RequestsJ2bController::class, 'error'])->name('solicitud.error');

Route::post('/registro/create', [App\Http\Controllers\RequestsJ2bController::class, 'store'])->name('solicitud.store');



//Route::get('/test','App\Http\Controllers\ReceiptController@test');

Route::get('/print-receipt-rent', 'App\Http\Controllers\ReceiptController@printReceiptRent');

Route::get('/print-purchase-order', 'App\Http\Controllers\PurchaseOrderController@printPurchaseOrder');

Route::get('/print-purchase-order', 'App\Http\Controllers\PurchaseOrderController@printPurchaseOrder');


Auth::routes([
    'login'    => true,
    'logout'   => true,
    'register' => true,
    'reset'    => false,
    'confirm'  => false,
    'verify'   => false,
]);

Route::group(['middleware' => 'auth'], function () {
    Route::get('/user/passwords/reset', [App\Http\Controllers\HomeController::class,'passwordReset'])->name('password.reset');
    Route::post('/user/passwords/update', [App\Http\Controllers\HomeController::class,'updatePassword'])->name('password.update');

    Route::group(['middleware' => 'superadmin'], function () {
        //Index
        Route::get('/superadmin', [App\Http\Controllers\SuperadminPagesController::class,'index'])->name('superadmin.index');
        //Shops
        Route::get('/superadmin/shops', [App\Http\Controllers\SuperadminPagesController::class,'shops'])->name('superadmin.shops');

        Route::get('/superadmin/shops/get', [App\Http\Controllers\Superadmin\ShopsController::class,'get']);
        Route::post('/superadmin/shops/store', [App\Http\Controllers\Superadmin\ShopsController::class,'store']);
        Route::put('/superadmin/shops/update', [App\Http\Controllers\Superadmin\ShopsController::class,'update']);
        Route::put('/superadmin/shops/active', [App\Http\Controllers\Superadmin\ShopsController::class,'active']);
        Route::put('/superadmin/shops/deactive', [App\Http\Controllers\Superadmin\ShopsController::class,'deactive']);
        Route::post('/superadmin/shops/upload-logo', [App\Http\Controllers\Superadmin\ShopsController::class,'uploadLogo']);

        //Plans
        Route::get('/superadmin/plans', [App\Http\Controllers\SuperadminPagesController::class,'plans'])->name('superadmin.plans');
        Route::get('/superadmin/plans/get', [App\Http\Controllers\Superadmin\PlansController::class,'get']);
        Route::post('/superadmin/plans/store', [App\Http\Controllers\Superadmin\PlansController::class,'store']);
        Route::put('/superadmin/plans/update', [App\Http\Controllers\Superadmin\PlansController::class,'update']);
        Route::put('/superadmin/plans/active', [App\Http\Controllers\Superadmin\PlansController::class,'active']);
        Route::put('/superadmin/plans/deactive', [App\Http\Controllers\Superadmin\PlansController::class,'deactive']);

        //Users
        Route::get('/superadmin/users', [App\Http\Controllers\SuperadminPagesController::class,'users'])->name('superadmin.users');
        Route::get('/superadmin/users/get', [App\Http\Controllers\Superadmin\UsersController::class,'get']);
        Route::post('/superadmin/users/store', [App\Http\Controllers\Superadmin\UsersController::class,'store']);
        Route::put('/superadmin/users/update', [App\Http\Controllers\Superadmin\UsersController::class,'updateInfo']);
        Route::put('/superadmin/users/active', [App\Http\Controllers\Superadmin\UsersController::class,'updateToActive']);
        Route::put('/superadmin/users/inactive', [App\Http\Controllers\Superadmin\UsersController::class,'updateToInactive']);

        //UPLOAD APK
        Route::get('/superadmin/upload', [App\Http\Controllers\SuperadminPagesController::class,'uploadApk'])->name('superadmin.upload_apk');
        Route::post('/superadmin/upload-apk/store', [App\Http\Controllers\Superadmin\UsersController::class,'storeApk'])->name('superadmin.store.apk');
    });//./Routes Middleware superadmin

    /*Route::group(['middleware' => 'admin'], function () {
        Route::get('/admin', function(){
            return view('admin.index');
        });
    });//./Routes Middleware admin
    */


    //Route::group(['middleware' => 'client'], function () {
    Route::group(['middleware' => 'admin'], function () {
        //Index
        Route::get('/client', [App\Http\Controllers\ClientPagesController::class,'index'])->name('client.index');
        //Shops
        Route::get('/client/shop', [App\Http\Controllers\ClientPagesController::class,'shop'])->name('client.shop');
        Route::get('/client/shop/edit', [App\Http\Controllers\ClientPagesController::class,'shopEdit'])->name('client.shop.edit');
        Route::put('/client/shop/update', [App\Http\Controllers\ShopController::class,'updateWeb'])->name('client.shop.update');

        Route::get('/client/download', [App\Http\Controllers\ClientPagesController::class,'download'])->name('client.download');

        /*Route::get('/client/download-apk/{filename}', function ($filename) {
            // Verifica si el archivo existe en la carpeta de almacenamiento público
            if (Storage::disk('public')->exists('apk/' . $filename)) {
                // Si el archivo existe, devuelve una respuesta de descarga
                return Storage::disk('public')->download('apk/' . $filename);
            } else {
                // Si el archivo no existe, devuelve una respuesta 404
                abort(404);
            }
        })->name('download.apk');*/
        Route::get('/client/download-apk/{filename}', function ($filename) {
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


        Route::get('/client/configurations', [App\Http\Controllers\ClientPagesController::class,'configurations'])->name('client.configurations');

        Route::get('/client/configurations/extra-fields-shop', [App\Http\Controllers\ExtraFieldsShopController::class,'index'])->name('client.configurations.extra_fields');

        Route::get('/client/configurations/extra-fields-shop/create', [App\Http\Controllers\ExtraFieldsShopController::class,'create'])->name('client.configurations.extra_fields.create');

        Route::get('/client/configurations/extra-fields-shop/edit/{id}', [App\Http\Controllers\ExtraFieldsShopController::class,'edit'])->name('client.configurations.extra_fields.edit');

        Route::post('/client/configurations/extra-fields/store', [App\Http\Controllers\ExtraFieldsShopController::class,'store'])->name('client.configurations.extra-fields.store');

        Route::put('/client/configurations/extra-fields-shop/{id}/toggle', [App\Http\Controllers\ExtraFieldsShopController::class,'toggleShow'])->name('client.configurations.extra_fields.toggle');

        Route::delete('/client/configurations/extra-fields/{id}', [App\Http\Controllers\ExtraFieldsShopController::class, 'destroy'])->name('client.configurations.extra_fields.destroy');

        Route::put('/client/configurations/extra-fields-shop/update/{id}', [App\Http\Controllers\ExtraFieldsShopController::class,'update'])->name('client.configurations.extra-fields.update');






    });//./Routes Middleware client

});#./Middlware AUTH