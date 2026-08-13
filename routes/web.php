<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CompanySettingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TaxSettingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\SystemInfoController;
use App\Http\Controllers\DatabaseCleanupController;
use Inertia\Inertia;

// Public welcome page
Route::get('/welcome', function () {
    return Inertia::render('Welcome');
})->name('welcome');

// Redirect root to dashboard for authenticated users, login for guests
Route::get('/', function () {
    return auth()->check() 
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Include authentication routes
require __DIR__.'/auth.php';

use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PaymentController;

// Protected routes - require authentication
Route::middleware(['auth'])->group(function () {
    
    // Expenses
    Route::resource('expense-categories', ExpenseCategoryController::class);
    Route::resource('expenses', ExpenseController::class);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard/data', [DashboardController::class, 'getData'])->name('api.dashboard.data');

    // Session Management
    Route::prefix('api/session')->group(function () {
        Route::get('/info', [App\Http\Controllers\SessionController::class, 'getSessionInfo'])->name('api.session.info');
        Route::post('/extend', [App\Http\Controllers\SessionController::class, 'extendSession'])->name('api.session.extend');
        Route::get('/check', [App\Http\Controllers\SessionController::class, 'checkSession'])->name('api.session.check');
        Route::get('/active', [App\Http\Controllers\SessionController::class, 'getActiveSessions'])->name('api.session.active');
        Route::get('/test', [App\Http\Controllers\TestSessionController::class, 'testSession'])->name('api.session.test');
    });

    // POS Routes - permission-based access
    Route::middleware(['permission:sales.pos'])->group(function () {
        Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
        Route::get('/sales/create', [POSController::class, 'create'])->name('sales.create');
        Route::post('/pos/checkout', [POSController::class, 'checkout'])->name('pos.checkout');
        Route::put('/pos/update/{id}', [POSController::class, 'update'])->name('pos.update');
        Route::get('/pos/print/a4/{sale}', [POSController::class, 'printA4'])->name('pos.print.a4');
        Route::get('/pos/print/80mm/{sale}', [POSController::class, 'print80mm'])->name('pos.print.80mm');
        Route::get('/pos/preview/a4/{sale}', [POSController::class, 'previewA4'])->name('pos.preview.a4');
        Route::get('/pos/preview/80mm/{sale}', [POSController::class, 'preview80mm'])->name('pos.preview.80mm');
    });

    // Customers - Full CRUD (specific routes first)
    Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::post('/customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::get('/customers/datatable', [CustomerController::class, 'tableData'])->name('customers.datatable');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    
    // Customer Payment Management
    Route::get('/customers/{customer}/account', [CustomerController::class, 'account'])->name('customers.account');
    Route::post('/customers/{customer}/advances', [CustomerController::class, 'storeAdvance'])->name('customers.advances.store');
    Route::post('/customers/{customerId}/pending-payments/{pendingPaymentId}/credit-payment', [CustomerController::class, 'storeCreditPayment'])->name('customers.credit.payment');
    
    // Customer API routes for POS
    Route::get('/api/customers/search', [CustomerController::class, 'search'])->name('api.customers.search');
    Route::get('/api/customers/{customer}/pos-info', [CustomerController::class, 'getPosInfo'])->name('api.customers.pos-info');
    Route::get('/api/customers/{customer}/account', [CustomerController::class, 'apiAccount'])->name('api.customers.account');
    
    // Categories - Full CRUD
    Route::get('/categories/datatable', [CategoryController::class, 'tableData'])->name('categories.datatable');
    Route::get('/categories/export', [CategoryController::class, 'export'])->name('categories.export');
    Route::resource('categories', CategoryController::class);
    
    // Units - Full CRUD
    Route::get('/units/datatable', [UnitController::class, 'tableData'])->name('units.datatable');
    Route::get('/units/export', [UnitController::class, 'export'])->name('units.export');
    Route::resource('units', UnitController::class);
    
    // Products - Full CRUD (specific routes first)
    Route::get('/products/datatable', [ProductController::class, 'tableData'])->name('products.datatable');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::post('/products/generate-sku', [ProductController::class, 'generateSku'])->name('products.generate-sku');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/products/{product}/barcode', [ProductController::class, 'barcode'])->name('products.barcode');
    
    // Suppliers - Full CRUD (specific routes first)
    Route::get('/suppliers/datatable', [SupplierController::class, 'tableData'])->name('suppliers.datatable');
    Route::post('/suppliers/import', [SupplierController::class, 'import'])->name('suppliers.import');
    Route::get('/suppliers/export', [SupplierController::class, 'export'])->name('suppliers.export');
    Route::patch('/suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');
    Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // Purchases
    Route::get('/purchases/datatable', [PurchaseController::class, 'tableData'])->name('purchases.datatable');
    Route::get('/api/purchases/table', [PurchaseController::class, 'tableData'])->name('api.purchases.table');
    Route::get('/purchases/export', [PurchaseController::class, 'export'])->name('purchases.export');
    Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
    Route::patch('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::patch('/purchases/{purchase}/update-status', [PurchaseController::class, 'updateStatus'])->name('purchases.update-status');
    Route::resource('purchases', PurchaseController::class);

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/api/inventory/data', [InventoryController::class, 'getData'])->name('api.inventory.data');
    Route::get('/inventory/datatable', [InventoryController::class, 'getData'])->name('inventory.datatable');
    Route::get('/api/inventory/batches/{product}', [InventoryController::class, 'getBatches'])->name('api.inventory.batches');
    Route::get('/api/inventory/history/{product}', [InventoryController::class, 'getHistory'])->name('api.inventory.history');
    Route::get('/api/customers/search', [CustomerController::class, 'search'])->name('api.customers.search');

    Route::post('/inventory/adjust', [StockAdjustmentController::class, 'store'])->name('inventory.adjust');
    Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
    Route::get('/api/adjustments/batches/{product}', [StockAdjustmentController::class, 'getBatches'])->name('api.adjustments.batches');

    // Company Settings
    Route::get('/settings/company', [CompanySettingController::class, 'index'])->name('settings.company');
    Route::post('/settings/company', [CompanySettingController::class, 'update'])->name('settings.company.update');

    // User Management (Permission-based access)
    Route::middleware(['permission:users.view'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });
    
    Route::middleware(['permission:users.create'])->group(function () {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
    });
    
    Route::middleware(['permission:users.edit'])->group(function () {
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });
    
    Route::middleware(['permission:users.delete'])->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Role & Permission Management (Admin Only)
    Route::middleware(['permission:users.roles'])->group(function () {
        Route::resource('roles', RoleController::class);
        Route::patch('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');
        
        Route::resource('permissions', PermissionController::class);
        Route::patch('/permissions/{permission}/toggle', [PermissionController::class, 'toggleStatus'])->name('permissions.toggle');
    });

    // Sales - permission-based access (using SalesController for main routes)
    Route::middleware(['permission:sales.view_all|sales.view_own'])->group(function () {
        Route::get('/api/sales/table', [SaleController::class, 'tableData'])->name('api.sales.table');
    });
    
    Route::middleware(['permission:reports.export'])->group(function () {
        Route::get('/sales/export', [SaleController::class, 'export'])->name('sales.export');
    });

    // Cash Registers
    Route::get('/registers', [RegisterController::class, 'index'])->name('registers.index');
    Route::post('/registers/open', [RegisterController::class, 'open'])->name('registers.open');
    Route::post('/registers/close', [RegisterController::class, 'close'])->name('registers.close');

    // Reports - accessible by admin and accountant
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    
    // Sales Reports
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/sales/export-pdf', [ReportController::class, 'exportSalesPDF'])->name('reports.sales.export-pdf');
    Route::get('/reports/sales/export-excel', [ReportController::class, 'exportSalesExcel'])->name('reports.sales.export-excel');
    Route::get('/reports/sales/export-csv', [ReportController::class, 'exportSalesCSV'])->name('reports.sales.export-csv');
    
    // Purchase Reports
    Route::get('/reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');
    Route::get('/reports/purchases/export-pdf', [ReportController::class, 'exportPurchasesPDF'])->name('reports.purchases.export-pdf');
    Route::get('/reports/purchases/export-excel', [ReportController::class, 'exportPurchasesExcel'])->name('reports.purchases.export-excel');
    Route::get('/reports/purchases/export-csv', [ReportController::class, 'exportPurchasesCSV'])->name('reports.purchases.export-csv');
    
    // Profit Reports
    Route::get('/reports/profit', [ReportController::class, 'profit'])->name('reports.profit');
    Route::get('/reports/profit/export-pdf', [ReportController::class, 'exportProfitPDF'])->name('reports.profit.export-pdf');
    Route::get('/reports/profit/export-excel', [ReportController::class, 'exportProfitExcel'])->name('reports.profit.export-excel');
    Route::get('/reports/profit/export-csv', [ReportController::class, 'exportProfitCSV'])->name('reports.profit.export-csv');
    
    // Expense Reports
    Route::get('/reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
    Route::get('/reports/expenses/export-pdf', [ReportController::class, 'exportExpensesPDF'])->name('reports.expenses.export-pdf');
    Route::get('/reports/expenses/export-excel', [ReportController::class, 'exportExpensesExcel'])->name('reports.expenses.export-excel');
    Route::get('/reports/expenses/export-csv', [ReportController::class, 'exportExpensesCSV'])->name('reports.expenses.export-csv');
    
    // Register Reports
    Route::get('/reports/register', [\App\Http\Controllers\RegisterReportController::class, 'index'])->name('reports.register.index');
    Route::get('/reports/register/{id}', [\App\Http\Controllers\RegisterReportController::class, 'show'])->name('reports.register.show');

    // Customer Reports
    Route::get('/reports/customers', [ReportController::class, 'customers'])->name('reports.customers');
    Route::get('/reports/customers/export/pdf', [ReportController::class, 'exportCustomersPDF'])->name('reports.customers.export.pdf');
    Route::get('/reports/customers/export/csv', [ReportController::class, 'exportCustomersCSV'])->name('reports.customers.export.csv');

    // Receivables Report
    Route::get('/reports/receivables', [ReportController::class, 'receivables'])->name('reports.receivables');
    Route::get('/reports/receivables/export-pdf', [ReportController::class, 'exportReceivablesPDF'])->name('reports.receivables.export-pdf');

    Route::get('/reports/suppliers', [ReportController::class, 'suppliers'])->name('reports.suppliers');
    Route::get('/reports/suppliers/export/pdf', [ReportController::class, 'exportSuppliersPDF'])->name('reports.suppliers.export.pdf');
    Route::get('/reports/suppliers/export/csv', [ReportController::class, 'exportSuppliersCSV'])->name('reports.suppliers.export.csv');
    
    // Stock Report
    Route::get('/reports/stock', [ReportController::class, 'stock'])->name('reports.stock');

    // All Parties Ledger
    Route::get('/reports/all-parties-ledger', [ReportController::class, 'allPartiesLedger'])->name('reports.all-parties-ledger');

    // Legacy routes (keep for backward compatibility)
    Route::get('/api/reports/data', [ReportController::class, 'getData'])->name('api.reports.data');
    Route::get('/api/reports/export/top-products', [ReportController::class, 'exportTopProducts'])->name('api.reports.export.top-products');
    Route::get('/api/reports/export/top-customers', [ReportController::class, 'exportTopCustomers'])->name('api.reports.export.top-customers');
    Route::get('/api/reports/export/sales-history', [ReportController::class, 'exportSalesHistory'])->name('api.reports.export.sales-history');
    Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('/reports/low-stock', [ReportController::class, 'lowStock'])->name('reports.low-stock');

    // Settings
    Route::get('/settings', function () {
        return Inertia::render('Settings/Index');
    })->name('settings');

    // Company Settings - permission-based access
    Route::middleware(['permission:settings.company'])->group(function () {
        Route::get('/company/settings', [CompanySettingController::class, 'index'])->name('company.settings');
        Route::post('/company/settings', [CompanySettingController::class, 'update'])->name('company.settings.update');
        
        // Tax Settings
        Route::get('/settings/tax', [TaxSettingController::class, 'index'])->name('settings.tax');
        Route::post('/settings/tax', [TaxSettingController::class, 'update'])->name('settings.tax.update');
        Route::post('/settings/tax/rates', [TaxSettingController::class, 'updateRates'])->name('settings.tax.rates.update');
        
        // Database Cleanup - admin only
        Route::post('/settings/database/clear-all', [DatabaseCleanupController::class, 'clearAll'])->name('settings.database.clear-all');
        Route::post('/settings/database/clear-customers', [DatabaseCleanupController::class, 'clearCustomers'])->name('settings.database.clear-customers');
        Route::post('/settings/database/clear-suppliers', [DatabaseCleanupController::class, 'clearSuppliers'])->name('settings.database.clear-suppliers');
        Route::post('/settings/database/clear-products', [DatabaseCleanupController::class, 'clearProducts'])->name('settings.database.clear-products');
        Route::post('/settings/database/clear-sales', [DatabaseCleanupController::class, 'clearSales'])->name('settings.database.clear-sales');
        Route::post('/settings/database/clear-purchases', [DatabaseCleanupController::class, 'clearPurchases'])->name('settings.database.clear-purchases');
        Route::post('/settings/database/clear-categories', [DatabaseCleanupController::class, 'clearCategories'])->name('settings.database.clear-categories');
        Route::post('/settings/database/clear-units', [DatabaseCleanupController::class, 'clearUnits'])->name('settings.database.clear-units');
        
        // Admin database cleanup routes (for tests)
        Route::get('/admin/database-cleanup', [\App\Http\Controllers\Admin\DatabaseCleanupController::class, 'index']);
        Route::post('/admin/database-cleanup/clear-all', [\App\Http\Controllers\Admin\DatabaseCleanupController::class, 'clearAll']);
        Route::post('/admin/database-cleanup/clear-customers', [\App\Http\Controllers\Admin\DatabaseCleanupController::class, 'clearCustomers']);
        Route::post('/admin/database-cleanup/clear-suppliers', [\App\Http\Controllers\Admin\DatabaseCleanupController::class, 'clearSuppliers']);
        Route::post('/admin/database-cleanup/clear-products', [\App\Http\Controllers\Admin\DatabaseCleanupController::class, 'clearProducts']);
        Route::post('/admin/database-cleanup/clear-sales', [\App\Http\Controllers\Admin\DatabaseCleanupController::class, 'clearSales']);
        Route::post('/admin/database-cleanup/clear-purchases', [\App\Http\Controllers\Admin\DatabaseCleanupController::class, 'clearPurchases']);
        Route::post('/admin/database-cleanup/clear-categories', [\App\Http\Controllers\Admin\DatabaseCleanupController::class, 'clearCategories']);
        Route::post('/admin/database-cleanup/clear-units', [\App\Http\Controllers\Admin\DatabaseCleanupController::class, 'clearUnits']);
        
        // Backup & Restore
        Route::get('/settings/backup', [BackupController::class, 'index'])->name('settings.backup');
        Route::post('/settings/backup/create', [BackupController::class, 'create'])->name('settings.backup.create');
        Route::post('/settings/backup/restore', [BackupController::class, 'restore'])->name('settings.backup.restore');
    Route::post('/settings/backup/empty', [BackupController::class, 'empty'])->name('settings.backup.empty');
        Route::get('/settings/backup/download/{id}', [BackupController::class, 'download'])->name('settings.backup.download');
        Route::delete('/settings/backup/{id}', [BackupController::class, 'delete'])->name('settings.backup.delete');
        Route::post('/settings/backup/automated', [BackupController::class, 'updateAutomated'])->name('settings.backup.automated.update');
        
        // System Information
        Route::get('/settings/system-info', [SystemInfoController::class, 'index'])->name('settings.system-info');
    });

    // Sales History & Returns
    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');

    // Payments
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    Route::get('/api/party-balance', [PaymentController::class, 'getPartyBalance'])->name('api.party.balance');

    Route::get('/payments/customers', function () {
        return Inertia::render('Payments/Customers');
    })->name('payments.customers');

    Route::get('/payments/suppliers', function () {
        return Inertia::render('Payments/Suppliers');
    })->name('payments.suppliers');

    // Supplier API routes for Payments
    Route::get('/api/suppliers/search', [SupplierController::class, 'search'])->name('api.suppliers.search');
    Route::get('/api/suppliers/{supplier}/account', [SupplierController::class, 'apiAccount'])->name('api.suppliers.account');
    Route::post('/suppliers/{supplier}/prepayments', [SupplierController::class, 'storePrepayment'])->name('suppliers.prepayments.store');
    Route::post('/suppliers/{supplier}/payments/{purchase}', [SupplierController::class, 'storePayment'])->name('suppliers.payments.store');

    Route::get('/sales/{sale}', [SalesController::class, 'show'])
        ->name('sales.show')
        ->whereNumber('sale');
    Route::delete('/sales/{sale}', [SalesController::class, 'destroy'])
        ->name('sales.destroy')
        ->whereNumber('sale');
    
    // Returns - permission-based access
    Route::middleware(['permission:sales.returns'])->group(function () {
        Route::get('/sales/{sale}/return', [SalesController::class, 'createReturn'])
            ->name('sales.return.create')
            ->whereNumber('sale');
        Route::post('/sales/{sale}/return', [SalesController::class, 'storeReturn'])
            ->name('sales.return.store')
            ->whereNumber('sale');
    });

    // Print Routes - accessible by all authenticated users
    Route::get('/prints/invoice/{sale}/a4', [PrintController::class, 'invoiceA4'])->name('prints.invoice.a4');
    Route::get('/prints/invoice/{sale}/80mm', [PrintController::class, 'invoice80mm'])->name('prints.invoice.80mm');
    Route::get('/prints/return/{saleReturn}/a4', [PrintController::class, 'returnA4'])->name('prints.return.a4');
    Route::get('/prints/return/{saleReturn}/80mm', [PrintController::class, 'return80mm'])->name('prints.return.80mm');


    // Export Routes - permission-based access
    Route::middleware(['permission:reports.export'])->group(function () {
        Route::get('/exports/sales/csv', [ExportController::class, 'salesCsv'])->name('exports.sales.csv');
        Route::get('/exports/sales/excel', [ExportController::class, 'salesExcel'])->name('exports.sales.excel');
        Route::get('/exports/returns/csv', [ExportController::class, 'returnsCsv'])->name('exports.returns.csv');
        Route::get('/exports/returns/excel', [ExportController::class, 'returnsExcel'])->name('exports.returns.excel');
    });
    
    // TEMPORARY: Fix suppliers table - add opening_balance column
    // Access this URL once: http://localhost:8000/fix-suppliers-table
    // Then delete this route
    Route::get('/fix-suppliers-table', function () {
        try {
            // Check if column exists
            $columns = \DB::select("SHOW COLUMNS FROM suppliers LIKE 'opening_balance'");
            
            if (count($columns) > 0) {
                return response()->json([
                    'success' => true,
                    'message' => '✓ Column already exists! No action needed.',
                    'action' => 'delete_this_route'
                ]);
            }
            
            // Add the column
            \DB::statement("ALTER TABLE `suppliers` ADD COLUMN `opening_balance` DECIMAL(12, 2) NOT NULL DEFAULT '0.00' AFTER `address`");
            
            return response()->json([
                'success' => true,
                'message' => '✓ SUCCESS! opening_balance column added to suppliers table!',
                'action' => 'You can now add suppliers with opening balance. Please delete this route from routes/web.php'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'troubleshooting' => [
                    'Check if MySQL is running in XAMPP',
                    'Verify database connection in .env file',
                    'Make sure suppliers table exists'
                ]
            ], 500);
        }
    });
});

// Storage Link Fallback for Shared Hosting
Route::get('/storage/{extra}', function ($extra) {
    $path = storage_path('app/public/' . $extra);
    
    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->where('extra', '.*');
