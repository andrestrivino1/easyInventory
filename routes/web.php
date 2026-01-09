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

// IMPORTS MODULE
use App\Http\Controllers\ImportController;
Route::middleware(['auth'])->group(function () {
    // ADMIN: see all imports
    Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
    // PROVIDER: see own imports
    Route::get('my-imports', [ImportController::class, 'providerIndex'])->name('imports.provider-index');
    Route::get('imports/create', [ImportController::class, 'create'])->name('imports.create');
    Route::post('imports', [ImportController::class, 'store'])->name('imports.store');
    Route::get('imports/{id}/edit', [ImportController::class, 'edit'])->name('imports.edit');
    Route::put('imports/{id}', [ImportController::class, 'update'])->name('imports.update');
    Route::get('imports/{id}/view/{file}', [ImportController::class, 'viewFile'])->name('imports.view');
    Route::get('imports/{id}/download/{file}', [ImportController::class, 'downloadFile'])->name('imports.download');
    Route::get('imports/{id}/report', [ImportController::class, 'report'])->name('imports.report');
});
Route::resource('salidas', App\Http\Controllers\SalidaController::class);
Route::resource('users', \App\Http\Controllers\UserController::class);
Route::resource('drivers', App\Http\Controllers\DriverController::class);
Route::resource('containers', App\Http\Controllers\ContainerController::class);
Auth::routes();
Route::get('transfer-orders/{transferOrder}/export', [TransferOrderController::class, 'export'])->name('transfer-orders.export');
Route::get('transfer-orders/{transferOrder}/print', [TransferOrderController::class, 'print'])->name('transfer-orders.print');
Route::get('salidas/{salida}/export', [App\Http\Controllers\SalidaController::class, 'export'])->name('salidas.export');
Route::get('salidas/{salida}/print', [App\Http\Controllers\SalidaController::class, 'print'])->name('salidas.print');
Route::get('transfer-orders/{transferOrder}/confirm', [TransferOrderController::class, 'showConfirmForm'])->name('transfer-orders.confirm');
Route::post('transfer-orders/{transferOrder}/confirm', [TransferOrderController::class, 'confirmReceived'])->name('transfer-orders.confirm.store');
Route::get('transfer-orders/get-products/{warehouseId}', [TransferOrderController::class, 'getProductsForWarehouse'])->name('transfer-orders.get-products');
Route::get('salidas/get-products/{warehouseId}', [App\Http\Controllers\SalidaController::class, 'getProductsForWarehouse'])->name('salidas.get-products');
Route::get('containers/{container}/export', [App\Http\Controllers\ContainerController::class, 'export'])->name('containers.export');
Route::get('containers/{container}/print', [App\Http\Controllers\ContainerController::class, 'print'])->name('containers.print');
Route::get('stock', [App\Http\Controllers\StockController::class, 'index'])->name('stock.index');
Route::get('stock/export-pdf', [App\Http\Controllers\StockController::class, 'exportPdf'])->name('stock.export-pdf');
Route::get('stock/export-excel', [App\Http\Controllers\StockController::class, 'exportExcel'])->name('stock.export-excel');
Route::get('traceability', [App\Http\Controllers\TraceabilityController::class, 'index'])->name('traceability.index');
Route::get('traceability/export-pdf', [App\Http\Controllers\TraceabilityController::class, 'exportPdf'])->name('traceability.export-pdf');
Route::get('traceability/export-excel', [App\Http\Controllers\TraceabilityController::class, 'exportExcel'])->name('traceability.export-excel');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
