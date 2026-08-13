<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Roles
        $adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'Full system access with all permissions',
            'is_active' => true
        ]);

        $salesRole = Role::create([
            'name' => 'Sales Staff',
            'slug' => 'sales',
            'description' => 'Limited access focused on sales operations',
            'is_active' => true
        ]);

        $accountantRole = Role::create([
            'name' => 'Accountant',
            'slug' => 'accountant',
            'description' => 'Access to financial data and reports',
            'is_active' => true
        ]);

        // Define all permissions
        $permissions = [
            // User Management
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'users', 'description' => 'View list of users'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'users', 'description' => 'Create new users'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'module' => 'users', 'description' => 'Edit existing users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'module' => 'users', 'description' => 'Delete users'],
            ['name' => 'Manage User Roles', 'slug' => 'users.roles', 'module' => 'users', 'description' => 'Manage user roles and permissions'],

            // Product Management
            ['name' => 'View Products', 'slug' => 'products.view', 'module' => 'products', 'description' => 'View product listings'],
            ['name' => 'Create Products', 'slug' => 'products.create', 'module' => 'products', 'description' => 'Create new products'],
            ['name' => 'Edit Products', 'slug' => 'products.edit', 'module' => 'products', 'description' => 'Edit existing products'],
            ['name' => 'Delete Products', 'slug' => 'products.delete', 'module' => 'products', 'description' => 'Delete products'],
            ['name' => 'Manage Product Categories', 'slug' => 'products.categories', 'module' => 'products', 'description' => 'Manage product categories'],
            ['name' => 'Manage Product Units', 'slug' => 'products.units', 'module' => 'products', 'description' => 'Manage product units'],
            ['name' => 'Import/Export Products', 'slug' => 'products.import_export', 'module' => 'products', 'description' => 'Import and export products'],

            // Customer Management
            ['name' => 'View Customers', 'slug' => 'customers.view', 'module' => 'customers', 'description' => 'View customer list'],
            ['name' => 'Create Customers', 'slug' => 'customers.create', 'module' => 'customers', 'description' => 'Create new customers'],
            ['name' => 'Edit Customers', 'slug' => 'customers.edit', 'module' => 'customers', 'description' => 'Edit customer information'],
            ['name' => 'Delete Customers', 'slug' => 'customers.delete', 'module' => 'customers', 'description' => 'Delete customers'],
            ['name' => 'Manage Customer Payments', 'slug' => 'customers.payments', 'module' => 'customers', 'description' => 'Manage customer advance payments'],

            // Sales Management
            ['name' => 'View All Sales', 'slug' => 'sales.view_all', 'module' => 'sales', 'description' => 'View all sales records'],
            ['name' => 'View Own Sales', 'slug' => 'sales.view_own', 'module' => 'sales', 'description' => 'View only own sales records'],
            ['name' => 'Create Sales', 'slug' => 'sales.create', 'module' => 'sales', 'description' => 'Create new sales/invoices'],
            ['name' => 'Edit Sales', 'slug' => 'sales.edit', 'module' => 'sales', 'description' => 'Edit sales records'],
            ['name' => 'Delete Sales', 'slug' => 'sales.delete', 'module' => 'sales', 'description' => 'Delete sales records'],
            ['name' => 'Process Returns', 'slug' => 'sales.returns', 'module' => 'sales', 'description' => 'Process sales returns'],
            ['name' => 'Use POS System', 'slug' => 'sales.pos', 'module' => 'sales', 'description' => 'Access POS system'],
            ['name' => 'Print Invoices', 'slug' => 'sales.print', 'module' => 'sales', 'description' => 'Print sales invoices'],

            // Supplier Management
            ['name' => 'View Suppliers', 'slug' => 'suppliers.view', 'module' => 'suppliers', 'description' => 'View supplier list'],
            ['name' => 'Create Suppliers', 'slug' => 'suppliers.create', 'module' => 'suppliers', 'description' => 'Create new suppliers'],
            ['name' => 'Edit Suppliers', 'slug' => 'suppliers.edit', 'module' => 'suppliers', 'description' => 'Edit supplier information'],
            ['name' => 'Delete Suppliers', 'slug' => 'suppliers.delete', 'module' => 'suppliers', 'description' => 'Delete suppliers'],

            // Purchase Management
            ['name' => 'View Purchases', 'slug' => 'purchases.view', 'module' => 'purchases', 'description' => 'View purchase records'],
            ['name' => 'Create Purchases', 'slug' => 'purchases.create', 'module' => 'purchases', 'description' => 'Create purchase orders'],
            ['name' => 'Edit Purchases', 'slug' => 'purchases.edit', 'module' => 'purchases', 'description' => 'Edit purchase orders'],
            ['name' => 'Delete Purchases', 'slug' => 'purchases.delete', 'module' => 'purchases', 'description' => 'Delete purchase orders'],
            ['name' => 'Receive Purchases', 'slug' => 'purchases.receive', 'module' => 'purchases', 'description' => 'Receive and process purchases'],

            // Inventory Management
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'module' => 'inventory', 'description' => 'View inventory levels'],
            ['name' => 'Adjust Stock', 'slug' => 'inventory.adjust', 'module' => 'inventory', 'description' => 'Make stock adjustments'],
            ['name' => 'View Stock History', 'slug' => 'inventory.history', 'module' => 'inventory', 'description' => 'View stock movement history'],

            // Reports & Analytics
            ['name' => 'View Sales Reports', 'slug' => 'reports.sales', 'module' => 'reports', 'description' => 'View sales reports and analytics'],
            ['name' => 'View Purchase Reports', 'slug' => 'reports.purchases', 'module' => 'reports', 'description' => 'View purchase reports'],
            ['name' => 'View Financial Reports', 'slug' => 'reports.financial', 'module' => 'reports', 'description' => 'View financial and profit/loss reports'],
            ['name' => 'View Inventory Reports', 'slug' => 'reports.inventory', 'module' => 'reports', 'description' => 'View inventory reports'],
            ['name' => 'Export Reports', 'slug' => 'reports.export', 'module' => 'reports', 'description' => 'Export reports to various formats'],

            // System Settings
            ['name' => 'Manage Company Settings', 'slug' => 'settings.company', 'module' => 'settings', 'description' => 'Manage company information and settings'],
            ['name' => 'Manage Tax Settings', 'slug' => 'settings.tax', 'module' => 'settings', 'description' => 'Configure tax rates and settings'],
            ['name' => 'Manage System Settings', 'slug' => 'settings.system', 'module' => 'settings', 'description' => 'Configure system-wide settings'],

            // Backup & Security
            ['name' => 'Create Backups', 'slug' => 'system.backup', 'module' => 'system', 'description' => 'Create system backups'],
            ['name' => 'Restore Backups', 'slug' => 'system.restore', 'module' => 'system', 'description' => 'Restore system from backups'],
            ['name' => 'Database Cleanup', 'slug' => 'system.cleanup', 'module' => 'system', 'description' => 'Perform database cleanup operations'],
            ['name' => 'View System Info', 'slug' => 'system.info', 'module' => 'system', 'description' => 'View system information'],

            // Transaction Approval
            ['name' => 'Approve Transactions', 'slug' => 'transactions.approve', 'module' => 'transactions', 'description' => 'Approve or reject transactions'],
            ['name' => 'View Payment Entries', 'slug' => 'payments.view', 'module' => 'payments', 'description' => 'View payment entries'],
            ['name' => 'Approve Payments', 'slug' => 'payments.approve', 'module' => 'payments', 'description' => 'Approve or reject payment entries'],
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Assign permissions to roles
        
        // ADMINISTRATOR - Gets all permissions (handled by isAdmin() check in User model)
        // Admin automatically has all permissions, so no need to assign individually
        
        // SALES STAFF - Limited permissions
        $salesPermissions = [
            // Customer Management
            'customers.view', 'customers.create', 'customers.edit', 'customers.payments',
            
            // Sales Management  
            'sales.view_own', 'sales.create', 'sales.pos', 'sales.print',
            
            // Product Viewing (for POS)
            'products.view',
            
            // Basic Inventory View
            'inventory.view',
            
            // Own Sales Reports
            'reports.sales'
        ];
        
        $salesRole->syncPermissions($salesPermissions);

        // ACCOUNTANT - Financial and reporting focus
        $accountantPermissions = [
            // Financial Reports
            'reports.sales', 'reports.purchases', 'reports.financial', 'reports.inventory', 'reports.export',
            
            // Payment Management
            'payments.view', 'payments.approve', 'transactions.approve',
            
            // View-only access to data
            'sales.view_all', 'purchases.view', 'customers.view', 'suppliers.view',
            'products.view', 'inventory.view',
        ];
        
        $accountantRole->syncPermissions($accountantPermissions);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Administrator: Full access to all system modules');
        $this->command->info('Sales Staff: ' . count($salesPermissions) . ' permissions assigned');
        $this->command->info('Accountant: ' . count($accountantPermissions) . ' permissions assigned');
    }
}