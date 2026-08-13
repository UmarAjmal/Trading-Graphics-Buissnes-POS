<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportSalesExport;
use App\Exports\ReportPurchasesExport;
use App\Exports\StockReportExport;

class ReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Reports/Index');
    }

    /**
     * Get comprehensive report data
     */
    public function getData(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $page = $request->input('page', 1);
        $perPage = 10;

        try {
            // Sales summary
            $salesSummary = Sale::whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ])
            ->where('invoice_no', 'not like', 'OPB-%') // Exclude Opening Balances
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(bill_total) as total_sales,
                SUM(
                    (SELECT SUM(si.quantity * COALESCE(p.purchase_rate, 0)) 
                     FROM sale_items si 
                     INNER JOIN products p ON si.product_id = p.id 
                     WHERE si.sale_id = sales.id)
                ) as total_cogs,
                AVG(bill_total) as avg_order_value
            ')
            ->first();

            // Items sold summary
            $itemsSummary = SaleItem::whereHas('sale', function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])->where('invoice_no', 'not like', 'OPB-%'); // Exclude Opening Balances
            })
            ->selectRaw('
                SUM(quantity) as items_sold,
                COUNT(DISTINCT product_id) as unique_products
            ')
            ->first();

            // Calculate Expenses
            $totalExpenses = Expense::whereBetween('date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString()
            ])->sum('amount');

            // Calculate actual profit and margin
            $grossProfit = $salesSummary->total_sales - ($salesSummary->total_cogs ?? 0);
            $netProfit = $grossProfit - $totalExpenses;
            
            $profitMargin = $salesSummary->total_sales > 0 
                ? round(($netProfit / $salesSummary->total_sales) * 100, 1)
                : 0;

            // Top selling products
            $topProducts = SaleItem::select('product_id')
                ->selectRaw('SUM(quantity) as quantity_sold')
                ->selectRaw('SUM(quantity * rate) as total_sales')
                ->with(['product:id,name,sku'])
                ->whereHas('sale', function($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ])->where('invoice_no', 'not like', 'OPB-%'); // Exclude Opening Balances
                })
                ->groupBy('product_id')
                ->orderByDesc('quantity_sold')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->product_id,
                        'name' => $item->product->name ?? 'Unknown Product',
                        'sku' => $item->product->sku ?? '',
                        'quantity_sold' => $item->quantity_sold,
                        'total_sales' => $item->total_sales
                    ];
                });

            // Top customers
            $topCustomers = Sale::select('customer_id')
                ->selectRaw('COUNT(*) as orders_count')
                ->selectRaw('SUM(bill_total) as total_spent')
                ->with(['customer:id,name,email'])
                ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->where('invoice_no', 'not like', 'OPB-%') // Exclude Opening Balances
                ->groupBy('customer_id')
                ->orderByDesc('total_spent')
                ->limit(10)
                ->get()
                ->map(function($sale) {
                    return [
                        'id' => $sale->customer_id,
                        'name' => $sale->customer->name ?? 'Unknown Customer',
                        'email' => $sale->customer->email ?? '',
                        'orders_count' => $sale->orders_count,
                        'total_spent' => $sale->total_spent
                    ];
                });

            // Sales history with pagination
            $salesHistory = Sale::with(['customer:id,name', 'saleItems'])
                ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->where('invoice_no', 'not like', 'OPB-%') // Exclude Opening Balances
                ->selectRaw('
                    id,
                    invoice_no as invoice_number,
                    customer_id,
                    bill_total as total_amount,
                    (bill_total - (SELECT SUM(si.quantity * COALESCE(p.purchase_rate, 0)) 
                                   FROM sale_items si 
                                   INNER JOIN products p ON si.product_id = p.id 
                                   WHERE si.sale_id = sales.id)) as profit,
                    created_at
                ')
                ->withCount('saleItems as items_count')
                ->orderByDesc('created_at')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'summary' => [
                    'totalSales' => $salesSummary->total_sales ?? 0,
                    'grossProfit' => $grossProfit,
                    'totalExpenses' => $totalExpenses,
                    'totalProfit' => $netProfit, // This is now Net Profit
                    'totalOrders' => $salesSummary->total_orders ?? 0,
                    'itemsSold' => $itemsSummary->items_sold ?? 0,
                    'uniqueProducts' => $itemsSummary->unique_products ?? 0,
                    'profitMargin' => $profitMargin,
                    'avgOrderValue' => $salesSummary->avg_order_value ?? 0
                ],
                'topProducts' => $topProducts,
                'topCustomers' => $topCustomers,
                'salesHistory' => $salesHistory
            ]);

        } catch (\Exception $e) {
            \Log::error('Report data fetch failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to load report data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export top products as CSV
     */
    public function exportTopProducts(Request $request)
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Handle custom period with proper date parsing
        if ($period === 'custom' && $startDate && $endDate) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        } else {
            [$startDate, $endDate] = $this->getDateRange($period, $startDate, $endDate);
        }

        $topProducts = SaleItem::select('product_id')
            ->selectRaw('SUM(quantity) as quantity_sold')
            ->selectRaw('SUM(quantity * rate) as total_sales')
            ->with(['product:id,name,sku'])
            ->whereHas('sale', function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->groupBy('product_id')
            ->orderByDesc('quantity_sold')
            ->get();

        $csv = "\xEF\xBB\xBF"; // Add BOM
        $csv .= "Product Name,SKU,Quantity Sold,Total Sales\n";
        foreach ($topProducts as $item) {
            $csv .= sprintf('"%s","%s",%d,%.2f' . "\n",
                $item->product->name ?? 'Unknown Product',
                $item->product->sku ?? '',
                $item->quantity_sold,
                $item->total_sales
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="top-products.csv"');
    }

    /**
     * Export top customers as CSV
     */
    public function exportTopCustomers(Request $request)
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Handle custom period with proper date parsing
        if ($period === 'custom' && $startDate && $endDate) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        } else {
            [$startDate, $endDate] = $this->getDateRange($period, $startDate, $endDate);
        }

        $topCustomers = Sale::select('customer_id')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(bill_total) as total_spent')
            ->with(['customer:id,name,email,phone'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->get();

        $csv = "\xEF\xBB\xBF"; // Add BOM
        $csv .= "Customer Name,Email,Phone,Orders Count,Total Spent\n";
        foreach ($topCustomers as $sale) {
            $csv .= sprintf('"%s","%s","%s",%d,%.2f' . "\n",
                $sale->customer->name ?? 'Unknown Customer',
                $sale->customer->email ?? '',
                $sale->customer->phone ?? '',
                $sale->orders_count,
                $sale->total_spent
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="top-customers.csv"');
    }

    /**
     * Export sales history as CSV
     */
    public function exportSalesHistory(Request $request)
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Handle custom period with proper date parsing
        if ($period === 'custom' && $startDate && $endDate) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        } else {
            [$startDate, $endDate] = $this->getDateRange($period, $startDate, $endDate);
        }

        $salesHistory = Sale::with(['customer:id,name'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                invoice_no as invoice_number,
                customer_id,
                bill_total as total_amount,
                (bill_total - (SELECT SUM(si.quantity * COALESCE(p.purchase_rate, 0)) 
                               FROM sale_items si 
                               INNER JOIN products p ON si.product_id = p.id 
                               WHERE si.sale_id = sales.id)) as profit,
                created_at
            ')
            ->withCount('saleItems as items_count')
            ->orderByDesc('created_at')
            ->get();

        $csv = "\xEF\xBB\xBF"; // Add BOM
        $csv .= "Date,Invoice Number,Customer,Items Count,Total Amount,Profit\n";
        foreach ($salesHistory as $sale) {
            $csv .= sprintf('"%s","%s","%s",%d,%.2f,%.2f' . "\n",
                $sale->created_at->format('Y-m-d H:i:s'),
                $sale->invoice_number,
                $sale->customer->name ?? 'Walk-in Customer',
                $sale->items_count,
                $sale->total_amount,
                $sale->profit
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="sales-history.csv"');
    }

    public function sales(Request $request)
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $paymentMethod = $request->input('payment_method');
        
        // Set date range based on period
        [$startDate, $endDate] = $this->getDateRange($period, $startDate, $endDate);
        
        // Sales summary
        $summary = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->where('invoice_no', 'not like', 'OPB-%') // Exclude Opening Balances
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(bill_total) as total_sales,
                AVG(bill_total) as avg_transaction,
                SUM(CASE WHEN payment_type = "cash" THEN bill_total ELSE 0 END) as cash_sales,
                SUM(CASE WHEN payment_type = "credit" THEN bill_total ELSE 0 END) as credit_sales,
                SUM(CASE WHEN payment_type = "bank" THEN bill_total ELSE 0 END) as bank_sales
            ')
            ->first();

        // Sales by date for chart
        $salesByDate = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->where('invoice_no', 'not like', 'OPB-%') // Exclude Opening Balances
            ->selectRaw('DATE(created_at) as date, SUM(bill_total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top products
        $topProducts = SaleItem::whereHas('sale', function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                      ->where('invoice_no', 'not like', 'OPB-%'); // Exclude Opening Balances
            })
            ->with('product:id,name,sku')
            ->selectRaw('product_id, SUM(quantity) as quantity_sold, SUM(quantity * rate) as total_sales')
            ->groupBy('product_id')
            ->orderByDesc('quantity_sold')
            ->limit(10)
            ->get();

        // Top customers
        $topCustomers = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->where('invoice_no', 'not like', 'OPB-%') // Exclude Opening Balances
            ->with('customer:id,name,email')
            ->whereNotNull('customer_id')
            ->selectRaw('customer_id, COUNT(*) as transactions, SUM(bill_total) as total_spent')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        // Detailed sales list
        $salesQuery = Sale::with(['customer:id,name', 'user:id,name'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('invoice_no', 'not like', 'OPB-%') // Exclude Opening Balances
            ->latest();

        if ($paymentMethod && in_array($paymentMethod, ['cash', 'credit', 'bank'])) {
            $salesQuery->where('payment_type', $paymentMethod);
        }

        $sales = $salesQuery->paginate(15);

        return Inertia::render('Reports/Sales', [
            'summary' => $summary,
            'salesByDate' => $salesByDate,
            'topProducts' => $topProducts,
            'topCustomers' => $topCustomers,
            'sales' => $sales,
            'filters' => [
                'period' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'payment_method' => $paymentMethod
            ]
        ]);
    }

    public function purchases(Request $request)
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Set date range based on period
        [$startDate, $endDate] = $this->getDateRange($period, $startDate, $endDate);
        
        // Purchase summary
        $summary = Purchase::whereBetween('purchased_at', [$startDate, $endDate])
            ->where('purchase_no', 'not like', 'OPB-%') // Exclude Opening Balances
            ->selectRaw('
                COUNT(*) as total_purchases,
                SUM(grand_total) as total_cost,
                AVG(grand_total) as avg_purchase,
                COUNT(DISTINCT supplier_id) as unique_suppliers
            ')
            ->first();

        // Purchases by date for chart
        $purchasesByDate = Purchase::whereBetween('purchased_at', [$startDate, $endDate])
            ->where('purchase_no', 'not like', 'OPB-%') // Exclude Opening Balances
            ->selectRaw('DATE(purchased_at) as date, SUM(grand_total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top suppliers
        $topSuppliers = Purchase::whereBetween('purchased_at', [$startDate, $endDate])
            ->where('purchase_no', 'not like', 'OPB-%') // Exclude Opening Balances
            ->with('supplier:id,name,email')
            ->whereNotNull('supplier_id')
            ->selectRaw('supplier_id, COUNT(*) as purchases, SUM(grand_total) as total_cost')
            ->groupBy('supplier_id')
            ->orderByDesc('total_cost')
            ->limit(10)
            ->get();

        // Detailed purchases list
        $purchases = Purchase::with(['supplier:id,name'])
            ->whereBetween('purchased_at', [$startDate, $endDate])
            ->where('purchase_no', 'not like', 'OPB-%') // Exclude Opening Balances
            ->latest('purchased_at')
            ->paginate(15);

        return Inertia::render('Reports/Purchases', [
            'summary' => $summary,
            'purchasesByDate' => $purchasesByDate,
            'topSuppliers' => $topSuppliers,
            'purchases' => $purchases,
            'filters' => [
                'period' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ]
        ]);
    }

    public function expenses(Request $request)
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $categoryId = $request->input('category_id');
        
        [$startDate, $endDate] = $this->getDateRange($period, $startDate, $endDate);
        
        $query = \App\Models\Expense::with('category')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
            
        if ($categoryId) {
            $query->where('expense_category_id', $categoryId);
        }
        
        // Clone query for stats
        $expenses = $query->get();
        
        // Summary Stats
        $totalExpenses = $expenses->sum('amount');
        $count = $expenses->count();
        $avgExpense = $count > 0 ? $totalExpenses / $count : 0;
        
        // By Category
        $byCategory = $expenses->groupBy(function($item) {
                return $item->category ? $item->category->name : 'Uncategorized';
            })
            ->map(function ($group) {
                return $group->sum('amount');
            })
            ->sortDesc();
            
        // By Date
        $byDate = $expenses->groupBy('date')
            ->map(function ($group) {
                return $group->sum('amount');
            })
            ->sortKeys();
            
        // Paginated List
        $paginatedExpenses = $query->latest('date')->paginate(20)->withQueryString();
        
        $categories = \App\Models\ExpenseCategory::all();

        return Inertia::render('Reports/Expenses', [
            'expenses' => $paginatedExpenses,
            'categories' => $categories,
            'summary' => [
                'total_expenses' => $totalExpenses,
                'avg_expense' => $avgExpense,
                'count' => $count,
                'by_category' => $byCategory,
                'by_date' => $byDate
            ],
            'filters' => [
                'period' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'category_id' => $categoryId
            ]
        ]);
    }

    public function profit(Request $request)
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Set date range based on period
        [$startDate, $endDate] = $this->getDateRange($period, $startDate, $endDate);
        
        $analytics = $this->calculateProfitData($startDate, $endDate);

        return Inertia::render('Reports/Profit', [
            'summary' => $analytics['summary'],
            'profitByDate' => $analytics['profitByDate'],
            'filters' => [
                'period' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ]
        ]);
    }

    private function getDateRange($period, $startDate = null, $endDate = null)
    {
        $now = Carbon::now();
        
        switch ($period) {
            case 'daily':
                return [
                    $startDate ? Carbon::parse($startDate)->startOfDay() : $now->copy()->startOfDay(),
                    $endDate ? Carbon::parse($endDate)->endOfDay() : $now->copy()->endOfDay()
                ];
            
            case 'weekly':
                return [
                    $now->copy()->startOfWeek(),
                    $now->copy()->endOfWeek()
                ];
            
            case 'monthly':
                return [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth()
                ];
            
            case 'yearly':
                return [
                    $now->copy()->startOfYear(),
                    $now->copy()->endOfYear()
                ];
            
            case 'custom':
                return [
                    $startDate ? Carbon::parse($startDate)->startOfDay() : $now->copy()->startOfMonth(),
                    $endDate ? Carbon::parse($endDate)->endOfDay() : $now->copy()->endOfMonth()
                ];
            
            default:
                return [
                    $now->copy()->startOfDay(),
                    $now->copy()->endOfDay()
                ];
        }
    }

    /**
     * Build profit summary and per-day breakdown using actual COGS.
     */
    private function calculateProfitData(Carbon $startDate, Carbon $endDate): array
    {
        // 1. Get Sales Summary with proper COGS calculation
        $salesData = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->where('invoice_no', 'not like', 'OPB-%') // Exclude Opening Balances
            ->with(['saleItems.product'])
            ->get();

        $totalSales = 0;
        $totalCogs = 0;
        $salesCount = $salesData->count();

        foreach ($salesData as $sale) {
            $totalSales += $sale->bill_total;
            foreach ($sale->saleItems as $item) {
                // Calculate COGS: units_sqft * purchase_rate (cost price)
                // Use units_sqft if available (for Panaflex), otherwise fallback to quantity
                $qty = $item->units_sqft > 0 ? $item->units_sqft : $item->quantity;
                $purchaseRate = $item->product->purchase_rate ?? 0;
                $totalCogs += ($qty * $purchaseRate);
            }
        }

        // 2. Get Returns Summary
        $returnsData = \App\Models\SaleReturn::whereBetween('returned_at', [$startDate, $endDate])
            ->whereHas('sale', function($q) {
                $q->where('invoice_no', 'not like', 'OPB-%');
            })
            ->with(['saleReturnItems.saleItem.product'])
            ->get();

        $totalReturns = 0;
        $returnsCogs = 0;

        foreach ($returnsData as $return) {
            $totalReturns += $return->grand_total;
            foreach ($return->saleReturnItems as $returnItem) {
                // Use units_sqft for returns too
                $qty = $returnItem->units_sqft > 0 ? $returnItem->units_sqft : $returnItem->quantity;
                $purchaseRate = $returnItem->saleItem->product->purchase_rate ?? 0;
                $returnsCogs += ($qty * $purchaseRate);
            }
        }

        // 3. Calculate Net Totals
        $netSales = $totalSales - $totalReturns;
        $netCogs = $totalCogs - $returnsCogs;
        $grossProfit = $netSales - $netCogs;
        
        // Calculate Expenses
        $totalExpenses = \App\Models\Expense::whereBetween('date', [
            $startDate->toDateString(),
            $endDate->toDateString()
        ])->sum('amount');

        $netProfit = $grossProfit - $totalExpenses;
        
        $profitMargin = $netSales > 0 ? round(($netProfit / $netSales) * 100, 2) : 0;

        // 4. Get Daily Sales with COGS
        $salesByDate = [];
        foreach ($salesData as $sale) {
            $date = $sale->created_at->format('Y-m-d');
            if (!isset($salesByDate[$date])) {
                $salesByDate[$date] = ['sales' => 0, 'cogs' => 0];
            }
            $salesByDate[$date]['sales'] += $sale->bill_total;
            
            foreach ($sale->saleItems as $item) {
                $qty = $item->units_sqft > 0 ? $item->units_sqft : $item->quantity;
                $purchaseRate = $item->product->purchase_rate ?? 0;
                $salesByDate[$date]['cogs'] += ($qty * $purchaseRate);
            }
        }

        // 5. Get Daily Returns with COGS
        $returnsByDate = [];
        foreach ($returnsData as $return) {
            $date = $return->returned_at->format('Y-m-d');
            if (!isset($returnsByDate[$date])) {
                $returnsByDate[$date] = ['returns' => 0, 'returns_cogs' => 0];
            }
            $returnsByDate[$date]['returns'] += $return->grand_total;
            
            foreach ($return->saleReturnItems as $returnItem) {
                $qty = $returnItem->units_sqft > 0 ? $returnItem->units_sqft : $returnItem->quantity;
                $purchaseRate = $returnItem->saleItem->product->purchase_rate ?? 0;
                $returnsByDate[$date]['returns_cogs'] += ($qty * $purchaseRate);
            }
        }

        // 5.5 Get Daily Expenses
        $expensesData = \App\Models\Expense::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('date, SUM(amount) as total_amount')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // 6. Merge and Calculate Daily Stats
        $allDates = array_unique(array_merge(
            array_keys($salesByDate), 
            array_keys($returnsByDate),
            $expensesData->keys()->toArray()
        ));
        sort($allDates);
        
        $profitByDate = [];
        foreach ($allDates as $date) {
            $daySales = $salesByDate[$date]['sales'] ?? 0;
            $daySaleCogs = $salesByDate[$date]['cogs'] ?? 0;
            
            $dayReturns = $returnsByDate[$date]['returns'] ?? 0;
            $dayReturnCogs = $returnsByDate[$date]['returns_cogs'] ?? 0;
            
            $dayExpenses = $expensesData[$date]->total_amount ?? 0;

            $netDaySales = $daySales - $dayReturns;
            $netDayCogs = $daySaleCogs - $dayReturnCogs;
            $grossDayProfit = $netDaySales - $netDayCogs;
            $netDayProfit = $grossDayProfit - $dayExpenses;

            $profitByDate[] = [
                'date' => $date,
                'sales' => $netDaySales,
                'cogs' => $netDayCogs,
                'expenses' => $dayExpenses,
                'profit' => $netDayProfit,
            ];
        }

        // 7. Calculate Receivables (Linked to Filter)
        $totalReceivables = \App\Models\PendingPayment::whereNotNull('customer_id')
            ->where('settled', false)
            ->whereHas('sale', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->sum('amount_due');

        // 8. Calculate Payables (Linked to Filter: Purchases - Payments)
        // Since PendingPayment is not always used for suppliers, we calculate net payable change
        $periodPurchases = \App\Models\Purchase::whereBetween('purchased_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('grand_total');

        $periodPayments = \App\Models\Payment::whereNotNull('supplier_id')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');

        $totalPayables = $periodPurchases - $periodPayments;

        return [
            'summary' => [
                'total_sales' => $netSales,
                'total_cogs' => $netCogs,
                'gross_profit' => $grossProfit,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
                'profit_margin' => $profitMargin,
                'sales_count' => $salesCount,
                'total_purchases' => $netCogs, // Legacy field
                'total_receivables' => (float) $totalReceivables,
                'total_payables' => (float) $totalPayables,
            ],
            'profitByDate' => $profitByDate,
        ];
    }

    // Export Methods
    public function exportSalesPDF(Request $request)
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Handle custom period with proper date parsing
        if ($period === 'custom' && $startDate && $endDate) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        } else {
            [$startDate, $endDate] = $this->getDateRange($period, $startDate, $endDate);
        }
        
        // Debug logging for troubleshooting
        \Log::info('Sales PDF Export', [
            'period' => $period,
            'start_date' => $startDate->toDateTimeString(),
            'end_date' => $endDate->toDateTimeString(),
            'request_params' => $request->all()
        ]);
        
        $sales = Sale::with(['customer:id,name', 'user:id,name'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('invoice_no', 'not like', 'OPB-%') // Exclude Opening Balances
            ->latest()
            ->get();

        $summary = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->where('invoice_no', 'not like', 'OPB-%') // Exclude Opening Balances
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(bill_total) as total_sales,
                AVG(bill_total) as avg_transaction
            ')
            ->first();

        $data = [
            'title' => 'Sales Report',
            'period' => ucfirst($period),
            'start_date' => $startDate->format('F j, Y'),
            'end_date' => $endDate->format('F j, Y'),
            'generated_at' => now()->format('F j, Y \a\t g:i A'),
            'summary' => $summary,
            'sales' => $sales
        ];

        // For now, return HTML that can be converted to PDF by browser
        return view('reports.sales-pdf', $data);
    }

    public function exportSalesExcel(Request $request)
    {
        return $this->exportSalesCSV($request, 'excel');
    }

    public function exportSalesCSV(Request $request, $format = 'csv')
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Handle custom period with proper date parsing
        if ($period === 'custom' && $startDate && $endDate) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        } else {
            [$startDate, $endDate] = $this->getDateRange($period, $startDate, $endDate);
        }
        
        $sales = Sale::with(['customer:id,name', 'user:id,name'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('invoice_no', 'not like', 'OPB-%') // Exclude Opening Balances
            ->latest()
            ->get();

        $filename = "sales-report-{$period}-{$startDate->format('Y-m-d')}";

        if ($format === 'excel') {
            return Excel::download(new ReportSalesExport($sales), "{$filename}.xlsx");
        }

        $csv = "\xEF\xBB\xBF"; // Add BOM for Excel compatibility
        $csv .= "Date,Invoice No,Customer,Total,Payment Method,Cashier\n";
        foreach ($sales as $sale) {
            $csv .= sprintf('"%s","%s","%s",%.2f,"%s","%s"' . "\n",
                $sale->created_at->format('Y-m-d H:i'),
                $sale->invoice_no,
                $sale->customer->name ?? 'Walk-in Customer',
                $sale->bill_total,
                $sale->payment_type,
                $sale->user->name ?? 'Unknown'
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
    }

    public function exportPurchasesPDF(Request $request)
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Handle custom period with proper date parsing
        if ($period === 'custom' && $startDate && $endDate) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        } else {
            [$startDate, $endDate] = $this->getDateRange($period, $startDate, $endDate);
        }
        
        $purchases = Purchase::with(['supplier:id,name'])
            ->whereBetween('purchased_at', [$startDate, $endDate])
            ->latest('purchased_at')
            ->get();

        $summary = Purchase::whereBetween('purchased_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_purchases,
                SUM(grand_total) as total_cost,
                AVG(grand_total) as avg_purchase
            ')
            ->first();

        $data = [
            'title' => 'Purchase Report',
            'period' => ucfirst($period),
            'start_date' => $startDate->format('F j, Y'),
            'end_date' => $endDate->format('F j, Y'),
            'generated_at' => now()->format('F j, Y \a\t g:i A'),
            'summary' => $summary,
            'purchases' => $purchases
        ];

        // For now, return HTML that can be converted to PDF by browser
        return view('reports.purchases-pdf', $data);
    }

    public function exportPurchasesExcel(Request $request)
    {
        return $this->exportPurchasesCSV($request, 'excel');
    }

    public function exportPurchasesCSV(Request $request, $format = 'csv')
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Handle custom period with proper date parsing
        if ($period === 'custom' && $startDate && $endDate) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        } else {
            [$startDate, $endDate] = $this->getDateRange($period, $startDate, $endDate);
        }
        
        $purchases = Purchase::with(['supplier:id,name'])
            ->whereBetween('purchased_at', [$startDate, $endDate])
            ->latest('purchased_at')
            ->get();

        $filename = "purchase-report-{$period}-{$startDate->format('Y-m-d')}";

        if ($format === 'excel') {
            return Excel::download(new ReportPurchasesExport($purchases), "{$filename}.xlsx");
        }

        $csv = "\xEF\xBB\xBF"; // Add BOM for Excel compatibility
        $csv .= "Date,Purchase No,Supplier,Total Cost,Status\n";
        foreach ($purchases as $purchase) {
            $csv .= sprintf('"%s","%s","%s",%.2f,"%s"' . "\n",
                $purchase->purchased_at->format('Y-m-d'),
                $purchase->purchase_no,
                $purchase->supplier->name ?? 'Unknown Supplier',
                $purchase->grand_total,
                $purchase->status
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
    }

    public function exportProfitPDF(Request $request)
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Handle custom period with proper date parsing
        if ($period === 'custom' && $startDate && $endDate) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        } else {
            [$startDate, $endDate] = $this->getDateRange($period, $startDate, $endDate);
        }
        
        $analytics = $this->calculateProfitData($startDate, $endDate);
        $profitByDate = $analytics['profitByDate'];
        $summary = $analytics['summary'];

        $data = [
            'title' => 'Profit Report',
            'period' => ucfirst($period),
            'start_date' => $startDate->format('F j, Y'),
            'end_date' => $endDate->format('F j, Y'),
            'generated_at' => now()->format('F j, Y \a\t g:i A'),
            'summary' => $summary,
            'profitByDate' => $profitByDate
        ];

        // For now, return HTML that can be converted to PDF by browser
        return view('reports.profit-pdf', $data);
    }

    public function exportProfitExcel(Request $request)
    {
        return $this->exportProfitCSV($request, 'excel');
    }

    public function exportProfitCSV(Request $request, $format = 'csv')
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Handle custom period with proper date parsing
        if ($period === 'custom' && $startDate && $endDate) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        } else {
            [$startDate, $endDate] = $this->getDateRange($period, $startDate, $endDate);
        }
        
        $analytics = $this->calculateProfitData($startDate, $endDate);
        $profitByDate = $analytics['profitByDate']->map(function ($item) {
            $sales = $item['sales'];
            $profit = $item['profit'];
            $margin = $sales > 0 ? round(($profit / $sales) * 100, 2) : 0;

            return array_merge($item, [
                'margin' => $margin,
            ]);
        });

        $filename = "profit-report-{$period}-{$startDate->format('Y-m-d')}";

        if ($format === 'excel') {
            return $this->generateExcel($profitByDate, $filename, [
                'Date', 'Sales', 'COGS', 'Profit', 'Margin %'
            ], function($item) {
                return [
                    $item['date'],
                    $item['sales'],
                    $item['cogs'],
                    $item['profit'],
                    $item['margin'] . '%'
                ];
            });
        }

        $csv = "Date,Sales,COGS,Profit,Margin %\n";
        foreach ($profitByDate as $item) {
            $csv .= sprintf('"%s",%.2f,%.2f,%.2f,"%.1f%%"' . "\n",
                $item['date'],
                $item['sales'],
                $item['cogs'],
                $item['profit'],
                $item['margin']
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
    }

    private function generateExcel($data, $filename, $headers, $rowCallback)
    {
        // This is a simple CSV-like Excel generation
        // In a real application, you'd use Laravel Excel package
        $output = implode(',', $headers) . "\n";
        
        foreach ($data as $item) {
            $row = $rowCallback($item);
            $output .= implode(',', array_map(function($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $row)) . "\n";
        }

        return response($output)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.xls\"");
    }

    public function inventory()
    {
        $products = Product::with(['category', 'unit'])
            ->select('*')
            ->selectRaw('(current_stock * purchase_rate) as stock_value')
            ->get();

        return Inertia::render('Reports/Inventory', [
            'products' => $products
        ]);
    }

    public function lowStock()
    {
        $products = Product::with(['category', 'unit'])
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->get();

        return Inertia::render('Reports/LowStock', [
            'products' => $products
        ]);
    }

    /**
     * Customer Reports - Ledger View
     */
    public function customers(Request $request)
    {
        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone']);
        
        if ($request->has('customer_id') && $request->customer_id) {
            $customerId = $request->customer_id;
            $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subMonth()->startOfDay();
            $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
            
            $customer = Customer::find($customerId);
            
            if ($customer) {
                $transactions = collect();
                
                // 1. Calculate Opening Balance
                // Formula: Sales - Returns - Payments - Advances(Received)
                
                $salesBefore = Sale::where('customer_id', $customerId)
                    ->where('sold_at', '<', $dateFrom)
                    ->sum('bill_total');
                    
                $returnsBefore = \App\Models\SaleReturn::whereHas('sale', function($q) use ($customerId) {
                        $q->where('customer_id', $customerId);
                    })
                    ->where('returned_at', '<', $dateFrom)
                    ->sum('grand_total');
                    
                $paymentsBefore = \App\Models\Payment::where('customer_id', $customerId)
                    ->where('type', 'received')
                    ->where('payment_date', '<', $dateFrom)
                    ->sum('amount');
                    
                // Only include POSITIVE advances (Money received)
                $advancesBefore = \App\Models\CustomerAdvance::where('customer_id', $customerId)
                    ->where('amount', '>', 0)
                    ->where('created_at', '<', $dateFrom)
                    ->sum('amount');
                
                // Note: We subtract payments and advances because they are Credits (reduce the debt)
                $openingBalance = $salesBefore - $returnsBefore - $paymentsBefore - $advancesBefore;
                
                // Add Opening Balance Row
                $transactions->push([
                    'date' => $dateFrom->copy()->subSecond(),
                    'type' => 'opening_balance',
                    'description' => 'Opening Balance',
                    'debit' => $openingBalance > 0 ? $openingBalance : 0,
                    'credit' => $openingBalance < 0 ? abs($openingBalance) : 0,
                    'balance' => $openingBalance,
                    'voucher_no' => 'B/F'
                ]);
                
                // 2. Get Current Transactions
                
                // Sales
                $sales = Sale::where('customer_id', $customerId)
                    ->whereBetween('sold_at', [$dateFrom, $dateTo])
                    ->get();
                
                // Map sale IDs to their timestamps for filtering duplicate payments
                $saleTimestamps = $sales->pluck('sold_at', 'id');

                foreach ($sales as $sale) {
                    $isOpb = str_starts_with($sale->invoice_no, 'OPB-');
                    
                    // Calculate Cash Paid part (Total Paid - Advance Used)
                    $cashPaid = $sale->paid_amount - ($sale->advance_used ?? 0);
                    $due = $sale->bill_total - $sale->paid_amount;
                    
                    // Format Description
                    $desc = $isOpb ? 'Opening Balance' : 'Sale Invoice';
                    if (!$isOpb && $cashPaid > 0) {
                        $desc .= " (Cash " . number_format($cashPaid, 0) . " / Due " . number_format($due, 0) . ")";
                    }

                    $transactions->push([
                        'date' => $sale->sold_at,
                        'type' => 'sale',
                        'description' => $desc,
                        'debit' => $sale->bill_total,
                        'credit' => $cashPaid > 0 ? $cashPaid : 0, // Show Cash Paid as Credit on Sale Row
                        'voucher_no' => $sale->invoice_no,
                        'data' => $sale
                    ]);
                }
                
                // Returns
                $returns = \App\Models\SaleReturn::whereHas('sale', function($q) use ($customerId) {
                        $q->where('customer_id', $customerId);
                    })
                    ->whereBetween('returned_at', [$dateFrom, $dateTo])
                    ->get();
                    
                foreach ($returns as $ret) {
                    $transactions->push([
                        'date' => $ret->returned_at,
                        'type' => 'return',
                        'description' => 'Sales Return',
                        'debit' => 0,
                        'credit' => $ret->grand_total,
                        'voucher_no' => $ret->return_no ?? '-',
                        'data' => $ret
                    ]);
                }
                
                // Payments (New System)
                $payments = \App\Models\Payment::where('customer_id', $customerId)
                    ->whereBetween('payment_date', [$dateFrom, $dateTo])
                    ->get();
                    
                foreach ($payments as $pay) {
                    // Skip if this is an initial payment for a sale we already processed
                    // Logic: Has sale_id AND sale exists in our list AND time difference is small (< 10 mins)
                    if ($pay->sale_id && isset($saleTimestamps[$pay->sale_id])) {
                        $saleTime = Carbon::parse($saleTimestamps[$pay->sale_id]);
                        $payTime = Carbon::parse($pay->created_at); // Use created_at for precision
                        
                        if ($payTime->diffInMinutes($saleTime) < 10) {
                            continue; // Skip this payment as it's merged into Sale row
                        }
                    }

                    $isReceived = $pay->type === 'received';
                    $desc = $isReceived ? 'Cash Received' : 'Cash Paid';
                    if ($pay->note) {
                        $desc .= ' - ' . $pay->note;
                    }
                    
                    // Use created_at for sorting if the payment date matches the creation date (Real-time entry)
                    // Otherwise use payment_date (Backdated entry)
                    $sortDate = $pay->created_at->isSameDay($pay->payment_date) ? $pay->created_at : $pay->payment_date;

                    $transactions->push([
                        'date' => $sortDate,
                        'type' => 'payment',
                        'description' => $desc,
                        'debit' => $isReceived ? 0 : $pay->amount,
                        'credit' => $isReceived ? $pay->amount : 0,
                        'voucher_no' => $pay->id,
                        'data' => $pay
                    ]);
                }

                // Credit Payments (Old System / Pay Later)
                $creditPayments = \App\Models\CustomerCreditPayment::where('customer_id', $customerId)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->get();

                foreach ($creditPayments as $cPay) {
                    $desc = 'Payment Received';
                    if ($cPay->note) $desc .= ' - ' . $cPay->note;

                    $transactions->push([
                        'date' => $cPay->created_at, // Use created_at as payment_date might be just date
                        'type' => 'payment',
                        'description' => $desc,
                        'debit' => 0,
                        'credit' => $cPay->amount,
                        'voucher_no' => $cPay->id,
                        'data' => $cPay
                    ]);
                }
                
                // Advances (Positive Only)
                $advances = \App\Models\CustomerAdvance::where('customer_id', $customerId)
                    ->where('amount', '>', 0)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->get();
                    
                foreach ($advances as $adv) {
                    $transactions->push([
                        'date' => $adv->created_at,
                        'type' => 'advance',
                        'description' => 'Advance Received',
                        'debit' => 0,
                        'credit' => $adv->amount,
                        'voucher_no' => 'ADV-' . $adv->id,
                        'data' => $adv
                    ]);
                }
                
                // 3. Sort and Calculate Running Balance
                // Sort by date timestamp to ensure correct chronological order, then by voucher_no for stability
                $sortedTransactions = $transactions->sort(function ($a, $b) {
                    $dateA = $a['date'] instanceof \Carbon\Carbon ? $a['date']->timestamp : strtotime($a['date']);
                    $dateB = $b['date'] instanceof \Carbon\Carbon ? $b['date']->timestamp : strtotime($b['date']);
                    
                    if ($dateA === $dateB) {
                        return strcmp($a['voucher_no'], $b['voucher_no']); // Secondary sort
                    }
                    return $dateA <=> $dateB;
                })->values();
                
                $runningBalance = 0; // Start from 0 because OPB row is included in sortedTransactions
                
                $finalData = $sortedTransactions->map(function ($item) use (&$runningBalance) {
                    // If it's the OPB row, the balance is already set, but for the loop we need to respect it
                    if ($item['type'] === 'opening_balance') {
                        $runningBalance = $item['balance'];
                    } else {
                        $runningBalance = $runningBalance + $item['debit'] - $item['credit'];
                        $item['balance'] = $runningBalance;
                    }
                    
                    // Format date for display
                    $item['formatted_date'] = $item['date'] instanceof \Carbon\Carbon ? $item['date']->format('Y-m-d') : Carbon::parse($item['date'])->format('Y-m-d');
                    
                    return $item;
                });

                // Handle PDF Export
                if ($request->has('export') && $request->export === 'pdf') {
                    $pdf = \PDF::loadView('reports.customers-pdf', [
                        'customer' => $customer,
                        'transactions' => $finalData,
                        'totals' => [
                            'debit' => $finalData->where('type', '!=', 'opening_balance')->sum('debit'),
                            'credit' => $finalData->where('type', '!=', 'opening_balance')->sum('credit'),
                            'closing_balance' => $runningBalance
                        ],
                        'dateFrom' => $dateFrom,
                        'dateTo' => $dateTo,
                        'company' => \App\Models\CompanySetting::first(),
                    ]);
                    return $pdf->download('customer_ledger_' . $customer->name . '.pdf');
                }

                return Inertia::render('Reports/Customers', [
                    'customers' => $customers,
                    'report' => [
                        'customer' => $customer,
                        'transactions' => $finalData,
                        'totals' => [
                            'debit' => $finalData->where('type', '!=', 'opening_balance')->sum('debit'),
                            'credit' => $finalData->where('type', '!=', 'opening_balance')->sum('credit'),
                            'closing_balance' => $runningBalance
                        ]
                    ],
                    'filters' => [
                        'customer_id' => $customerId,
                        'date_from' => $dateFrom->format('Y-m-d'),
                        'date_to' => $dateTo->format('Y-m-d'),
                    ]
                ]);
            }
        }

        return Inertia::render('Reports/Customers', [
            'customers' => $customers,
            'filters' => [
                'date_from' => Carbon::now()->subMonth()->format('Y-m-d'),
                'date_to' => Carbon::now()->format('Y-m-d'),
            ]
        ]);
    }

    /**
     * Export Customer Report as PDF
     */
    public function exportCustomersPDF(Request $request)
    {
        $customerId = $request->customer_id;
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subMonth()->startOfDay();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        
        if (!$customerId) {
            return back()->with('error', 'Please select a customer');
        }
        
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            return back()->with('error', 'Customer not found');
        }

        $transactions = collect();
        
        // 1. Calculate Opening Balance
        $salesBefore = Sale::where('customer_id', $customerId)
            ->where('sold_at', '<', $dateFrom)
            ->sum('bill_total');
            
        $returnsBefore = \App\Models\SaleReturn::whereHas('sale', function($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            })
            ->where('returned_at', '<', $dateFrom)
            ->sum('grand_total');
            
        $paymentsBefore = \App\Models\Payment::where('customer_id', $customerId)
            ->where('type', 'received')
            ->where('payment_date', '<', $dateFrom)
            ->sum('amount');
            
        $advancesBefore = \App\Models\CustomerAdvance::where('customer_id', $customerId)
            ->where('amount', '>', 0)
            ->where('created_at', '<', $dateFrom)
            ->sum('amount');
        
        $openingBalance = $salesBefore - $returnsBefore - $paymentsBefore - $advancesBefore;
        
        $transactions->push([
            'date' => $dateFrom->copy()->subSecond(),
            'type' => 'opening_balance',
            'description' => 'Opening Balance',
            'debit' => $openingBalance > 0 ? $openingBalance : 0,
            'credit' => $openingBalance < 0 ? abs($openingBalance) : 0,
            'balance' => $openingBalance,
            'formatted_date' => $dateFrom->format('Y-m-d'),
            'voucher_no' => 'B/F'
        ]);

        // 2. Get Current Transactions
        $sales = Sale::where('customer_id', $customerId)
            ->whereBetween('sold_at', [$dateFrom, $dateTo])
            ->get();
        
        $saleTimestamps = $sales->pluck('sold_at', 'id');

        foreach ($sales as $sale) {
            $isOpb = str_starts_with($sale->invoice_no, 'OPB-');
            $cashPaid = $sale->paid_amount - ($sale->advance_used ?? 0);
            $due = $sale->bill_total - $sale->paid_amount;
            
            $desc = $isOpb ? 'Opening Balance' : 'Sale Invoice';
            if (!$isOpb && $cashPaid > 0) {
                $desc .= " (Cash " . number_format($cashPaid, 0) . " / Due " . number_format($due, 0) . ")";
            }

            $transactions->push([
                'date' => $sale->sold_at,
                'type' => 'sale',
                'description' => $desc,
                'debit' => $sale->bill_total,
                'credit' => $cashPaid > 0 ? $cashPaid : 0, 
                'voucher_no' => $sale->invoice_no,
                'data' => $sale
            ]);
        }
        
        $returns = \App\Models\SaleReturn::whereHas('sale', function($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            })
            ->whereBetween('returned_at', [$dateFrom, $dateTo])
            ->get();
            
        foreach ($returns as $ret) {
            $transactions->push([
                'date' => $ret->returned_at,
                'type' => 'return',
                'description' => 'Sales Return',
                'debit' => 0,
                'credit' => $ret->grand_total,
                'voucher_no' => $ret->return_no ?? '-',
                'data' => $ret
            ]);
        }
        
        $payments = \App\Models\Payment::where('customer_id', $customerId)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->get();
            
        foreach ($payments as $pay) {
            if ($pay->sale_id && isset($saleTimestamps[$pay->sale_id])) {
                $saleTime = Carbon::parse($saleTimestamps[$pay->sale_id]);
                $payTime = Carbon::parse($pay->created_at);
                if ($payTime->diffInMinutes($saleTime) < 10) {
                    continue; 
                }
            }

            $isReceived = $pay->type === 'received';
            $desc = $isReceived ? 'Cash Received' : 'Cash Paid';
            if ($pay->note) $desc .= ' - ' . $pay->note;
            
            $sortDate = $pay->created_at->isSameDay($pay->payment_date) ? $pay->created_at : $pay->payment_date;

            $transactions->push([
                'date' => $sortDate,
                'type' => 'payment',
                'description' => $desc,
                'debit' => $isReceived ? 0 : $pay->amount,
                'credit' => $isReceived ? $pay->amount : 0,
                'voucher_no' => $pay->id,
                'data' => $pay
            ]);
        }
        
        $creditPayments = \App\Models\CustomerCreditPayment::where('customer_id', $customerId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->get();

        foreach ($creditPayments as $cPay) {
            $desc = 'Payment Received';
            if ($cPay->note) $desc .= ' - ' . $cPay->note;

            $transactions->push([
                'date' => $cPay->created_at,
                'type' => 'payment',
                'description' => $desc,
                'debit' => 0,
                'credit' => $cPay->amount,
                'voucher_no' => $cPay->id,
                'data' => $cPay
            ]);
        }

        $advances = \App\Models\CustomerAdvance::where('customer_id', $customerId)
            ->where('amount', '>', 0)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->get();
            
        foreach ($advances as $adv) {
            $transactions->push([
                'date' => $adv->created_at,
                'type' => 'advance',
                'description' => 'Advance Received',
                'debit' => 0,
                'credit' => $adv->amount,
                'voucher_no' => 'ADV-' . $adv->id,
                'data' => $adv
            ]);
        }

        $sortedTransactions = $transactions->sortBy('date')->values();
        
        $runningBalance = 0;
        
        $finalData = $sortedTransactions->map(function ($item) use (&$runningBalance) {
            if ($item['type'] === 'opening_balance') {
                $runningBalance = $item['balance'];
            } else {
                $runningBalance = $runningBalance + $item['debit'] - $item['credit'];
                $item['balance'] = $runningBalance;
            }
            if (!isset($item['formatted_date'])) {
                 $item['formatted_date'] = $item['date'] instanceof \Carbon\Carbon ? $item['date']->format('Y-m-d') : Carbon::parse($item['date'])->format('Y-m-d');
            }
            return $item;
        });

        $data = [
            'customer' => $customer,
            'transactions' => $finalData,
            'totals' => [
                'debit' => $finalData->where('type', '!=', 'opening_balance')->sum('debit'),
                'credit' => $finalData->where('type', '!=', 'opening_balance')->sum('credit'),
                'closing_balance' => $runningBalance
            ],
            'date_from' => $dateFrom->format('F j, Y'), 
            'date_to' => $dateTo->format('F j, Y'),
            'generated_at' => now()->format('F j, Y \a\t g:i A'),
            'company' => \App\Models\CompanySetting::first(),
        ];

        return view('reports.customers-pdf', $data);
    }

    /**
     * Export Customer Report as CSV
     */
    public function exportCustomersCSV(Request $request)
    {
        $customerId = $request->customer_id;
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subMonth()->startOfDay();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        
        if (!$customerId) {
            return back()->with('error', 'Please select a customer');
        }
        
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            return back()->with('error', 'Customer not found');
        }
        
        $ledger = [];
        
        // Calculate Opening Balance
        // First, get the OPB (Opening Balance) entry if it exists
        $opbSale = Sale::where('customer_id', $customerId)
            ->where('invoice_no', 'like', 'OPB-%')
            ->first();
        
        $opbAmount = $opbSale ? $opbSale->bill_total : 0;
        
        // Then get all previous transactions (excluding OPB as it's already counted)
        $prevSales = Sale::where('customer_id', $customerId)
            ->where('invoice_no', 'not like', 'OPB-%')
            ->where('created_at', '<', $dateFrom)
            ->sum('bill_total');
            
        $prevReturns = \App\Models\SaleReturn::whereHas('sale', function($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            })
            ->where('returned_at', '<', $dateFrom)
            ->sum('grand_total');
            
        $prevPayments = \App\Models\CustomerCreditPayment::where('customer_id', $customerId)
            ->where('created_at', '<', $dateFrom)
            ->sum('amount');
            
        $prevAdvances = \App\Models\CustomerAdvance::where('customer_id', $customerId)
            ->where('created_at', '<', $dateFrom)
            ->sum('amount');
        
        $openingBalance = $opbAmount + $prevSales - ($prevReturns + $prevPayments + $prevAdvances);
        $balance = $openingBalance;

        // Add Opening Balance Row
        $ledger[] = [
            'date' => $dateFrom->copy()->subSecond(),
            'description' => 'Opening Balance',
            'sale_amount' => 0,
            'payment' => 0,
            'advance' => 0,
            'return_amount' => 0,
            'balance' => $openingBalance
        ];
        
        // Get Current Transactions (excluding OPB sales)
        $sales = Sale::where('customer_id', $customerId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('invoice_no', 'not like', 'OPB-%') // Exclude Opening Balance entries
            ->orderBy('created_at')
            ->get();
        
        $returns = \App\Models\SaleReturn::whereHas('sale', function($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            })
            ->whereBetween('returned_at', [$dateFrom, $dateTo])
            ->with('sale')
            ->orderBy('returned_at')
            ->get();

        $advances = \App\Models\CustomerAdvance::where('customer_id', $customerId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at')
            ->get();
        
        $creditPayments = \App\Models\CustomerCreditPayment::where('customer_id', $customerId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at')
            ->get();
        
        // Merge and Sort Transactions
        $transactions = collect();
        
        foreach ($sales as $sale) {
            $transactions->push([
                'date' => $sale->created_at,
                'type' => 'sale',
                'data' => $sale
            ]);
        }
        
        foreach ($returns as $return) {
            $transactions->push([
                'date' => $return->returned_at,
                'type' => 'return',
                'data' => $return
            ]);
        }
        
        foreach ($advances as $advance) {
            $transactions->push([
                'date' => $advance->created_at,
                'type' => 'advance',
                'data' => $advance
            ]);
        }
        
        foreach ($creditPayments as $payment) {
            $transactions->push([
                'date' => $payment->created_at,
                'type' => 'payment',
                'data' => $payment
            ]);
        }
        
        $transactions = $transactions->sortBy('date');
        
        // Process Ledger Entries
        $totalSales = 0;
        $totalPayments = 0;
        
        foreach ($transactions as $transaction) {
            $entry = [
                'date' => $transaction['date'],
                'description' => '',
                'sale_amount' => 0,
                'payment' => 0,
                'advance' => 0,
                'return_amount' => 0,
                'balance' => 0
            ];
            
            if ($transaction['type'] === 'sale') {
                $sale = $transaction['data'];
                if ($sale->payment_type === 'cash') {
                    $entry['description'] = "Cash Sale Invoice #{$sale->invoice_no}";
                    $entry['sale_amount'] = $sale->bill_total;
                    $entry['payment'] = $sale->bill_total;
                    $totalSales += $sale->bill_total;
                    $totalPayments += $sale->bill_total;
                } else {
                    $entry['description'] = "Credit Sale Invoice #{$sale->invoice_no}";
                    $entry['sale_amount'] = $sale->bill_total;
                    $balance += $sale->bill_total;
                    $totalSales += $sale->bill_total;
                }
            } elseif ($transaction['type'] === 'return') {
                $return = $transaction['data'];
                $entry['description'] = "Sale Return (Invoice #{$return->sale->invoice_no})";
                $entry['return_amount'] = $return->grand_total;
                $balance -= $return->grand_total;
                $totalPayments += $return->grand_total;
            } elseif ($transaction['type'] === 'advance') {
                $advance = $transaction['data'];
                $entry['description'] = "Advance Payment";
                $entry['advance'] = $advance->amount;
                $balance -= $advance->amount;
                $totalPayments += $advance->amount;
            } elseif ($transaction['type'] === 'payment') {
                $payment = $transaction['data'];
                $entry['description'] = "Payment";
                $entry['payment'] = $payment->amount;
                $balance -= $payment->amount;
                $totalPayments += $payment->amount;
            }
            
            $entry['balance'] = $balance;
            $ledger[] = $entry;
        }

        // Build CSV with header information
        $csv = config('app.name', 'POS System') . " - Customer Report\n";
        $csv .= "Customer: {$customer->name}\n";
        $csv .= "Phone: " . ($customer->phone ?? 'N/A') . "\n";
        $csv .= "Period: {$dateFrom->format('F j, Y')} to {$dateTo->format('F j, Y')}\n";
        $csv .= "Generated: " . now()->format('F j, Y \a\t g:i A') . "\n";
        $csv .= "\n";
        
        // Add summary
        $csv .= "SUMMARY\n";
        $csv .= "Total Sales,Rs " . number_format($totalSales, 2) . "\n";
        $csv .= "Total Payments,Rs " . number_format($totalPayments, 2) . "\n";
        $csv .= "Current Balance,Rs " . number_format(abs($balance), 2) . " " . ($balance >= 0 ? '(Receivable)' : '(Advance)') . "\n";
        $csv .= "\n";
        
        // Add ledger header
        $csv .= "CUSTOMER LEDGER\n";
        $csv .= "Sr,Date,Description,Debit,Credit,Balance\n";
        
        // Add ledger entries
        foreach ($ledger as $index => $entry) {
            $credit = ($entry['payment'] ?? 0) + ($entry['advance'] ?? 0) + ($entry['return_amount'] ?? 0);
            
            $csv .= sprintf('%d,"%s","%s",%s,%s,%s' . "\n",
                $index + 1,
                Carbon::parse($entry['date'])->format('d M Y'),
                $entry['description'],
                $entry['sale_amount'] > 0 ? number_format($entry['sale_amount'], 2) : '0.00',
                $credit > 0 ? number_format($credit, 2) : '0.00',
                number_format(abs($entry['balance']), 2)
            );
        }

        $filename = "customer-report-{$customer->name}-{$dateFrom->format('Y-m-d')}.csv";

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Supplier Reports - Ledger View with Prepayment
     */
    public function suppliers(Request $request)
    {
        $suppliers = \App\Models\Supplier::orderBy('name')->get(['id', 'name', 'phone']);
        
        $report = null;
        
        if ($request->has('supplier_id') && $request->supplier_id) {
            $supplierId = $request->supplier_id;
            $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subMonth()->startOfDay();
            $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
            
            $supplier = \App\Models\Supplier::find($supplierId);
            
            if ($supplier) {
                $ledger = [];
                
                // 1. Calculate Opening Balance
                $prevPurchases = Purchase::where('supplier_id', $supplierId)
                    ->where('created_at', '<', $dateFrom)
                    ->sum('grand_total');
                    
                $prevPayments = \App\Models\PendingPayment::where('supplier_id', $supplierId)
                    ->where('created_at', '<', $dateFrom)
                    ->sum('amount');

                // New Ledger Payments (Previous)
                $prevNewPaymentsPaid = \App\Models\Payment::where('supplier_id', $supplierId)
                    ->where('type', 'paid')
                    ->where('payment_date', '<', $dateFrom)
                    ->sum('amount');

                $prevNewPaymentsReceived = \App\Models\Payment::where('supplier_id', $supplierId)
                    ->where('type', 'received')
                    ->where('payment_date', '<', $dateFrom)
                    ->sum('amount');
                
                // For suppliers, Balance = Purchases - Payments - New Paid + New Received
                $openingBalance = $prevPurchases - $prevPayments - $prevNewPaymentsPaid + $prevNewPaymentsReceived;
                $balance = $openingBalance;
                $prepaymentBalance = 0; // Reset for current period logic, or needs complex tracking
                
                // Add Opening Balance Row only if there is a balance
                if ($openingBalance != 0) {
                    $ledger[] = [
                        'date' => $dateFrom->copy()->subSecond(),
                        'description' => 'Opening Balance (B/F)',
                        'purchase_amount' => 0,
                        'payment' => 0,
                        'prepayment' => 0,
                        'balance' => $openingBalance
                    ];
                }
                
                // 2. Get Current Transactions
                $purchases = Purchase::where('supplier_id', $supplierId)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->orderBy('created_at')
                    ->get();
                
                $pendingPayments = \App\Models\PendingPayment::where('supplier_id', $supplierId)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->where('amount', '>', 0) // Only include actual payments, not liability records
                    ->orderBy('created_at')
                    ->get();

                // New Ledger Payments (Current)
                $newPayments = \App\Models\Payment::where('supplier_id', $supplierId)
                    ->whereBetween('payment_date', [$dateFrom, $dateTo])
                    ->orderBy('payment_date')
                    ->get();
                
                // 3. Combine and sort all transactions
                $transactions = collect();
                
                foreach ($purchases as $purchase) {
                    $transactions->push([
                        'date' => $purchase->created_at,
                        'type' => 'purchase',
                        'data' => $purchase
                    ]);
                }
                
                foreach ($pendingPayments as $payment) {
                    $transactions->push([
                        'date' => $payment->created_at,
                        'type' => 'payment',
                        'data' => $payment
                    ]);
                }

                foreach ($newPayments as $payment) {
                    // Use payment_date but add the time from created_at to preserve entry order
                    $paymentDate = Carbon::parse($payment->payment_date);
                    $sortDate = $paymentDate->copy()->setTime(
                        $payment->created_at->hour,
                        $payment->created_at->minute,
                        $payment->created_at->second
                    );

                    $transactions->push([
                        'date' => $sortDate,
                        'type' => 'new_payment',
                        'data' => $payment
                    ]);
                }
                
                $transactions = $transactions->sortBy('date');
                
                // 4. Process Ledger Entries
                foreach ($transactions as $transaction) {
                    $entry = [
                        'date' => $transaction['date'],
                        'description' => '',
                        'purchase_amount' => 0,
                        'payment' => 0,
                        'prepayment' => 0,
                        'balance' => 0
                    ];
                    
                    if ($transaction['type'] === 'purchase') {
                        $purchase = $transaction['data'];
                        
                        // Check if it's an opening balance dummy purchase
                        if (str_starts_with($purchase->purchase_no, 'OPB-') || $purchase->notes === 'Opening Balance') {
                            $entry['description'] = 'Opening Balance';
                        } else {
                            $entry['description'] = 'Purchase Invoice #' . $purchase->purchase_no . ' (goods)';
                        }
                        
                        $entry['purchase_amount'] = $purchase->grand_total;
                        
                        // Check if prepayment was used
                        if ($prepaymentBalance < 0) {
                            $prepaymentUsed = min(abs($prepaymentBalance), $purchase->grand_total);
                            $entry['prepayment'] = -$prepaymentUsed; // Negative means prepayment was used
                            $prepaymentBalance += $prepaymentUsed;
                            $balance += ($purchase->grand_total - $prepaymentUsed);
                        } else {
                            $balance += $purchase->grand_total;
                        }
                    } elseif ($transaction['type'] === 'payment') {
                        $payment = $transaction['data'];
                        
                        if ($payment->is_prepayment) {
                            $entry['description'] = 'Prepayment for future order';
                            $entry['prepayment'] = $payment->amount;
                            $prepaymentBalance -= $payment->amount; // Negative balance = we have prepaid
                            $balance -= $payment->amount;
                        } else {
                            $entry['description'] = 'Payment against invoice';
                            $entry['payment'] = $payment->amount;
                            $balance -= $payment->amount;
                        }
                    } elseif ($transaction['type'] === 'new_payment') {
                        $payment = $transaction['data'];
                        if ($payment->type === 'paid') {
                            $entry['description'] = "Cash Paid (Voucher)" . ($payment->note ? " - {$payment->note}" : "");
                            $entry['payment'] = $payment->amount;
                            $balance -= $payment->amount;
                        } else {
                            $entry['description'] = "Cash Received (Voucher)" . ($payment->note ? " - {$payment->note}" : "");
                            $entry['purchase_amount'] = $payment->amount; // Treat as credit/purchase
                            $balance += $payment->amount;
                        }
                    }
                    
                    $entry['balance'] = $balance;
                    $ledger[] = $entry;
                }
                
                $report = [
                    'supplier' => $supplier,
                    'ledger' => $ledger,
                    'summary' => [
                        'total_purchases' => $purchases->sum('grand_total'),
                        'total_payments' => $pendingPayments->where('is_prepayment', false)->sum('amount'),
                        'total_prepayments' => $pendingPayments->where('is_prepayment', true)->sum('amount'),
                        'balance' => $balance
                    ]
                ];
            }
        }
        
        return Inertia::render('Reports/Suppliers', [
            'suppliers' => $suppliers,
            'report' => $report
        ]);
    }

    /**
     * Export Supplier Report as PDF
     */
    public function exportSuppliersPDF(Request $request)
    {
        $supplierId = $request->supplier_id;
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subMonth()->startOfDay();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        
        if (!$supplierId) {
            return back()->with('error', 'Please select a supplier');
        }
        
        $supplier = \App\Models\Supplier::find($supplierId);
        
        if (!$supplier) {
            return back()->with('error', 'Supplier not found');
        }
        
        $ledger = [];
        
        // Calculate Opening Balance
        $prevPurchases = Purchase::where('supplier_id', $supplierId)
            ->where('created_at', '<', $dateFrom)
            ->sum('grand_total');
            
        $prevPayments = \App\Models\PendingPayment::where('supplier_id', $supplierId)
            ->where('created_at', '<', $dateFrom)
            ->sum('amount');
        
        $openingBalance = $prevPurchases - $prevPayments;
        $balance = $openingBalance;

        // Add Opening Balance Row
        if ($openingBalance != 0) {
            $ledger[] = [
                'date' => $dateFrom->copy()->subSecond(),
                'description' => 'Opening Balance (B/F)',
                'purchase_amount' => 0,
                'payment' => 0,
                'prepayment' => 0,
                'balance' => $openingBalance
            ];
        }
        
        // Get Current Transactions
        $purchases = Purchase::where('supplier_id', $supplierId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at')
            ->get();
        
        $pendingPayments = \App\Models\PendingPayment::where('supplier_id', $supplierId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at')
            ->get();
        
        // Merge and Sort Transactions
        $transactions = collect();
        
        foreach ($purchases as $purchase) {
            $transactions->push([
                'date' => $purchase->created_at,
                'type' => 'purchase',
                'data' => $purchase
            ]);
        }
        
        foreach ($pendingPayments as $payment) {
            $transactions->push([
                'date' => $payment->created_at,
                'type' => 'payment',
                'data' => $payment
            ]);
        }
        
        $transactions = $transactions->sortBy('date');
        
        // Process Ledger Entries
        $totalPurchases = 0;
        $totalPayments = 0;
        $prepaymentBalance = 0;
        
        foreach ($transactions as $transaction) {
            $entry = [
                'date' => $transaction['date'],
                'description' => '',
                'purchase_amount' => 0,
                'payment' => 0,
                'prepayment' => 0,
                'balance' => 0
            ];
            
            if ($transaction['type'] === 'purchase') {
                $purchase = $transaction['data'];
                
                if (str_starts_with($purchase->purchase_no, 'OPB-') || $purchase->notes === 'Opening Balance') {
                    $entry['description'] = 'Opening Balance';
                } else {
                    $entry['description'] = "Purchase Invoice #{$purchase->purchase_no}";
                }
                
                $entry['purchase_amount'] = $purchase->grand_total;
                $balance += $purchase->grand_total;
                $totalPurchases += $purchase->grand_total;
            } elseif ($transaction['type'] === 'payment') {
                $payment = $transaction['data'];
                
                if ($payment->is_prepayment) {
                    $entry['description'] = "Prepayment for future order";
                    $entry['prepayment'] = $payment->amount;
                    $balance -= $payment->amount;
                } else {
                    $entry['description'] = "Payment against invoice";
                    $entry['payment'] = $payment->amount;
                    $balance -= $payment->amount;
                }
                $totalPayments += $payment->amount;
            }
            
            $entry['balance'] = $balance;
            $ledger[] = $entry;
        }

        $data = [
            'supplier' => $supplier,
            'ledger' => $ledger,
            'summary' => [
                'total_purchases' => $totalPurchases,
                'total_payments' => $totalPayments,
                'balance' => $balance
            ],
            'date_from' => $dateFrom->format('F j, Y'),
            'date_to' => $dateTo->format('F j, Y'),
            'generated_at' => now()->format('F j, Y \a\t g:i A')
        ];

        return view('reports.suppliers-pdf', $data);
    }

    /**
     * Export Supplier Report as CSV
     */
    public function exportSuppliersCSV(Request $request)
    {
        $supplierId = $request->supplier_id;
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subMonth()->startOfDay();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        
        if (!$supplierId) {
            return back()->with('error', 'Please select a supplier');
        }
        
        $supplier = \App\Models\Supplier::find($supplierId);
        
        if (!$supplier) {
            return back()->with('error', 'Supplier not found');
        }
        
        $ledger = [];
        
        // Calculate Opening Balance
        $prevPurchases = Purchase::where('supplier_id', $supplierId)
            ->where('created_at', '<', $dateFrom)
            ->sum('grand_total');
            
        $prevPayments = \App\Models\PendingPayment::where('supplier_id', $supplierId)
            ->where('created_at', '<', $dateFrom)
            ->sum('amount');
        
        $openingBalance = $prevPurchases - $prevPayments;
        $balance = $openingBalance;

        // Add Opening Balance Row
        if ($openingBalance != 0) {
            $ledger[] = [
                'date' => $dateFrom->copy()->subSecond(),
                'description' => 'Opening Balance (B/F)',
                'purchase_amount' => 0,
                'payment' => 0,
                'prepayment' => 0,
                'balance' => $openingBalance
            ];
        }
        
        // Get Current Transactions
        $purchases = Purchase::where('supplier_id', $supplierId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at')
            ->get();
        
        $pendingPayments = \App\Models\PendingPayment::where('supplier_id', $supplierId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at')
            ->get();
        
        // Merge and Sort Transactions
        $transactions = collect();
        
        foreach ($purchases as $purchase) {
            $transactions->push([
                'date' => $purchase->created_at,
                'type' => 'purchase',
                'data' => $purchase
            ]);
        }
        
        foreach ($pendingPayments as $payment) {
            $transactions->push([
                'date' => $payment->created_at,
                'type' => 'payment',
                'data' => $payment
            ]);
        }
        
        $transactions = $transactions->sortBy('date');
        
        // Process Ledger Entries
        $totalPurchases = 0;
        $totalPayments = 0;
        
        foreach ($transactions as $transaction) {
            $entry = [
                'date' => $transaction['date'],
                'description' => '',
                'purchase_amount' => 0,
                'payment' => 0,
                'prepayment' => 0,
                'balance' => 0
            ];
            
            if ($transaction['type'] === 'purchase') {
                $purchase = $transaction['data'];
                
                if (str_starts_with($purchase->purchase_no, 'OPB-') || $purchase->notes === 'Opening Balance') {
                    $entry['description'] = 'Opening Balance';
                } else {
                    $entry['description'] = "Purchase Invoice #{$purchase->purchase_no}";
                }
                
                $entry['purchase_amount'] = $purchase->grand_total;
                $balance += $purchase->grand_total;
                $totalPurchases += $purchase->grand_total;
            } elseif ($transaction['type'] === 'payment') {
                $payment = $transaction['data'];
                
                if ($payment->is_prepayment) {
                    $entry['description'] = "Prepayment for future order";
                    $entry['prepayment'] = $payment->amount;
                    $balance -= $payment->amount;
                } else {
                    $entry['description'] = "Payment against invoice";
                    $entry['payment'] = $payment->amount;
                    $balance -= $payment->amount;
                }
                $totalPayments += $payment->amount;
            }
            
            $entry['balance'] = $balance;
            $ledger[] = $entry;
        }

        // Build CSV with header information
        $csv = config('app.name', 'POS System') . " - Supplier Report\n";
        $csv .= "Supplier: {$supplier->name}\n";
        $csv .= "Phone: " . ($supplier->phone ?? 'N/A') . "\n";
        $csv .= "Period: {$dateFrom->format('F j, Y')} to {$dateTo->format('F j, Y')}\n";
        $csv .= "Generated: " . now()->format('F j, Y \a\t g:i A') . "\n";
        $csv .= "\n";
        
        // Add summary
        $csv .= "SUMMARY\n";
        $csv .= "Total Purchases,Rs " . number_format($totalPurchases, 2) . "\n";
        $csv .= "Total Payments,Rs " . number_format($totalPayments, 2) . "\n";
        $csv .= "Current Balance,Rs " . number_format(abs($balance), 2) . " " . ($balance >= 0 ? '(Payable)' : '(Prepayment)') . "\n";
        $csv .= "\n";
        
        // Add ledger header
        $csv .= "SUPPLIER LEDGER\n";
        $csv .= "Sr,Date,Description,Debit,Credit,Balance\n";
        
        // Add ledger entries
        foreach ($ledger as $index => $entry) {
            $credit = ($entry['payment'] ?? 0) + ($entry['prepayment'] ?? 0);
            
            $csv .= sprintf('%d,"%s","%s",%s,%s,%s' . "\n",
                $index + 1,
                Carbon::parse($entry['date'])->format('d M Y'),
                $entry['description'],
                $credit > 0 ? number_format($credit, 2) : '0.00',
                $entry['purchase_amount'] > 0 ? number_format($entry['purchase_amount'], 2) : '0.00',
                number_format(abs($entry['balance']), 2)
            );
        }

        $filename = "supplier-report-{$supplier->name}-{$dateFrom->format('Y-m-d')}.csv";

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Stock Report
     */
    public function stock(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $format = $request->input('format');

        $query = Product::with(['category', 'unit', 'panaflexSpec']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Get products
        $products = $query->get()->map(function ($product) use ($startDate, $endDate) {
            // Calculate sold quantity in period
            $soldQty = SaleItem::where('product_id', $product->id)
                ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('sold_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ]);
                })
                ->sum('quantity'); // Note: For Panaflex this sums 'quantity' (pieces), not area. 
                                   // If we want area sold, we should sum 'units_sqft' for panaflex.
            
            // Let's refine sold qty logic
            if ($product->type === 'panaflex_roll') {
                $soldArea = SaleItem::where('product_id', $product->id)
                    ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('sold_at', [
                            Carbon::parse($startDate)->startOfDay(),
                            Carbon::parse($endDate)->endOfDay()
                        ]);
                    })
                    ->sum('units_sqft');
                $product->sold_qty_period = $soldArea; // In SqFt
            } else {
                $product->sold_qty_period = $soldQty;
            }

            // Calculate Stock Values
            // For Panaflex, stock_meters is the physical stock. 
            // Value is usually based on Purchase Rate per Unit.
            // If Purchase Rate is per SqFt (for Panaflex), we need to convert stock meters to SqFt first?
            // Or is Purchase Rate per Meter?
            // Usually Purchase Rate for Panaflex is per SqFt or per Roll?
            // Let's assume Purchase Rate is per Unit defined in Product.
            
            // For Panaflex, current_stock attribute returns SqFt.
            // Let's use current_stock for value calculation if rate is per SqFt.
            // If rate is per Meter, we use stock_meters.
            // Standard practice: Rate is per Unit.
            
            $stockQty = $product->current_stock; // SqFt for Panaflex, Qty for Simple
            
            $product->stock_value_cost = $stockQty * $product->purchase_rate;
            $product->stock_value_sale = $stockQty * $product->sale_rate;
            
            return $product;
        });

        if ($format === 'excel') {
            return Excel::download(new StockReportExport($products), 'stock-report.xlsx');
        }

        if ($format === 'pdf') {
            $data = [
                'title' => 'Stock Report',
                'start_date' => Carbon::parse($startDate)->format('F j, Y'),
                'end_date' => Carbon::parse($endDate)->format('F j, Y'),
                'generated_at' => now()->format('F j, Y \a\t g:i A'),
                'products' => $products,
                'totals' => [
                    'total_cost_value' => $products->sum('stock_value_cost'),
                    'total_sale_value' => $products->sum('stock_value_sale'),
                    'total_items' => $products->count(),
                ]
            ];
            return view('reports.stock-pdf', $data);
        }

        if ($format === 'csv') {
            return Excel::download(new StockReportExport($products), 'stock-report.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return Inertia::render('Reports/Stock', [
            'products' => $products,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'category_id' => $request->category_id,
                'search' => $request->search,
            ],
            'categories' => \App\Models\Category::all(['id', 'name']),
            'totals' => [
                'total_cost_value' => $products->sum('stock_value_cost'),
                'total_sale_value' => $products->sum('stock_value_sale'),
                'total_items' => $products->count(),
            ]
        ]);
    }

    /**
     * All Parties Ledger Report
     */
    public function allPartiesLedger(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfDay()->toDateString());
        $export = $request->boolean('export');

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        $allTransactions = collect();
        $partyBalances = []; // To track running balance per party

        // --- 1. Customers ---
        $customers = Customer::all();
        foreach ($customers as $customer) {
            // A. Calculate Initial Opening Balance (Before Start Date)
            // We reconstruct the balance purely from transactions to ensure accuracy.
            // Balance = (Sum of Bill Totals) - (Sum of Returns) - (Sum of Payments Received) + (Sum of Payments Paid) - (Sum of Advances)
            
            $salesBefore = $customer->sales()->where('sold_at', '<', $start)->sum('bill_total');
            $returnsBefore = $customer->returns()->where('sale_returns.returned_at', '<', $start)->sum('sale_returns.grand_total');
            $payRecBefore = $customer->payments()->where('type', 'received')->where('payment_date', '<', $start)->sum('amount');
            $payPaidBefore = $customer->payments()->where('type', 'paid')->where('payment_date', '<', $start)->sum('amount');
            
            // Advances (Positive Only - Money Received)
            $advancesBefore = \App\Models\CustomerAdvance::where('customer_id', $customer->id)
                ->where('amount', '>', 0)
                ->where('created_at', '<', $start)
                ->sum('amount');

            // Note: We ignore the 'opening_balance' field and 'pending_payments' table 
            // because the OPB should exist as a Sale record (OPB-xxx) which is included in $salesBefore.
            
            $initialBalance = $salesBefore - $returnsBefore - $payRecBefore + $payPaidBefore - $advancesBefore;
            
            // Store initial balance
            $partyKey = 'c_' . $customer->id;
            $partyBalances[$partyKey] = $initialBalance;

            // If there is an Opening Balance, add it as a row
            if ($initialBalance != 0) {
                $allTransactions->push([
                    'date' => $start->format('Y-m-d'),
                    'raw_date' => $start->copy()->subSecond(),
                    'voucher_no' => 'B/F',
                    'party_name' => $customer->name,
                    'party_type' => 'customer',
                    'party_id' => $customer->id,
                    'description' => 'Balance Brought Forward',
                    'debit' => $initialBalance > 0 ? $initialBalance : 0,
                    'credit' => $initialBalance < 0 ? abs($initialBalance) : 0,
                    'balance' => $initialBalance, 
                    'type' => 'opening_balance'
                ]);
            }

            // B. Transactions During Period
            // Sales
            // We INCLUDE OPB records here if they fall within the date range, 
            // as they represent the starting debt for that period if not already covered by B/F.
            $sales = $customer->sales()
                ->whereBetween('sold_at', [$start, $end])
                ->get();
            
            // Map sale IDs to their timestamps for filtering duplicate payments
            $saleTimestamps = $sales->pluck('sold_at', 'id');
                
            foreach ($sales as $sale) {
                $isOpb = str_starts_with($sale->invoice_no, 'OPB-');
                
                // Calculate Cash Paid part (Total Paid - Advance Used)
                $cashPaid = $sale->paid_amount - ($sale->advance_used ?? 0);
                $due = $sale->bill_total - $sale->paid_amount;
                
                // Format Description
                $desc = $isOpb ? 'Opening Balance' : 'Sale Invoice';
                if (!$isOpb && $cashPaid > 0) {
                    $desc .= " (Cash " . number_format($cashPaid, 0) . " / Due " . number_format($due, 0) . ")";
                }

                $allTransactions->push([
                    'date' => $sale->sold_at->format('Y-m-d'),
                    'raw_date' => $sale->sold_at,
                    'voucher_no' => $sale->invoice_no,
                    'party_name' => $customer->name,
                    'party_key' => $partyKey,
                    'description' => $desc,
                    'debit' => $sale->bill_total, // Use bill_total (actual sale amount), NOT grand_total (running balance)
                    'credit' => $cashPaid > 0 ? $cashPaid : 0,
                    'type' => 'sale'
                ]);
            }

            // Returns
            $returns = $customer->returns()->whereBetween('sale_returns.returned_at', [$start, $end])->get();
            foreach ($returns as $ret) {
                $allTransactions->push([
                    'date' => $ret->returned_at->format('Y-m-d'),
                    'raw_date' => $ret->returned_at,
                    'voucher_no' => $ret->return_no ?? '-',
                    'party_name' => $customer->name,
                    'party_key' => $partyKey,
                    'description' => 'Sales Return',
                    'debit' => 0,
                    'credit' => $ret->grand_total,
                    'type' => 'return'
                ]);
            }

            // Payments
            $payments = $customer->payments()->whereBetween('payment_date', [$start, $end])->get();
            foreach ($payments as $pay) {
                // Skip if this is an initial payment for a sale we already processed
                if ($pay->sale_id && isset($saleTimestamps[$pay->sale_id])) {
                    $saleTime = \Carbon\Carbon::parse($saleTimestamps[$pay->sale_id]);
                    $payTime = \Carbon\Carbon::parse($pay->created_at); 
                    
                    if ($payTime->diffInMinutes($saleTime) < 10) {
                        continue; 
                    }
                }

                $isReceived = $pay->type === 'received';
                $desc = $isReceived ? 'Cash Received' : 'Cash Paid';
                if ($pay->note) {
                    $desc .= ' - ' . $pay->note;
                }

                // Use created_at for sorting if the payment date matches the creation date (Real-time entry)
                $sortDate = $pay->created_at->isSameDay($pay->payment_date) ? $pay->created_at : $pay->payment_date;

                $allTransactions->push([
                    'date' => $pay->payment_date->format('Y-m-d'),
                    'raw_date' => $sortDate,
                    'voucher_no' => $pay->id, // Or payment ref
                    'party_name' => $customer->name,
                    'party_key' => $partyKey,
                    'description' => $desc,
                    'debit' => $isReceived ? 0 : $pay->amount,
                    'credit' => $isReceived ? $pay->amount : 0,
                    'type' => 'payment'
                ]);
            }
            
            // Advances
            $advances = \App\Models\CustomerAdvance::where('customer_id', $customer->id)
                ->where('amount', '>', 0)
                ->whereBetween('created_at', [$start, $end])
                ->get();
                
            foreach ($advances as $adv) {
                $allTransactions->push([
                    'date' => $adv->created_at->format('Y-m-d'),
                    'raw_date' => $adv->created_at,
                    'voucher_no' => 'ADV-' . $adv->id,
                    'party_name' => $customer->name,
                    'party_key' => $partyKey,
                    'description' => 'Advance Received',
                    'debit' => 0,
                    'credit' => $adv->amount,
                    'type' => 'advance'
                ]);
            }
        }

        // --- 2. Suppliers ---
        $suppliers = \App\Models\Supplier::all();
        foreach ($suppliers as $supplier) {
            // A. Initial Balance
            // Reconstruct from transactions: Purchases (Credit) - Payments (Debit)
            // Note: We assume Purchase grand_total is the Bill Amount (not running balance).
            // If Purchase grand_total is also a running balance, we would need to fix this too.
            // Usually Purchase models are simpler. Let's assume grand_total is correct for now, 
            // but we should verify if 'bill_total' exists for purchases.
            // Checking Purchase model... it usually has grand_total as the bill amount.
            
            $purchasesBefore = $supplier->purchases()
                ->where('purchased_at', '<', $start)
                ->where('status', '!=', 'cancelled')
                ->sum('grand_total');
                
            $payPaidBefore = $supplier->payments()->where('type', 'paid')->where('payment_date', '<', $start)->sum('amount');
            $payRecBefore = $supplier->payments()->where('type', 'received')->where('payment_date', '<', $start)->sum('amount');

            // Net Payable = Purchases - Payments
            // If Positive: We Owe (Credit Balance in our Ledger logic where Bal = Dr - Cr, so Negative)
            // Wait, earlier logic: Bal = Dr - Cr.
            // Purchase = Credit. Payment = Debit.
            // Bal = Payment - Purchase.
            // If Purchase 100, Payment 0. Bal = -100. (We owe 100).
            // So Initial Balance = (Payments - Purchases).
            
            $initialBalance = ($payPaidBefore - $payRecBefore) - $purchasesBefore;

            $partyKey = 's_' . $supplier->id;
            $partyBalances[$partyKey] = $initialBalance;

            if ($initialBalance != 0) {
                 $allTransactions->push([
                    'date' => $start->format('Y-m-d'),
                    'raw_date' => $start->subSecond(),
                    'voucher_no' => 'B/F',
                    'party_name' => $supplier->name,
                    'party_type' => 'supplier',
                    'party_id' => $supplier->id,
                    'description' => 'Balance Brought Forward',
                    'debit' => $initialBalance > 0 ? $initialBalance : 0, 
                    'credit' => $initialBalance < 0 ? abs($initialBalance) : 0,
                    'balance' => $initialBalance,
                    'type' => 'opening_balance'
                ]);
            }
            
            // B. Transactions
            // Purchases (Credit)
            $purchases = $supplier->purchases()
                ->whereBetween('purchased_at', [$start, $end])
                ->where('status', '!=', 'cancelled')
                ->get();
            foreach ($purchases as $pur) {
                $isOpb = str_starts_with($pur->purchase_no, 'OPB-');
                $allTransactions->push([
                    'date' => $pur->purchased_at->format('Y-m-d'),
                    'raw_date' => $pur->purchased_at,
                    'voucher_no' => $pur->purchase_no,
                    'party_name' => $supplier->name,
                    'party_key' => $partyKey,
                    'description' => $isOpb ? 'Opening Balance' : 'Purchase Invoice',
                    'debit' => 0,
                    'credit' => $pur->grand_total,
                    'type' => 'purchase'
                ]);
            }

            // Payments (Debit)
            $payments = $supplier->payments()->whereBetween('payment_date', [$start, $end])->get();
            foreach ($payments as $pay) {
                $isPaid = $pay->type === 'paid'; // We paid supplier -> Debit Supplier
                
                // Use created_at for sorting if the payment date matches the creation date (Real-time entry)
                $sortDate = $pay->created_at->isSameDay($pay->payment_date) ? $pay->created_at : $pay->payment_date;

                $allTransactions->push([
                    'date' => $pay->payment_date->format('Y-m-d'),
                    'raw_date' => $sortDate,
                    'voucher_no' => $pay->id,
                    'party_name' => $supplier->name,
                    'party_key' => $partyKey,
                    'description' => $isPaid ? 'Cash Payment' : 'Cash Received',
                    'debit' => $isPaid ? $pay->amount : 0,
                    'credit' => $isPaid ? 0 : $pay->amount,
                    'type' => 'payment'
                ]);
            }
        }

        // --- 3. Sort & Calculate Running Balances ---
        // Sort by Date, then opening balance types first
        $sortedTransactions = $allTransactions->sortBy(function ($item) {
             return $item['raw_date'];
        })->values();

        // Calculate Balance Column
        // Use initial Party Balances as the starting point.
        // Wait, we already added OPB rows with the initial balance.
        // So if we start running balance from 0 and just add Dr - Cr, it should work IF the OPB row is included.
        // In my code above, I added OPB row with Dr/Cr.
        // Example: Customer OPB 100 (Dr).
        // Row 1: OPB, Dr 100, Cr 0.
        // Running Bal = 0 + 100 - 0 = 100. Correct.
        // Example: Supplier OPB 100 (Cr).
        // Row 1: OPB, Dr 0, Cr 100.
        // Running Bal = 0 + 0 - 100 = -100. Correct.
        
        // So we need to reset running balances to 0 for the loop, because the OPB row itself provides the starting value.
        // BUT, what if there is no OPB row (balance was 0)? Then running balance starts at 0. Correct.
        // What if we are on Page 2? The "Opening Balance" row is only added if it's the very first transaction?
        // No, the OPB row I added is "Opening Balance B/F" calculated up to $startDate.
        // So it acts as the carry forward.
        
        // However, we need to track balance PER PARTY.
        $runningBalances = []; 

        $finalData = $sortedTransactions->map(function ($row) use (&$runningBalances) {
            $key = $row['party_key'] ?? ($row['party_type'] == 'customer' ? 'c_'.$row['party_id'] : 's_'.$row['party_id']);
            
            if (!isset($runningBalances[$key])) {
                $runningBalances[$key] = 0;
            }

            // Update Balance
            // Formula: Bal = Bal + Dr - Cr
            $runningBalances[$key] = $runningBalances[$key] + $row['debit'] - $row['credit'];

            $row['balance'] = $runningBalances[$key];
            unset($row['raw_date']); // clean up
            unset($row['party_key']);
            return $row;
        });

        // FILTER: Remove Opening Balance (B/F) rows from the final display list as per user request
        $finalData = $finalData->filter(function($row) {
            return $row['voucher_no'] !== 'B/F';
        })->values();

        // Calculate Totals
        $totalOpeningBalance = 0;
        foreach ($partyBalances as $bal) {
            $totalOpeningBalance += $bal;
        }
        
        // Filter out opening balance rows for transaction totals
        $transactionRows = $finalData->where('type', '!=', 'opening_balance');
        $totalDebit = $transactionRows->sum('debit');
        $totalCredit = $transactionRows->sum('credit');
        
        // Calculate Total Received and Paid (for Cash Flow analysis)
        $totalReceived = $transactionRows->filter(function($row) {
            // Received includes: 
            // 1. Payments received (type='payment', credit > 0)
            // 2. Advances received (type='advance')
            // 3. Cash Sales (type='sale', credit > 0)
            return in_array($row['type'], ['payment', 'advance']) || ($row['type'] === 'sale' && $row['credit'] > 0);
        })->sum('credit');

        $totalPaid = $transactionRows->filter(function($row) {
            // Paid includes:
            // 1. Payments made to suppliers or refunds to customers (type='payment', debit > 0)
            return $row['type'] === 'payment' && $row['debit'] > 0;
        })->sum('debit');

        // Closing Balance = Opening + (Transaction Debits - Transaction Credits)
        $totalClosingBalance = $totalOpeningBalance + $totalDebit - $totalCredit;

        // Handle Exports
        $format = $request->input('format');
        if ($format) {
            $filename = 'all_parties_ledger_' . date('Y-m-d_His');
            
            if ($format === 'pdf') {
                // Use Dompdf directly since wrapper might be missing
                $options = new \Dompdf\Options();
                $options->set('isRemoteEnabled', true);
                $options->set('defaultFont', 'Arial');
                
                $dompdf = new \Dompdf\Dompdf($options);
                $html = view('reports.all-parties-ledger-pdf', [
                    'transactions' => $finalData,
                    'totals' => [
                        'opening_balance' => $totalOpeningBalance,
                        'total_debit' => $totalDebit,
                        'total_credit' => $totalCredit,
                        'total_received' => $totalReceived,
                        'total_paid' => $totalPaid,
                        'closing_balance' => $totalClosingBalance,
                    ],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'company' => \App\Models\CompanySetting::first(),
                ])->render();
                
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape'); // Landscape for ledger
                $dompdf->render();
                
                return response($dompdf->output())
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"');
            }
            
            if ($format === 'excel') {
                return Excel::download(new \App\Exports\AllPartiesLedgerExport($finalData), $filename . '.xlsx');
            }
            
            if ($format === 'csv') {
                return Excel::download(new \App\Exports\AllPartiesLedgerExport($finalData), $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
            }
        }

        return Inertia::render('Reports/AllPartiesLedger', [
            'transactions' => $finalData,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'totals' => [
                'opening_balance' => $totalOpeningBalance,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'total_received' => $totalReceived,
                'total_paid' => $totalPaid,
                'closing_balance' => $totalClosingBalance,
            ]
        ]);
    }

    /**
     * Display receivables report (All Customers Balances)
     */
    public function receivables(Request $request)
    {
        $type = $request->input('type', 'all'); // all, receivable, advance
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // If no date provided, use current date
        $activeEndDate = $endDate ? $endDate : now()->format('Y-m-d');
        
        // Use eager loading with aggregate functions for performance
        // Calculation matches Customer Ledger Logic:
        // Balance = (Total Sales) - (Total Returns) - (Total Payments Received) - (Total Advances) + (Total Refunds)
        $query = Customer::query()
            ->withSum(['sales as total_sales' => function($q) use ($activeEndDate) {
                if ($activeEndDate) $q->whereDate('sold_at', '<=', $activeEndDate);
            }], 'bill_total')
            
            // For Returns, we need HasManyThrough sum. Laravel withSum supports this.
            // Note: Relationship is 'returns' (HasManyThrough)
            ->withSum(['returns as total_returns' => function($q) use ($activeEndDate) {
                if ($activeEndDate) $q->whereDate('sale_returns.returned_at', '<=', $activeEndDate);
            }], 'grand_total')
            
            ->withSum(['payments as total_received' => function($q) use ($activeEndDate) {
                $q->where('type', 'received');
                if ($activeEndDate) $q->whereDate('payment_date', '<=', $activeEndDate);
            }], 'amount')
            
            ->withSum(['payments as total_refunds' => function($q) use ($activeEndDate) {
                $q->where('type', 'paid');
                if ($activeEndDate) $q->whereDate('payment_date', '<=', $activeEndDate);
            }], 'amount')
            
            ->withSum(['advances as total_advances' => function($q) use ($activeEndDate) {
                $q->where('amount', '>', 0); // Ledger specific: only distinct advances
                if ($activeEndDate) $q->whereDate('created_at', '<=', $activeEndDate);
            }], 'amount');

        $customers = $query->get();
        
        $reportData = $customers->map(function ($customer) {
            // Calculate balance based on reconstructed aggregates
            
            $balance = ($customer->total_sales ?? 0) 
                     - ($customer->total_returns ?? 0) 
                     - ($customer->total_received ?? 0)
                     - ($customer->total_advances ?? 0)
                     + ($customer->total_refunds ?? 0);

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'balance' => $balance
            ];
        })->filter(function ($customer) use ($type) {
            // Remove zero balances (tolerance for float precision)
            if (abs($customer['balance']) < 1) return false;

            if ($type === 'receivable') return $customer['balance'] > 0;
            if ($type === 'advance') return $customer['balance'] < 0;
            
            return true;
        })->values();

        // Sort by balance descending (highest debt first)
        $reportData = $reportData->sortByDesc('balance')->values();

        return Inertia::render('Reports/Receivables', [
            'customers' => $reportData,
            'filters' => [
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
    }

    public function exportReceivablesPDF(Request $request)
    {
        $type = $request->input('type', 'all');
        $endDate = $request->input('end_date');
        $activeEndDate = $endDate ? $endDate : now()->format('Y-m-d');
        
        $query = Customer::query()
            ->withSum(['sales as total_sales' => function($q) use ($activeEndDate) {
                if ($activeEndDate) $q->whereDate('sold_at', '<=', $activeEndDate);
            }], 'bill_total')
            ->withSum(['returns as total_returns' => function($q) use ($activeEndDate) {
                if ($activeEndDate) $q->whereDate('sale_returns.returned_at', '<=', $activeEndDate);
            }], 'grand_total')
            ->withSum(['payments as total_received' => function($q) use ($activeEndDate) {
                $q->where('type', 'received');
                if ($activeEndDate) $q->whereDate('payment_date', '<=', $activeEndDate);
            }], 'amount')
            ->withSum(['payments as total_refunds' => function($q) use ($activeEndDate) {
                $q->where('type', 'paid');
                if ($activeEndDate) $q->whereDate('payment_date', '<=', $activeEndDate);
            }], 'amount')
            ->withSum(['advances as total_advances' => function($q) use ($activeEndDate) {
                $q->where('amount', '>', 0);
                if ($activeEndDate) $q->whereDate('created_at', '<=', $activeEndDate);
            }], 'amount');
            
        $customers = $query->get();
        
        $reportData = $customers->map(function ($customer) {
            $balance = ($customer->total_sales ?? 0) 
                     - ($customer->total_returns ?? 0) 
                     - ($customer->total_received ?? 0)
                     - ($customer->total_advances ?? 0)
                     + ($customer->total_refunds ?? 0);
                     
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'balance' => $balance
            ];
        })->filter(function ($customer) use ($type) {
            if (abs($customer['balance']) < 1) return false;
            if ($type === 'receivable') return $customer['balance'] > 0;
            if ($type === 'advance') return $customer['balance'] < 0;
            return true;
        })->sortByDesc('balance')->values();

        $data = [
            'customers' => $reportData,
            'type' => $type,
            'generated_at' => \Carbon\Carbon::parse($activeEndDate)->format('F j, Y'),
            'company' => \App\Models\CompanySetting::first(),
        ];
        
        // Use Dompdf directly 
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        
        $dompdf = new \Dompdf\Dompdf($options);
        $html = view('reports.receivables-pdf', $data)->render();
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait'); 
        $dompdf->render();
        
        return response($dompdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="receivables_report.pdf"');
    }
}

