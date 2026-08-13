<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RegisterSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['category', 'user', 'registerSession'])
            ->latest('date');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Support for single category selection (backward compatibility)
        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        // Add support for multiple category selection (Group Filter)
        if ($request->filled('category_ids') && is_array($request->category_ids)) {
             $query->whereIn('expense_category_id', $request->category_ids);
        }

        // Calculate summaries based on the same filters
        $summaryQuery = clone $query;
        $totalExpense = $summaryQuery->sum('amount');
        
        $drawerExpense = (clone $summaryQuery)->where('payment_source', 'drawer')->sum('amount');
        $externalExpense = (clone $summaryQuery)->where('payment_source', 'external')->sum('amount');

        $expenses = $query->paginate(10)->withQueryString();
        $categories = ExpenseCategory::all();

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'filters' => $request->only(['start_date', 'end_date', 'category_id', 'category_ids']),
            'summary' => [
                'total' => $totalExpense,
                'drawer' => $drawerExpense,
                'external' => $externalExpense
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'payment_source' => 'required|in:drawer,external'
        ]);

        $data = $request->all();
        $data['user_id'] = Auth::id();

        if ($request->payment_source === 'drawer') {
            $activeSession = RegisterSession::getActiveSession(Auth::id());
            
            if (!$activeSession) {
                return redirect()->back()->with('error', 'Cannot pay from drawer: No active register session found. Please open the register first.');
            }
            
            $data['register_session_id'] = $activeSession->id;
        } else {
            // Even if external, link to active session if exists (for reporting purposes)
            // But RegisterController must be smart enough to filter them out of cash reconciliation
            $activeSession = RegisterSession::getActiveSession(Auth::id());
            if ($activeSession) {
                $data['register_session_id'] = $activeSession->id;
            } else {
                $data['register_session_id'] = null;
            }
        }

        Expense::create($data);

        return redirect()->back()->with('success', 'Expense added successfully');
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'payment_source' => 'required|in:drawer,external'
        ]);

        $data = $request->all();

        // Handle payment source change logic if needed
        // For simplicity, we might restrict changing payment source if it affects closed registers
        // But for now, let's allow basic updates. 
        // Ideally, if changing TO drawer, we need active session.
        
        if ($request->payment_source === 'drawer' && $expense->payment_source !== 'drawer') {
             $activeSession = RegisterSession::getActiveSession(Auth::id());
             if (!$activeSession) {
                return redirect()->back()->with('error', 'Cannot switch to drawer payment: No active register session.');
             }
             $data['register_session_id'] = $activeSession->id;
        } elseif ($request->payment_source === 'external') {
            $data['register_session_id'] = null;
        }

        $expense->update($data);

        return redirect()->back()->with('success', 'Expense updated successfully');
    }

    public function destroy(Expense $expense)
    {
        // Prevent deleting if linked to a closed register session? 
        // For now, allow deletion.
        $expense->delete();

        return redirect()->back()->with('success', 'Expense deleted successfully');
    }
}
