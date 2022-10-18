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

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


//Route::get('/test','App\Http\Controllers\ReceiptController@test');

Route::get('/print-receipt-rent', 'App\Http\Controllers\ReceiptController@printReceiptRent');

Route::get('/print-purchase-order', 'App\Http\Controllers\PurchaseOrderController@printPurchaseOrder');


Auth::routes();

Route::group(['middleware' => 'auth'], function () {
    Route::get('/user/passwords/reset', [App\Http\Controllers\HomeController::class,'passwordReset'])->name('password.reset');
    Route::post('/user/passwords/update', [App\Http\Controllers\HomeController::class,'updatePassword'])->name('password.update');

});#./Middlware AUTH