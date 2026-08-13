<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\RegisterSession;
use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function index()
    {
        // Get today's sales for current user
        $today = Carbon::today();
        $user = Auth::user();

        // Get active register session
        $activeSession = RegisterSession::getActiveSession($user->id);

        if ($activeSession) {
            // If session is open, fetch sales for THIS session
            $sessionSales = Sale::where('register_session_id', $activeSession->id)
                ->with(['customer', 'saleItems.product'])
                ->latest()
                ->get();
            
            // Calculate session expenses (Drawer Only)
            // Filter strictly by payment_source = 'drawer' to avoid counting external expenses against cash
            $drawerExpenses = Expense::where('register_session_id', $activeSession->id)
                ->where('payment_source', 'drawer')
                ->sum('amount');
            
            // Calculate external expenses (Owner/Bank) - for info only
            $externalExpenses = Expense::where('register_session_id', $activeSession->id)
                ->where('payment_source', 'external')
                ->sum('amount');

        } else {
            // If no active session, show empty
            $sessionSales = collect([]);
            $drawerExpenses = 0;
            $externalExpenses = 0;
        }

        // Calculate stats specific to the session
        $stats = [
            'today_sales_count' => $sessionSales->count(),
            'today_sales_total' => $sessionSales->sum('bill_total'),
            
            // Cash Sales (Money coming into drawer)
            'today_cash_sales' => $sessionSales->where('payment_type', 'cash')->sum('paid_amount'), 
            
            // Credit Sales (Money NOT in drawer)
            'today_credit_sales' => $sessionSales->where('payment_type', 'credit')->sum('bill_total'),
            
            // Bank Sales (Money in Bank, NOT in drawer)
            'today_bank_sales'   => $sessionSales->where('payment_type', 'bank')->sum('paid_amount'), // Use paid_amount for consistency
            
            'session_expenses' => $drawerExpenses,
            'external_expenses' => $externalExpenses
        ];

        return Inertia::render('Registers/Index', [
            'todaySales' => $sessionSales, 
            'stats' => $stats,
            'user' => $user,
            'activeSession' => $activeSession,
            'registerStatus' => $activeSession ? 'open' : 'closed'
        ]);
    }

    public function open(Request $request)
    {
        $request->validate([
            'opening_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        $user = Auth::user();

        // Check if user already has an active session
        $existingSession = RegisterSession::getActiveSession($user->id);
        
        if ($existingSession) {
            return redirect()->route('registers.index')
                ->with('error', 'You already have an open register session. Please close it first.');
        }

        try {
            DB::beginTransaction();

            // Create new register session
            $session = RegisterSession::create([
                'user_id' => $user->id,
                'opening_cash' => $request->opening_cash,
                'opening_notes' => $request->notes,
                'opened_at' => now(),
                'status' => 'open'
            ]);

            DB::commit();

            return redirect()->route('registers.index')
                ->with('success', 'Register opened successfully with ' . config('app.currency', 'Rs.') . ' ' . number_format($request->opening_cash, 2));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('registers.index')
                ->with('error', 'Failed to open register: ' . $e->getMessage());
        }
    }

    public function close(Request $request)
    {
        $request->validate([
            'cash_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        $user = Auth::user();

        // Get active session
        $session = RegisterSession::getActiveSession($user->id);

        if (!$session) {
            return redirect()->route('registers.index')
                ->with('error', 'No active register session found.');
        }

        try {
            DB::beginTransaction();

            // Get session specific cash sales (NOT just today's sales which might include other sessions)
            $sessionCashSales = $session->sales()
                ->where('payment_type', 'cash')
                ->sum('grand_total');
            
            // Get session drawer expenses
            $sessionDrawerExpenses = $session->expenses()
                ->where('payment_source', 'drawer')
                ->sum('amount');

            $expectedCash = ($session->opening_cash + $sessionCashSales) - $sessionDrawerExpenses;
            $cashDifference = $request->cash_amount - $expectedCash;

            // Update session
            $session->update([
                'closing_cash' => $request->cash_amount,
                'expected_cash' => $expectedCash,
                'cash_difference' => $cashDifference,
                'closing_notes' => $request->notes,
                'closed_at' => now(),
                'status' => 'closed'
            ]);

            DB::commit();

            $message = 'Register closed successfully. ';
            if ($cashDifference != 0) {
                $diffType = $cashDifference > 0 ? 'Over' : 'Short';
                $message .= $diffType . ' by ' . config('app.currency', 'Rs.') . ' ' . number_format(abs($cashDifference), 2);
            } else {
                $message .= 'Cash count matches perfectly!';
            }

            return redirect()->route('registers.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('registers.index')
                ->with('error', 'Failed to close register: ' . $e->getMessage());
        }
    }
}
