<?php

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

/*CLIENTES*/
Route::resource('client','App\Http\Controllers\ClientController');
Route::post('client/edit/update','App\Http\Controllers\ClientController@update');
Route::post('client/delete','App\Http\Controllers\ClientController@inactive');

/*PROVEEDORES*/
Route::get('supplier','App\Http\Controllers\SupplierController@index');
Route::post('supplier/store','App\Http\Controllers\SupplierController@store');
Route::post('supplier/update','App\Http\Controllers\SupplierController@update');
Route::post('supplier/delete','App\Http\Controllers\SupplierController@inactive');
Route::post('supplier/inactive','App\Http\Controllers\SupplierController@inactive');
Route::post('supplier/active','App\Http\Controllers\SupplierController@active');

/*RENTAS*/
Route::get('rents/{client_id}','App\Http\Controllers\RentController@index');
Route::get('rent/get/{id}','App\Http\Controllers\RentController@getRentByID');

Route::get('rents/get/by-cutoff','App\Http\Controllers\RentController@getByCutoff');

Route::post('rent/store','App\Http\Controllers\RentController@store');
Route::post('rent/update','App\Http\Controllers\RentController@update');
Route::post('rent/delete','App\Http\Controllers\RentController@destroy');

Route::post('rent/store-detail','App\Http\Controllers\RentController@storeDetail');
Route::post('rent/update-detail','App\Http\Controllers\RentController@updateDetail');
Route::post('rent/delete-detail','App\Http\Controllers\RentController@destroyDetail');

/*RECIBOS*/
//Route::resource('receipt','App\Http\Controllers\ReceiptController');
Route::get('receipt/all','App\Http\Controllers\ReceiptController@getAll');
Route::get('receipt/{client_id}','App\Http\Controllers\ReceiptController@index');

Route::get('receipt/detail/{receipt_id}','App\Http\Controllers\ReceiptDetailController@getDetail');
Route::post('receipt/store','App\Http\Controllers\ReceiptController@store');
Route::post('receipt/edit/update-status','App\Http\Controllers\ReceiptController@updateStatus');
Route::post('receipt/edit/cancel','App\Http\Controllers\ReceiptController@cancel');
Route::post('receipt/edit/devolucion','App\Http\Controllers\ReceiptController@devolucion');

Route::post('receipt/edit/update-info','App\Http\Controllers\ReceiptController@updateInfo');
Route::post('receipt/delete','App\Http\Controllers\ReceiptController@delete');
    /*PRINT PDF*/
    Route::get('receipt/pdf/print-receipt-rent', 'App\Http\Controllers\ReceiptController@printReceiptRent');

Route::post('receipt/edit/update/quotation-to-sale','App\Http\Controllers\ReceiptController@updateQuotationToSale');

/*PartialPayments*/
Route::post('receipt/partial-payment/store','App\Http\Controllers\PartialPaymentsController@store');

/*PRODUCTOS*/
Route::resource('product','App\Http\Controllers\ProductController');
Route::post('product/edit/update','App\Http\Controllers\ProductController@update');
Route::post('product/edit/update-stock','App\Http\Controllers\ProductController@updateStock');
Route::post('product/delete','App\Http\Controllers\ProductController@inactive');

/*CATEGORIAS*/
Route::resource('category','App\Http\Controllers\CategoryController');
Route::get('/category/get/all','App\Http\Controllers\CategoryController@all');
Route::post('category/edit/update','App\Http\Controllers\CategoryController@update');
Route::post('category/delete','App\Http\Controllers\CategoryController@inactive');

/*SERVICIOS*/
Route::resource('service','App\Http\Controllers\ServiceController');
Route::post('service/edit/update','App\Http\Controllers\ServiceController@update');
Route::post('service/delete','App\Http\Controllers\ServiceController@inactive');


/*PLANS*/
Route::resource('plan','App\Http\Controllers\PlanController');

Route::get('/plan/get/all','App\Http\Controllers\PlanController@all');

/* SHOP */
Route::get('/shop/{shop_id}','App\Http\Controllers\ShopController@getShop');
Route::post('shop/update','App\Http\Controllers\ShopController@update');

/* PRUCHASE ORDER*/
Route::get('purchase-order/all','App\Http\Controllers\PurchaseOrderController@getAll');
Route::post('purchase-order/store','App\Http\Controllers\PurchaseOrderController@store');
Route::get('purchase-order/detail/{purchase_order_id}','App\Http\Controllers\PurchaseOrderDetailController@getDetail');

Route::post('purchase-order/edit/update-status','App\Http\Controllers\PurchaseOrderController@updateStatus');
Route::post('purchase-order/edit/update/complete-purchase-order','App\Http\Controllers\PurchaseOrderController@updateCompletePurchaseOrder');

Route::post('puchase-order/edit/cancel','App\Http\Controllers\PurchaseOrderController@cancel');

Route::post('purchase-order/partial-payment/store','App\Http\Controllers\PurchaseOrderPartialPaymentsController@store');



Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('login', '\App\Http\Controllers\AuthController@login');
    Route::post('signup', '\App\Http\Controllers\AuthController@signUp');

    Route::group([
      'middleware' => 'auth:api'
    ], function() {
        Route::get('logout', '\App\Http\Controllers\AuthController@logout');
        Route::get('user', '\App\Http\Controllers\AuthController@user');
        Route::post('user', '\App\Http\Controllers\AuthController@update');
    });
});

/*
Route::get('/articulos','App\Http\Controllers\ArticuloContorller@index');
Route::post('/articulos','App\Http\Controllers\ArticuloController@store');
Route::put('/articulos/{id}','App\Http\Controllers\ArticuloController@update');
Route::delete('/articulos/{id}','App\Http\Controllers\ArticuloController@destroy');
*/