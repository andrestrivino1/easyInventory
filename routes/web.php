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

// Language switching - Available for all users including importers
Route::get('language/{locale}', [App\Http\Controllers\LanguageController::class, 'switchLanguage'])->name('language.switch');

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
    // IMPORT_VIEWER: see all imports (read-only)
    Route::get('imports-viewer', [ImportController::class, 'viewerIndex'])->name('imports.viewer-index');
    // PROVIDER: see own imports
    Route::get('my-imports', [ImportController::class, 'providerIndex'])->name('imports.provider-index');
    Route::get('imports/create', [ImportController::class, 'create'])->name('imports.create');
    Route::post('imports', [ImportController::class, 'store'])->name('imports.store');
    Route::get('imports/{id}/edit', [ImportController::class, 'edit'])->name('imports.edit');
    Route::put('imports/{id}', [ImportController::class, 'update'])->name('imports.update');
    Route::delete('imports/{id}', [ImportController::class, 'destroy'])->name('imports.destroy');
    Route::get('imports/{id}/view/{file}', [ImportController::class, 'viewFile'])->name('imports.view');
    Route::get('imports/{id}/download/{file}', [ImportController::class, 'downloadFile'])->name('imports.download');
    // ADMIN: Export all DO reports (debe ir ANTES de la ruta con parámetro {id})
    Route::get('imports/export-all-reports', [ImportController::class, 'exportAllReports'])->name('imports.export-all-reports');
    // ADMIN: Export to Excel
    Route::get('imports/export-excel', [ImportController::class, 'exportExcel'])->name('imports.export-excel');
    Route::post('imports/clear-omitted-info', [ImportController::class, 'clearOmittedInfo'])->name('imports.clear-omitted-info');
    // ADMIN: Mark import as nationalized
    Route::post('imports/{id}/nationalize', [ImportController::class, 'markAsNationalized'])->name('imports.nationalize');
    // ADMIN: Mark credit as paid
    Route::post('imports/{id}/mark-credit-paid', [ImportController::class, 'markCreditAsPaid'])->name('imports.mark-credit-paid');
    Route::get('imports/{id}/report', [ImportController::class, 'report'])->name('imports.report');
    // FUNCIONARIO routes
    Route::get('imports-funcionario', [ImportController::class, 'funcionarioIndex'])->name('imports.funcionario-index');
    Route::post('imports/{id}/update-arrival', [ImportController::class, 'updateArrival'])->name('imports.update-arrival');
    Route::post('imports/{id}/update-estimated-arrival', [ImportController::class, 'updateEstimatedArrival'])->name('imports.update-estimated-arrival');
    Route::post('imports/{id}/deliver-to-transport', [ImportController::class, 'deliverToTransport'])->name('imports.deliver-to-transport');
    Route::post('imports/{id}/admin-confirm', [ImportController::class, 'adminConfirm'])->name('imports.admin-confirm');
});

// ITR MODULE (admin, funcionario, proveedor_itr)
use App\Http\Controllers\ItrController;
Route::middleware(['auth'])->group(function () {
    Route::get('itrs', [ItrController::class, 'index'])->name('itrs.index');
    Route::post('itrs/{id}/update-date', [ItrController::class, 'updateDate'])->name('itrs.update-date');
    Route::post('itrs/{itr}/upload-evidence', [ItrController::class, 'uploadEvidence'])->name('itrs.upload-evidence');
    Route::get('itrs/{itr}/download-evidence/{type}', [ItrController::class, 'downloadEvidence'])->name('itrs.download-evidence');
    Route::get('itrs/{id}/date-history/{field}', [ItrController::class, 'dateHistory'])->name('itrs.date-history');
});

Route::resource('salidas', App\Http\Controllers\SalidaController::class);
Route::resource('users', \App\Http\Controllers\UserController::class);
// Rutas específicas deben ir ANTES de la ruta resource para evitar conflictos
Route::get('drivers/{driver}/social-security-pdf', [App\Http\Controllers\DriverController::class, 'viewSocialSecurityPdf'])->name('drivers.social-security-pdf');
Route::get('drivers/{driver}/photo', [App\Http\Controllers\DriverController::class, 'viewPhoto'])->name('drivers.photo');
Route::get('drivers/{driver}/vehicle-photo', [App\Http\Controllers\DriverController::class, 'viewVehiclePhoto'])->name('drivers.vehicle-photo');
Route::resource('drivers', App\Http\Controllers\DriverController::class);
Route::resource('containers', App\Http\Controllers\ContainerController::class);

// Fix for logout via GET
// Fix for logout via GET triggering proper logout
Route::get('logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login')->with('error', 'Tu sesión ha cerrado correctamente.');
});

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
Route::get('stock/export-excel-products', [App\Http\Controllers\StockController::class, 'exportExcelProducts'])->name('stock.export-excel-products');
Route::get('stock/export-excel-containers', [App\Http\Controllers\StockController::class, 'exportExcelContainers'])->name('stock.export-excel-containers');
Route::get('stock/export-excel-transfers', [App\Http\Controllers\StockController::class, 'exportExcelTransfers'])->name('stock.export-excel-transfers');
Route::get('stock/export-excel-salidas', [App\Http\Controllers\StockController::class, 'exportExcelSalidas'])->name('stock.export-excel-salidas');
Route::get('traceability', [App\Http\Controllers\TraceabilityController::class, 'index'])->name('traceability.index');
Route::get('traceability/export-pdf', [App\Http\Controllers\TraceabilityController::class, 'exportPdf'])->name('traceability.export-pdf');
Route::get('traceability/export-excel', [App\Http\Controllers\TraceabilityController::class, 'exportExcel'])->name('traceability.export-excel');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
