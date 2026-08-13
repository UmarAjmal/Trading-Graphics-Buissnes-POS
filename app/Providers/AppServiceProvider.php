<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define Gates for role-based access (backward compatibility)
        Gate::define('admin-access', function ($user) {
            return $user->role === 'admin';
        });
        
        Gate::define('sales-access', function ($user) {
            return in_array($user->role, ['admin', 'sales']);
        });
        
        Gate::define('reports-access', function ($user) {
            return in_array($user->role, ['admin', 'accountant']);
        });

        // Define Permission-based Gates
        Gate::define('manage-users', function ($user) {
            return $user->hasPermission('users.view') || $user->isAdmin();
        });

        Gate::define('create-users', function ($user) {
            return $user->hasPermission('users.create') || $user->isAdmin();
        });

        Gate::define('edit-users', function ($user) {
            return $user->hasPermission('users.edit') || $user->isAdmin();
        });

        Gate::define('delete-users', function ($user) {
            return $user->hasPermission('users.delete') || $user->isAdmin();
        });

        // Product Gates
        Gate::define('view-products', function ($user) {
            return $user->hasPermission('products.view') || $user->isAdmin() || $user->isSales();
        });

        Gate::define('manage-products', function ($user) {
            return $user->hasPermission('products.create') || $user->hasPermission('products.edit') || $user->isAdmin();
        });

        Gate::define('delete-products', function ($user) {
            return $user->hasPermission('products.delete') || $user->isAdmin();
        });

        // Customer Gates
        Gate::define('manage-customers', function ($user) {
            return $user->hasPermission('customers.view') || $user->isAdmin() || $user->isSales();
        });

        Gate::define('create-customers', function ($user) {
            return $user->hasPermission('customers.create') || $user->isAdmin() || $user->isSales();
        });

        Gate::define('edit-customers', function ($user) {
            return $user->hasPermission('customers.edit') || $user->isAdmin() || $user->isSales();
        });

        Gate::define('delete-customers', function ($user) {
            return $user->hasPermission('customers.delete') || $user->isAdmin();
        });

        // Sales Gates
        Gate::define('view-all-sales', function ($user) {
            return $user->hasPermission('sales.view_all') || $user->isAdmin() || $user->isAccountant();
        });

        Gate::define('view-own-sales', function ($user) {
            return $user->hasPermission('sales.view_own') || $user->isSales();
        });

        Gate::define('create-sales', function ($user) {
            return $user->hasPermission('sales.create') || $user->isAdmin() || $user->isSales();
        });

        Gate::define('edit-sales', function ($user) {
            return $user->hasPermission('sales.edit') || $user->isAdmin();
        });

        Gate::define('delete-sales', function ($user) {
            return $user->hasPermission('sales.delete') || $user->isAdmin();
        });

        Gate::define('use-pos', function ($user) {
            return $user->hasPermission('sales.pos') || $user->isAdmin() || $user->isSales();
        });

        Gate::define('process-returns', function ($user) {
            return $user->hasPermission('sales.returns') || $user->isAdmin();
        });

        // Purchase Gates
        Gate::define('view-purchases', function ($user) {
            return $user->hasPermission('purchases.view') || $user->isAdmin() || $user->isAccountant();
        });

        Gate::define('manage-purchases', function ($user) {
            return $user->hasPermission('purchases.create') || $user->hasPermission('purchases.edit') || $user->isAdmin();
        });

        Gate::define('delete-purchases', function ($user) {
            return $user->hasPermission('purchases.delete') || $user->isAdmin();
        });

        // Supplier Gates
        Gate::define('manage-suppliers', function ($user) {
            return $user->hasPermission('suppliers.view') || $user->isAdmin();
        });

        Gate::define('create-suppliers', function ($user) {
            return $user->hasPermission('suppliers.create') || $user->isAdmin();
        });

        Gate::define('edit-suppliers', function ($user) {
            return $user->hasPermission('suppliers.edit') || $user->isAdmin();
        });

        Gate::define('delete-suppliers', function ($user) {
            return $user->hasPermission('suppliers.delete') || $user->isAdmin();
        });

        // Inventory Gates
        Gate::define('view-inventory', function ($user) {
            return $user->hasPermission('inventory.view') || $user->isAdmin() || $user->isSales();
        });

        Gate::define('adjust-inventory', function ($user) {
            return $user->hasPermission('inventory.adjust') || $user->isAdmin();
        });

        // Reports Gates
        Gate::define('view-sales-reports', function ($user) {
            return $user->hasPermission('reports.sales') || $user->isAdmin() || $user->isAccountant() || 
                   ($user->isSales() && $user->hasPermission('sales.view_own'));
        });

        Gate::define('view-financial-reports', function ($user) {
            return $user->hasPermission('reports.financial') || $user->isAdmin() || $user->isAccountant();
        });

        Gate::define('view-inventory-reports', function ($user) {
            return $user->hasPermission('reports.inventory') || $user->isAdmin() || $user->isAccountant();
        });

        Gate::define('export-reports', function ($user) {
            return $user->hasPermission('reports.export') || $user->isAdmin() || $user->isAccountant();
        });

        // Settings Gates
        Gate::define('manage-company-settings', function ($user) {
            return $user->hasPermission('settings.company') || $user->isAdmin();
        });

        Gate::define('manage-system-settings', function ($user) {
            return $user->hasPermission('settings.system') || $user->isAdmin();
        });

        // System Gates
        Gate::define('backup-system', function ($user) {
            return $user->hasPermission('system.backup') || $user->isAdmin();
        });

        Gate::define('restore-system', function ($user) {
            return $user->hasPermission('system.restore') || $user->isAdmin();
        });

        Gate::define('cleanup-database', function ($user) {
            return $user->hasPermission('system.cleanup') || $user->isAdmin();
        });

        // Transaction approval Gates
        Gate::define('approve-transactions', function ($user) {
            return $user->hasPermission('transactions.approve') || $user->isAdmin();
        });

        Gate::define('approve-payments', function ($user) {
            return $user->hasPermission('payments.approve') || $user->isAdmin() || $user->isAccountant();
        });

        // Sales ownership check
        Gate::define('edit-own-sale', function ($user, $sale) {
            return $user->isAdmin() || 
                   ($user->hasPermission('sales.edit') && $sale->user_id === $user->id) ||
                   ($user->isSales() && $sale->user_id === $user->id);
        });
    }
}
