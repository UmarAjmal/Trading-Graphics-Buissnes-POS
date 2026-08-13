<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    private function checkAdminAccess()
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return null;
    }

    public function dailySales(Request $request)
    {
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck) return $adminCheck;
        
        $dateInput = $request->get('date', now()->format('Y-m-d'));
        
        // Validate date format
        try {
            $date = Carbon::createFromFormat('Y-m-d', $dateInput)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date format'], 400);
        }
        
        // Check if date is not in the future
        if (Carbon::createFromFormat('Y-m-d', $date)->isFuture()) {
            return response()->json(['error' => 'Future dates are not allowed'], 400);
        }
        
        $sales = Sale::with('customer')->whereDate('sold_at', $date)->get();
        
        $totalSales = $sales->count();
        $totalAmount = $sales->sum('grand_total');
        $cashSales = $sales->where('payment_type', 'cash')->sum('grand_total');
        $creditSales = $sales->where('payment_type', 'credit')->sum('grand_total');
        
        $salesData = $sales->map(function ($sale) {
            return [
                'id' => $sale->id,
                'customer_name' => $sale->customer->name ?? 'Walk-in Customer',
                'total_amount' => $sale->grand_total,
                'payment_method' => $sale->payment_type,
                'date' => $sale->sold_at->format('Y-m-d H:i:s')
            ];
        });
        
        return response()->json([
            'data' => [
                'total_sales' => $totalSales,
                'total_amount' => $totalAmount,
                'cash_sales' => $cashSales,
                'credit_sales' => $creditSales,
                'sales' => $salesData
            ]
        ]);
    }

    public function monthlySales(Request $request)
    {
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck) return $adminCheck;
        
        $month = $request->get('month', now()->format('Y-m'));
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        
        $sales = Sale::whereBetween('sold_at', [$startDate, $endDate])->get();
        
        $totalSales = $sales->count();
        $totalAmount = $sales->sum('grand_total');
        $averageSaleAmount = $totalSales > 0 ? round($totalAmount / $totalSales, 2) : 0;
        
        // Create daily breakdown
        $dailyBreakdown = $sales->groupBy(function ($sale) {
            return $sale->sold_at->format('Y-m-d');
        })->map(function ($daySales, $date) {
            return [
                'date' => $date,
                'sales_count' => $daySales->count(),
                'total_amount' => $daySales->sum('grand_total')
            ];
        })->values();
        
        return response()->json([
            'data' => [
                'month' => $month,
                'total_sales' => $totalSales,
                'total_amount' => $totalAmount,
                'average_sale_amount' => $averageSaleAmount,
                'daily_breakdown' => $dailyBreakdown
            ]
        ]);
    }

    public function topProducts(Request $request)
    {
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck) return $adminCheck;
        
        $limit = $request->get('limit', 10);
        
        $topProducts = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_quantity_sold'), DB::raw('SUM(line_total) as total_revenue'), DB::raw('COUNT(DISTINCT sale_id) as sale_count'))
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity_sold')
            ->limit($limit)
            ->get();
        
        $data = $topProducts->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'Unknown Product',
                'total_quantity_sold' => $item->total_quantity_sold,
                'total_revenue' => $item->total_revenue,
                'sale_count' => $item->sale_count
            ];
        });
        
        return response()->json(['data' => $data]);
    }

    public function customerReport($customerId)
    {
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck) return $adminCheck;
        
        $customer = Customer::findOrFail($customerId);
        $sales = Sale::where('customer_id', $customerId)->orderBy('sold_at', 'desc')->get();
        
        $totalPurchases = $sales->count();
        $totalAmount = $sales->sum('grand_total');
        
        $recentSales = $sales->take(5)->map(function ($sale) {
            return [
                'id' => $sale->id,
                'date' => $sale->sold_at->format('Y-m-d H:i:s'),
                'total_amount' => $sale->grand_total,
                'payment_method' => $sale->payment_type
            ];
        });

        return response()->json([
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone
                ],
                'total_purchases' => [
                    'count' => $totalPurchases,
                    'amount' => $totalAmount
                ],
                'advance_balance' => 0, // Placeholder
                'recent_sales' => $recentSales
            ]
        ]);
    }

    public function inventory()
    {
        $products = Product::all();
        
        $totalProducts = $products->count();
        $lowStockProducts = $products->filter(function ($product) {
            return $product->stock_quantity <= $product->min_stock;
        })->count();
        $outOfStockProducts = $products->where('stock_quantity', '<=', 0)->count();
        $totalValue = $products->sum(function ($product) {
            return $product->stock_quantity * $product->purchase_rate;
        });

        $productsData = $products->map(function ($product) {
            $stockValue = $product->stock_quantity * $product->purchase_rate;
            $status = 'normal';
            if ($product->stock_quantity <= 0) {
                $status = 'out_of_stock';
            } elseif ($product->stock_quantity <= $product->min_stock) {
                $status = 'low_stock';
            }
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => $product->stock_quantity,
                'min_stock' => $product->min_stock,
                'price' => $product->sale_rate,
                'stock_value' => $stockValue,
                'status' => $status
            ];
        });
        
        return response()->json([
            'data' => [
                'total_products' => $totalProducts,
                'low_stock_products' => $lowStockProducts,
                'out_of_stock_products' => $outOfStockProducts,
                'total_inventory_value' => $totalValue,
                'products' => $productsData
            ]
        ]);
    }
}