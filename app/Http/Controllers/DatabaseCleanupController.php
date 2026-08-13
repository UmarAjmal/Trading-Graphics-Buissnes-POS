<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DatabaseCleanupController extends Controller
{
    /**
     * Clear all database data except users and settings
     */
    public function clearAll(Request $request)
    {
        try {
            DB::transaction(function () {
                // Clear all transactional data
                DB::table('sale_returns')->delete();
                DB::table('sale_return_items')->delete();
                DB::table('sale_items')->delete();
                DB::table('sales')->delete();
                DB::table('purchase_items')->delete();
                DB::table('purchases')->delete();
                DB::table('stock_moves')->delete();
                DB::table('stock_batches')->delete();
                DB::table('stock_adjustments')->delete();
                DB::table('customer_credit_payments')->delete();
                DB::table('customer_advances')->delete();
                DB::table('pending_payments')->delete();
                DB::table('customers')->delete();
                DB::table('suppliers')->delete();
                DB::table('products')->delete();
                DB::table('categories')->delete();
                DB::table('units')->delete();
                
                Log::info('All database data cleared by user: ' . auth()->user()->name);
            });

            return back()->with('success', 'All database data has been cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Error clearing all data: ' . $e->getMessage());
            return back()->with('error', 'Failed to clear database data: ' . $e->getMessage());
        }
    }

    /**
     * Clear customers data
     */
    public function clearCustomers(Request $request)
    {
        try {
            DB::transaction(function () {
                // Clear customer related data in proper order
                DB::table('customer_credit_payments')->delete();
                DB::table('customer_advances')->delete();
                
                // Update sales to remove customer references
                DB::table('sales')->update(['customer_id' => null]);
                
                // Clear customers
                DB::table('customers')->delete();
                
                Log::info('Customers data cleared by user: ' . auth()->user()->name);
            });

            return back()->with('success', 'All customers data has been cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Error clearing customers: ' . $e->getMessage());
            return back()->with('error', 'Failed to clear customers data: ' . $e->getMessage());
        }
    }

    /**
     * Clear suppliers data
     */
    public function clearSuppliers(Request $request)
    {
        try {
            DB::transaction(function () {
                // Update purchases to remove supplier references
                DB::table('purchases')->update(['supplier_id' => null]);
                
                // Clear suppliers
                DB::table('suppliers')->delete();
                
                Log::info('Suppliers data cleared by user: ' . auth()->user()->name);
            });

            return back()->with('success', 'All suppliers data has been cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Error clearing suppliers: ' . $e->getMessage());
            return back()->with('error', 'Failed to clear suppliers data: ' . $e->getMessage());
        }
    }

    /**
     * Clear products data
     */
    public function clearProducts(Request $request)
    {
        try {
            DB::transaction(function () {
                // Clear product related data
                DB::table('sale_items')->delete();
                DB::table('purchase_items')->delete();
                DB::table('stock_moves')->delete();
                DB::table('stock_batches')->delete();
                DB::table('stock_adjustments')->delete();
                
                // Clear products
                DB::table('products')->delete();
                
                Log::info('Products data cleared by user: ' . auth()->user()->name);
            });

            return back()->with('success', 'All products data has been cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Error clearing products: ' . $e->getMessage());
            return back()->with('error', 'Failed to clear products data: ' . $e->getMessage());
        }
    }

    /**
     * Clear sales data
     */
    public function clearSales(Request $request)
    {
        try {
            DB::transaction(function () {
                // Clear sales related data
                DB::table('sale_returns')->delete();
                DB::table('sale_return_items')->delete();
                DB::table('sale_items')->delete();
                DB::table('customer_credit_payments')->delete();
                DB::table('pending_payments')->delete();
                DB::table('sales')->delete();
                
                Log::info('Sales data cleared by user: ' . auth()->user()->name);
            });

            return back()->with('success', 'All sales data has been cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Error clearing sales: ' . $e->getMessage());
            return back()->with('error', 'Failed to clear sales data: ' . $e->getMessage());
        }
    }

    /**
     * Clear purchases data
     */
    public function clearPurchases(Request $request)
    {
        try {
            DB::transaction(function () {
                // Clear purchase related data
                DB::table('purchase_items')->delete();
                DB::table('purchases')->delete();
                
                Log::info('Purchases data cleared by user: ' . auth()->user()->name);
            });

            return back()->with('success', 'All purchases data has been cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Error clearing purchases: ' . $e->getMessage());
            return back()->with('error', 'Failed to clear purchases data: ' . $e->getMessage());
        }
    }

    /**
     * Clear categories data
     */
    public function clearCategories(Request $request)
    {
        try {
            DB::transaction(function () {
                // Update products to remove category references
                DB::table('products')->update(['category_id' => null]);
                
                // Clear categories
                DB::table('categories')->delete();
                
                Log::info('Categories data cleared by user: ' . auth()->user()->name);
            });

            return back()->with('success', 'All categories data has been cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Error clearing categories: ' . $e->getMessage());
            return back()->with('error', 'Failed to clear categories data: ' . $e->getMessage());
        }
    }

    /**
     * Clear units data
     */
    public function clearUnits(Request $request)
    {
        try {
            DB::transaction(function () {
                // Update products to remove unit references
                DB::table('products')->update(['unit_id' => null]);
                
                // Clear units
                DB::table('units')->delete();
                
                Log::info('Units data cleared by user: ' . auth()->user()->name);
            });

            return back()->with('success', 'All units data has been cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Error clearing units: ' . $e->getMessage());
            return back()->with('error', 'Failed to clear units data: ' . $e->getMessage());
        }
    }
}