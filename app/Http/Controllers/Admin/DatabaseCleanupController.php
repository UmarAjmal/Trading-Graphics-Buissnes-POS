<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class DatabaseCleanupController extends Controller
{
    /**
     * Show database cleanup page
     */
    public function index()
    {
        Gate::authorize('admin-access');
        
        return inertia('Admin/DatabaseCleanup', [
            'stats' => [
                'customers' => Customer::count(),
                'suppliers' => Supplier::count(),
                'products' => Product::count(),
                'categories' => Category::count(),
                'units' => Unit::count(),
                'sales' => Sale::count(),
                'purchases' => Purchase::count(),
            ]
        ]);
    }

    /**
     * Clear all customers
     */
    public function clearCustomers()
    {
        Gate::authorize('admin-access');
        
        try {
            DB::beginTransaction();
            
            // Delete related data first
            DB::table('customer_advances')->delete();
            DB::table('customer_credit_payments')->delete();
            
            // Clear customers (this will cascade to sales if properly configured)
            $count = Customer::count();
            Customer::truncate();
            
            DB::commit();
            
            Log::info("Database cleanup: Cleared {$count} customers", [
                'user_id' => auth()->id(),
                'action' => 'clear_customers'
            ]);
            
            return redirect()->back()->with('success', "Successfully cleared {$count} customers");
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Database cleanup failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to clear customers: ' . $e->getMessage());
        }
    }

    /**
     * Clear all suppliers
     */
    public function clearSuppliers()
    {
        Gate::authorize('admin-access');
        
        try {
            $count = Supplier::count();
            Supplier::truncate();
            
            Log::info("Database cleanup: Cleared {$count} suppliers", [
                'user_id' => auth()->id(),
                'action' => 'clear_suppliers'
            ]);
            
            return redirect()->back()->with('success', "Successfully cleared {$count} suppliers");
            
        } catch (\Exception $e) {
            Log::error('Database cleanup failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to clear suppliers: ' . $e->getMessage());
        }
    }

    /**
     * Clear all products
     */
    public function clearProducts()
    {
        Gate::authorize('admin-access');
        
        try {
            DB::beginTransaction();
            
            // Clear related data first
            DB::table('sale_items')->delete();
            DB::table('purchase_items')->delete();
            DB::table('stock_batches')->delete();
            DB::table('stock_moves')->delete();
            DB::table('stock_adjustments')->delete();
            
            $count = Product::count();
            Product::truncate();
            
            DB::commit();
            
            Log::info("Database cleanup: Cleared {$count} products", [
                'user_id' => auth()->id(),
                'action' => 'clear_products'
            ]);
            
            return redirect()->back()->with('success', "Successfully cleared {$count} products");
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Database cleanup failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to clear products: ' . $e->getMessage());
        }
    }

    /**
     * Clear all sales
     */
    public function clearSales()
    {
        Gate::authorize('admin-access');
        
        try {
            DB::beginTransaction();
            
            // Clear related data first
            DB::table('sale_items')->delete();
            DB::table('sale_returns')->delete();
            DB::table('sale_return_items')->delete();
            
            $count = Sale::count();
            Sale::truncate();
            
            DB::commit();
            
            Log::info("Database cleanup: Cleared {$count} sales", [
                'user_id' => auth()->id(),
                'action' => 'clear_sales'
            ]);
            
            return redirect()->back()->with('success', "Successfully cleared {$count} sales");
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Database cleanup failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to clear sales: ' . $e->getMessage());
        }
    }

    /**
     * Clear all purchases
     */
    public function clearPurchases()
    {
        Gate::authorize('admin-access');
        
        try {
            DB::beginTransaction();
            
            // Clear related data first
            DB::table('purchase_items')->delete();
            
            $count = Purchase::count();
            Purchase::truncate();
            
            DB::commit();
            
            Log::info("Database cleanup: Cleared {$count} purchases", [
                'user_id' => auth()->id(),
                'action' => 'clear_purchases'
            ]);
            
            return redirect()->back()->with('success', "Successfully cleared {$count} purchases");
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Database cleanup failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to clear purchases: ' . $e->getMessage());
        }
    }

    /**
     * Clear all categories
     */
    public function clearCategories()
    {
        Gate::authorize('admin-access');
        
        try {
            $count = Category::count();
            Category::truncate();
            
            Log::info("Database cleanup: Cleared {$count} categories", [
                'user_id' => auth()->id(),
                'action' => 'clear_categories'
            ]);
            
            return redirect()->back()->with('success', "Successfully cleared {$count} categories");
            
        } catch (\Exception $e) {
            Log::error('Database cleanup failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to clear categories: ' . $e->getMessage());
        }
    }

    /**
     * Clear all units
     */
    public function clearUnits()
    {
        Gate::authorize('admin-access');
        
        try {
            $count = Unit::count();
            Unit::truncate();
            
            Log::info("Database cleanup: Cleared {$count} units", [
                'user_id' => auth()->id(),
                'action' => 'clear_units'
            ]);
            
            return redirect()->back()->with('success', "Successfully cleared {$count} units");
            
        } catch (\Exception $e) {
            Log::error('Database cleanup failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to clear units: ' . $e->getMessage());
        }
    }

    /**
     * Clear all data except users
     */
    public function clearAll()
    {
        Gate::authorize('admin-access');
        
        try {
            DB::beginTransaction();
            
            // Clear all related tables in proper order
            DB::table('sale_return_items')->delete();
            DB::table('sale_returns')->delete();
            DB::table('sale_items')->delete();
            DB::table('purchase_items')->delete();
            DB::table('stock_batches')->delete();
            DB::table('stock_moves')->delete();
            DB::table('stock_adjustments')->delete();
            DB::table('customer_advances')->delete();
            DB::table('customer_credit_payments')->delete();
            
            // Clear main tables
            Sale::truncate();
            Purchase::truncate(); 
            Product::truncate();
            Customer::truncate();
            Supplier::truncate();
            Category::truncate();
            Unit::truncate();
            
            DB::commit();
            
            Log::info("Database cleanup: Cleared all data except users", [
                'user_id' => auth()->id(),
                'action' => 'clear_all'
            ]);
            
            return redirect()->back()->with('success', 'Successfully cleared all data (users preserved)');
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Database cleanup failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to clear all data: ' . $e->getMessage());
        }
    }
}