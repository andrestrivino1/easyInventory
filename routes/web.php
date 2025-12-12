<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\TransferOrderController;
use Illuminate\Support\Facades\Auth;

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

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('home');
    }
    return view('auth.login');
});
Route::resource('products', ProductController::class);
Route::resource('warehouses', WarehouseController::class);
Route::resource('transfer-orders', TransferOrderController::class);
Route::resource('users', \App\Http\Controllers\UserController::class);
Route::resource('drivers', App\Http\Controllers\DriverController::class);
Auth::routes();
Route::get('transfer-orders/{transferOrder}/export', [TransferOrderController::class, 'export'])->name('transfer-orders.export');
Route::get('transfer-orders/{transferOrder}/print', [TransferOrderController::class, 'print'])->name('transfer-orders.print');
Route::post('transfer-orders/{transferOrder}/confirm', [TransferOrderController::class, 'confirmReceived'])->name('transfer-orders.confirm');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
