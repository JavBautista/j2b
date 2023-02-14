<?php

use Illuminate\Support\Facades\Route;

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




//Route::get('/test','App\Http\Controllers\ReceiptController@test');

Route::get('/print-receipt-rent', 'App\Http\Controllers\ReceiptController@printReceiptRent');

Route::get('/print-purchase-order', 'App\Http\Controllers\PurchaseOrderController@printPurchaseOrder');


Auth::routes();

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

    });//./Routes Middleware superadmin

    Route::group(['middleware' => 'admin'], function () {
        Route::get('/admin', function(){
            return view('admin.index');
        });
    });//./Routes Middleware admin


    Route::group(['middleware' => 'client'], function () {
        Route::get('/client', function(){
            return view('client.index');
        });
    });//./Routes Middleware client

});#./Middlware AUTH