<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Expense;
use App\Models\CustomerCreditPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard page
     */
    public function index()
    {
        return inertia('Dashboard');
    }
    
    /**
     * Get dashboard data
     */
    public function getData()
    {
        try {
            // Use precise start/end of day to handle timezones correctly with database UTC timestamps
            $todayStart = Carbon::now()->startOfDay();
            $todayEnd = Carbon::now()->endOfDay();
            
            $yesterdayStart = Carbon::yesterday()->startOfDay();
            $yesterdayEnd = Carbon::yesterday()->endOfDay();
            
            // 1. Net Sales Today (Sales - Returns)
            // Use bill_total to exclude previous balance
            $todaySalesTotal = Sale::whereBetween('created_at', [$todayStart, $todayEnd])
                ->where('invoice_no', 'not like', 'OPB-%')
                ->sum('bill_total');
            $todayReturnsTotal = SaleReturn::whereBetween('created_at', [$todayStart, $todayEnd])->sum('grand_total');
            $todayNetSales = $todaySalesTotal - $todayReturnsTotal;

            $yesterdaySalesTotal = Sale::whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
                ->where('invoice_no', 'not like', 'OPB-%')
                ->sum('bill_total');
            $yesterdayReturnsTotal = SaleReturn::whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])->sum('grand_total');
            $yesterdayNetSales = $yesterdaySalesTotal - $yesterdayReturnsTotal;
            
            $salesChange = $yesterdayNetSales > 0 
                ? (($todayNetSales - $yesterdayNetSales) / $yesterdayNetSales) * 100 
                : ($todayNetSales > 0 ? 100 : 0);
            
            // 2. Bank Transactions Today (Replaces Orders Today)
            $todayBankSales = Sale::whereBetween('created_at', [$todayStart, $todayEnd])
                ->where('invoice_no', 'not like', 'OPB-%')
                ->where('payment_type', 'bank')
                ->sum(DB::raw('paid_amount - advance_used'));
            
            $yesterdayBankSales = Sale::whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
                ->where('invoice_no', 'not like', 'OPB-%')
                ->where('payment_type', 'bank')
                ->sum(DB::raw('paid_amount - advance_used'));
            
            $bankChange = $yesterdayBankSales > 0 
                ? (($todayBankSales - $yesterdayBankSales) / $yesterdayBankSales) * 100 
                : ($todayBankSales > 0 ? 100 : 0);
            
            // 3. Cash Received Today (Paid Amount - Advance Used + Credit Payments)
            // We subtract advance_used because that money was received in the past, not today.
            $todayCashSales = Sale::whereBetween('created_at', [$todayStart, $todayEnd])
                ->where('invoice_no', 'not like', 'OPB-%')
                ->sum(DB::raw('paid_amount - advance_used'));
                
            $todayCreditPayments = CustomerCreditPayment::whereDate('payment_date', Carbon::today())->sum('amount');
            $todayCashReceived = $todayCashSales + $todayCreditPayments;

            $yesterdayCashSales = Sale::whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
                ->where('invoice_no', 'not like', 'OPB-%')
                ->sum(DB::raw('paid_amount - advance_used'));
                
            $yesterdayCreditPayments = CustomerCreditPayment::whereDate('payment_date', Carbon::yesterday())->sum('amount');
            $yesterdayCashReceived = $yesterdayCashSales + $yesterdayCreditPayments;

            $cashChange = $yesterdayCashReceived > 0
                ? (($todayCashReceived - $yesterdayCashReceived) / $yesterdayCashReceived) * 100
                : ($todayCashReceived > 0 ? 100 : 0);
            
            // 4. Expenses Today
            $todayExpenses = Expense::whereDate('date', Carbon::today())->sum('amount');
            $yesterdayExpenses = Expense::whereDate('date', Carbon::yesterday())->sum('amount');

            $expensesChange = $yesterdayExpenses > 0
                ? (($todayExpenses - $yesterdayExpenses) / $yesterdayExpenses) * 100
                : ($todayExpenses > 0 ? 100 : 0);

            // ========================================================
            // DAILY FINANCIAL KPIS: Cash In, Cash Out, Closing Balance, Profit
            // ========================================================
            // 1. Cash In Today (Customer payments received)
            $todayCashIn = Payment::where(function($q) {
                    $q->whereNotNull('customer_id')->orWhere('type', 'received');
                })
                ->where('type', 'received')
                ->whereDate('payment_date', Carbon::today())
                ->sum('amount');

            $yesterdayCashIn = Payment::where(function($q) {
                    $q->whereNotNull('customer_id')->orWhere('type', 'received');
                })
                ->where('type', 'received')
                ->whereDate('payment_date', Carbon::yesterday())
                ->sum('amount');

            $cashInChange = $yesterdayCashIn > 0
                ? (($todayCashIn - $yesterdayCashIn) / $yesterdayCashIn) * 100
                : ($todayCashIn > 0 ? 100 : 0);

            // 2. Cash Out Today (Supplier payments + Expenses)
            $todaySuppPaid = Payment::whereNotNull('supplier_id')
                ->where('type', 'paid')
                ->whereDate('payment_date', Carbon::today())
                ->sum('amount');
            $todayCashOut = $todaySuppPaid + $todayExpenses;

            $yesterdaySuppPaid = Payment::whereNotNull('supplier_id')
                ->where('type', 'paid')
                ->whereDate('payment_date', Carbon::yesterday())
                ->sum('amount');
            $yesterdayCashOut = $yesterdaySuppPaid + $yesterdayExpenses;

            $cashOutChange = $yesterdayCashOut > 0
                ? (($todayCashOut - $yesterdayCashOut) / $yesterdayCashOut) * 100
                : ($todayCashOut > 0 ? 100 : 0);

            // 3. Closing Balance Today (Net Cash in Hand = Cash In - Cash Out)
            $todayClosingBalance = $todayCashIn - $todayCashOut;
            $yesterdayClosingBalance = $yesterdayCashIn - $yesterdayCashOut;

            $closingChange = $yesterdayClosingBalance != 0
                ? (($todayClosingBalance - $yesterdayClosingBalance) / abs($yesterdayClosingBalance)) * 100
                : ($todayClosingBalance != 0 ? 100 : 0);

            // 4. Profit Today (Net Profit = Sales - COGS - Expenses)
            $todayCogs = DB::table('sale_items as si')
                ->join('sales as s', 's.id', '=', 'si.sale_id')
                ->join('products as p', 'p.id', '=', 'si.product_id')
                ->where('s.invoice_no', 'not like', 'OPB-%')
                ->whereBetween('s.created_at', [$todayStart, $todayEnd])
                ->sum(DB::raw('COALESCE(si.units_sqft, si.quantity) * COALESCE(p.purchase_rate, 0)'));

            $todayProfit = $todayNetSales - $todayCogs - $todayExpenses;

            $yesterdayCogs = DB::table('sale_items as si')
                ->join('sales as s', 's.id', '=', 'si.sale_id')
                ->join('products as p', 'p.id', '=', 'si.product_id')
                ->where('s.invoice_no', 'not like', 'OPB-%')
                ->whereBetween('s.created_at', [$yesterdayStart, $yesterdayEnd])
                ->sum(DB::raw('COALESCE(si.units_sqft, si.quantity) * COALESCE(p.purchase_rate, 0)'));

            $yesterdayProfit = $yesterdayNetSales - $yesterdayCogs - $yesterdayExpenses;

            $profitChange = $yesterdayProfit != 0
                ? (($todayProfit - $yesterdayProfit) / abs($yesterdayProfit)) * 100
                : ($todayProfit != 0 ? 100 : 0);
            
            // Get recent transactions
            $recentTransactions = Sale::with(['customer'])
                ->where('invoice_no', 'not like', 'OPB-%')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'customer' => $sale->customer ? $sale->customer->name : 'Walk-in Customer',
                        'amount' => $sale->grand_total,
                        'time' => $sale->created_at->diffForHumans(),
                        'status' => 'completed' // You can add status field to sales table if needed
                    ];
                });
            
            // Get low stock items - only count products that have min_qty set and are below threshold
            $lowStockItems = Product::where('stock_quantity', '<=', DB::raw('min_qty'))
                ->where('min_qty', '>', 0)
                ->where('active', true)
                ->count();
                
            // Don't use arbitrary fallback - only show actual low stock items with proper thresholds
            
            // Get sales chart data (last 7 days)
            $salesChartData = $this->getSalesChartData();

            // Get customers with credit issues for alerts
            $creditAlerts = Customer::with('pendingPayments')
                ->where('credit_limit', '>', 0)
                ->get()
                ->filter(function ($customer) {
                    return $customer->credit_status !== 'safe';
                })
                ->take(5) // Limit to top 5 most urgent
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'credit_used' => $customer->credit_used,
                        'credit_limit' => $customer->credit_limit,
                        'status' => $customer->credit_status
                    ];
                })
                ->values();

            return response()->json([
                'kpis' => [
                    [
                        'title' => 'Net Sales Today',
                        'value' => config('app.currency', 'Rs.') . ' ' . number_format($todayNetSales, 0),
                        'change' => ($salesChange >= 0 ? '+' : '') . number_format($salesChange, 1) . '%',
                        'trend' => $salesChange >= 0 ? 'up' : 'down',
                        'icon' => 'CurrencyIcon'
                    ],
                    [
                        'title' => 'Bank Transactions',
                        'value' => config('app.currency', 'Rs.') . ' ' . number_format($todayBankSales, 0),
                        'change' => ($bankChange >= 0 ? '+' : '') . number_format($bankChange, 1) . '%',
                        'trend' => $bankChange >= 0 ? 'up' : 'down',
                        'icon' => 'CurrencyIcon'
                    ],
                    [
                        'title' => 'Cash Received Today',
                        'value' => config('app.currency', 'Rs.') . ' ' . number_format($todayCashReceived, 0),
                        'change' => ($cashChange >= 0 ? '+' : '') . number_format($cashChange, 1) . '%',
                        'trend' => $cashChange >= 0 ? 'up' : 'down',
                        'icon' => 'CurrencyIcon'
                    ],
                    [
                        'title' => 'Expenses Today',
                        'value' => config('app.currency', 'Rs.') . ' ' . number_format($todayExpenses, 0),
                        'change' => ($expensesChange >= 0 ? '+' : '') . number_format($expensesChange, 1) . '%',
                        'trend' => $expensesChange >= 0 ? 'up' : 'down',
                        'icon' => 'CurrencyIcon'
                    ]
                ],
                'financialKpis' => [
                    [
                        'title' => 'Cash In',
                        'value' => config('app.currency', 'Rs.') . ' ' . number_format($todayCashIn, 0),
                        'change' => ($cashInChange >= 0 ? '+' : '') . number_format($cashInChange, 1) . '%',
                        'trend' => $cashInChange >= 0 ? 'up' : 'down',
                        'icon' => 'TrendingUpIcon',
                        'subtitle' => 'Customer receipts today'
                    ],
                    [
                        'title' => 'Cash Out',
                        'value' => config('app.currency', 'Rs.') . ' ' . number_format($todayCashOut, 0),
                        'change' => ($cashOutChange >= 0 ? '+' : '') . number_format($cashOutChange, 1) . '%',
                        'trend' => $cashOutChange >= 0 ? 'up' : 'down',
                        'icon' => 'CurrencyIcon',
                        'subtitle' => 'Suppliers + Expenses'
                    ],
                    [
                        'title' => 'Closing Balance',
                        'value' => config('app.currency', 'Rs.') . ' ' . number_format($todayClosingBalance, 0),
                        'change' => ($closingChange >= 0 ? '+' : '') . number_format($closingChange, 1) . '%',
                        'trend' => $todayClosingBalance >= 0 ? 'up' : 'down',
                        'icon' => 'DollarIcon',
                        'subtitle' => 'Daily Net Cash in Hand'
                    ],
                    [
                        'title' => 'Profit',
                        'value' => config('app.currency', 'Rs.') . ' ' . number_format($todayProfit, 0),
                        'change' => ($profitChange >= 0 ? '+' : '') . number_format($profitChange, 1) . '%',
                        'trend' => $todayProfit >= 0 ? 'up' : 'down',
                        'icon' => 'ShoppingBagIcon',
                        'subtitle' => 'Daily Net Profit'
                    ]
                ],
                'recentTransactions' => $recentTransactions,
                'lowStockCount' => $lowStockItems,
                'creditAlerts' => $creditAlerts,
                'salesData' => [
                    'today' => $todayNetSales,
                    'yesterday' => $yesterdayNetSales,
                ],
                'salesChartData' => $salesChartData
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard data fetch failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to load dashboard data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sales chart data for the last 7 days
     */
    private function getSalesChartData()
    {
        $days = [];
        $sales = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayName = $date->format('M j');
            $daySales = Sale::whereDate('created_at', $date)
                ->where('invoice_no', 'not like', 'OPB-%')
                ->sum('grand_total');
            $dayReturns = SaleReturn::whereDate('created_at', $date)->sum('grand_total');
            
            $days[] = $dayName;
            $sales[] = (float) ($daySales - $dayReturns);
        }
        
        return [
            'labels' => $days,
            'datasets' => [
                [
                    'label' => 'Net Sales (PKR)',
                    'data' => $sales,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.1
                ]
            ]
        ];
    }
}