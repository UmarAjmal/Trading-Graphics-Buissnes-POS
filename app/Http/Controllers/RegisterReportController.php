<?php

namespace App\Http\Controllers;

use App\Models\RegisterSession;
use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegisterReportController extends Controller
{
    public function index()
    {
        $sessions = RegisterSession::with('user')
            ->whereNotNull('closed_at')
            ->orderBy('closed_at', 'desc')
            ->paginate(15);

        return Inertia::render('Reports/Register/Index', [
            'sessions' => $sessions
        ]);
    }

    public function show($id)
    {
        $session = RegisterSession::with([
            'user',
            'sales.customer', 
            'sales.saleItems.product',
            'expenses.category'
        ])->findOrFail($id);

        // Calculate Summary Data
        $sales = $session->sales;

        // Fetch orphan expenses strictly within time range for historical/unlinked data
        $startTime = $session->opened_at;
        $endTime = $session->closed_at ?? now();

        $orphanExpenses = Expense::with('category')
            ->whereNull('register_session_id')
            ->whereBetween('created_at', [$startTime, $endTime])
            ->get();

        // Merge linked expenses with orphans
        $allExpenses = $session->expenses->merge($orphanExpenses);
        
        // Drawer Expenses: Linked to session OR in time range
        $drawerExpenses = $allExpenses->where('payment_source', 'drawer');
        
        // External/Owner Expenses: Linked to session OR in time range
        $externalExpenses = $allExpenses->where('payment_source', 'external');

        // Recalculate Expected Cash for Report Integrity (Fixing historical bad data)
        // Expected = Opening + Cash Sales - Drawer Expenses
        $cashSales = $sales->where('payment_type', 'cash')->sum('grand_total'); // Assuming grand_total is what is collected
        $totalDrawerExpenses = $drawerExpenses->sum('amount');
        
        $session->expected_cash = ($session->opening_cash + $cashSales) - $totalDrawerExpenses;
        $session->cash_difference = $session->closing_cash - $session->expected_cash;

        $totalSales = $sales->sum('bill_total'); // Or grand_total depending on logic
        
        $paymentMethods = $sales->groupBy('payment_type')->map(function ($group) {
            return $group->sum('bill_total');
        });

        // Stock Sold Summary
        $stockSold = [];
        foreach ($sales as $sale) {
            foreach ($sale->saleItems as $item) {
                $productName = $item->product ? $item->product->name : ($item->description ?? 'Unknown Item');
                
                // Key by product ID if exists, else description to group customs
                $key = $item->product_id ? 'p_'.$item->product_id : 'c_'.md5($item->description);
                
                if (!isset($stockSold[$key])) {
                    $stockSold[$key] = [
                        'name' => $productName,
                        'qty' => 0,
                        'sqft' => 0,
                        'total' => 0
                    ];
                }
                $stockSold[$key]['qty'] += $item->quantity; // Use actual column name 'quantity'
                if ($item->units_sqft > 0) {
                     $stockSold[$key]['sqft'] += $item->units_sqft;
                }
                // Depending on logic, might need unit checking. For report simplicy, just Qty for now or line_total
                $stockSold[$key]['total'] += $item->line_total;
            }
        }

        return Inertia::render('Reports/Register/Show', [
            'session' => $session,
            'summary' => [
                'total_sales' => $totalSales,
                'sales_by_method' => $paymentMethods,
                'total_expenses' => $drawerExpenses->sum('amount'),
                'expense_breakdown' => $drawerExpenses->groupBy('expense_category_id')->map(function($group) {
                    return [
                        'category' => $group->first()->category->name ?? 'Uncategorized',
                        'amount' => $group->sum('amount')
                    ];
                }),
                'owner_expenses_total' => $externalExpenses->sum('amount'),
                'owner_expenses_breakdown' => $externalExpenses->groupBy('expense_category_id')->map(function($group) {
                    return [
                        'category' => $group->first()->category->name ?? 'Uncategorized',
                        'amount' => $group->sum('amount')
                    ];
                })
            ],
            'stock_sold' => array_values($stockSold)
        ]);
    }
}
