<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanySettingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReportsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Simple API routes without authentication for company settings
Route::get('/settings/company', [CompanySettingController::class, 'apiGet'])->name('api.settings.company');

// Protected API routes that use web session authentication
Route::middleware(['web', 'auth'])->group(function () {
    // Company Settings API
    Route::put('/settings/company', [CompanySettingController::class, 'apiUpdate'])->name('api.settings.company.update');

    // POS API Routes
    Route::get('/products/search', [POSController::class, 'searchProducts'])->name('api.products.search');
    Route::post('/pos/calc', [POSController::class, 'calculate'])->name('api.pos.calc');
    Route::post('/pos/checkout', [POSController::class, 'checkout'])->name('api.pos.checkout');
    Route::get('/customers/search', [POSController::class, 'searchCustomers'])->name('api.customers.search');
    Route::get('/customers/details', [POSController::class, 'getCustomerDetails'])->name('api.customers.details');
    Route::get('/customers', [CustomerController::class, 'apiIndex'])->name('api.customers.index');
    Route::post('/customers', [CustomerController::class, 'apiStore'])->name('api.customers.store');

    // Products API
    Route::get('/products/table', [ProductController::class, 'tableData'])->name('api.products.table');
    Route::post('/products', [ProductController::class, 'apiStore'])->name('api.products.store');
    Route::get('/products/{product}', [ProductController::class, 'apiShow'])->name('api.products.show');
    Route::put('/products/{product}', [ProductController::class, 'apiUpdate'])->name('api.products.update');
    Route::delete('/products/{product}', [ProductController::class, 'apiDestroy'])->name('api.products.destroy');
    Route::post('/products/generate-sku', [ProductController::class, 'generateSku'])->name('api.products.generate-sku');

    // Reports API
    Route::get('/reports/daily-sales', [ReportsController::class, 'dailySales'])->name('api.reports.daily-sales');
    Route::get('/reports/monthly-sales', [ReportsController::class, 'monthlySales'])->name('api.reports.monthly-sales');
    Route::get('/reports/top-products', [ReportsController::class, 'topProducts'])->name('api.reports.top-products');
    Route::get('/reports/customer/{customer}', [ReportsController::class, 'customerReport'])->name('api.reports.customer');
    Route::get('/reports/inventory', [ReportsController::class, 'inventory'])->name('api.reports.inventory');
});