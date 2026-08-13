<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use App\Exports\CustomersExport;
use App\Imports\CustomersImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = Customer::with('pendingPayments')
            ->latest()
            ->paginate(15);
        
        // Add credit status to each customer
        $customers->through(function ($customer) {
            $customer->credit_status = $customer->credit_status;
            $customer->credit_used = $customer->credit_used;
            $customer->available_credit = $customer->available_credit;
            return $customer;
        });
        
        // Get customers with credit issues for alerts
        $creditAlerts = Customer::with('pendingPayments')
            ->where('credit_limit', '>', 0)
            ->get()
            ->filter(function ($customer) {
                return $customer->credit_status !== 'safe';
            })
            ->values();
        
        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'creditAlerts' => $creditAlerts
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Customers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Convert empty strings to null for optional fields
        $validated['email'] = $validated['email'] ?? null;
        $validated['phone'] = $validated['phone'] ?? null;
        $validated['address'] = $validated['address'] ?? null;
        $validated['city'] = $validated['city'] ?? null;
        $validated['postal_code'] = $validated['postal_code'] ?? null;
        $validated['credit_limit'] = $validated['credit_limit'] ?? 0;
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['notes'] = $validated['notes'] ?? null;
        $validated['customer_type'] = 'individual'; // Set default since field is removed from form

        $customer = Customer::create($validated);

        // Handle Opening Balance as a Credit Sale
        if ($validated['opening_balance'] > 0) {
            $invoiceNo = 'OPB-' . str_pad($customer->id, 5, '0', STR_PAD_LEFT);
            
            $sale = Sale::create([
                'invoice_no' => $invoiceNo,
                'customer_id' => $customer->id,
                'user_id' => auth()->id(), // Current user created this
                'sold_at' => now(),
                'payment_type' => 'credit',
                'subtotal' => $validated['opening_balance'],
                'grand_total' => $validated['opening_balance'],
                'paid_amount' => 0,
                'current_balance' => -$validated['opening_balance'], // Negative means due
                'notes' => 'Opening Balance',
                'bill_total' => $validated['opening_balance'],
            ]);

            // Create Pending Payment record
            \App\Models\PendingPayment::create([
                'sale_id' => $sale->id,
                'customer_id' => $customer->id,
                'amount_due' => $validated['opening_balance'],
                'amount' => $validated['opening_balance'],
                'settled' => false,
                'note' => 'Opening Balance',
            ]);
            
            // Update customer credit used
            $customer->increment('credit_used', $validated['opening_balance']);
        }

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        $customer->load(['sales' => function($query) {
            $query->latest()->limit(5);
        }]);

        return Inertia::render('Customers/Show', [
            'customer' => $customer
        ]);
    }

    /**
     * Display customer account page with advance and credit management
     */
    /**
     * Display customer account page with advance and credit management
     */
    public function account(Customer $customer)
    {
        $data = $this->getAccountData($customer);

        return Inertia::render('Customers/Account', [
            'customer' => $customer,
            'advances' => $data['advances'],
            'creditHistory' => $data['creditHistory'],
        ]);
    }

    /**
     * API endpoint for customer account data
     */
    public function apiAccount(Customer $customer)
    {
        $data = $this->getAccountData($customer);

        return response()->json([
            'customer' => $customer,
            'advances' => $data['advances'],
            'creditHistory' => $data['creditHistory'],
        ]);
    }

    /**
     * Helper to get account data
     */
    private function getAccountData(Customer $customer)
    {
        // Get all advances for this customer
        $advances = $customer->advances()
            ->with('user')
            ->orderBy('date', 'desc')
            ->get();

        // Get credit history from PendingPayments (which now correctly stores bill_total amounts)
        $creditHistory = \App\Models\PendingPayment::where('customer_id', $customer->id)
            ->with(['sale' => function($query) {
                $query->with('creditPayments');
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($pendingPayment) {
                if (!$pendingPayment->sale) {
                    return null; // Skip if sale was deleted
                }
                
                $sale = $pendingPayment->sale;
                
                // Use amount_due from PendingPayment (which is bill_total based)
                $totalCredit = $pendingPayment->amount;
                $totalPaid = $sale->creditPayments->sum('amount');
                $remainingCredit = $pendingPayment->amount_due; // Current remaining
                
                $status = 'unpaid';
                if ($pendingPayment->settled || $remainingCredit <= 0) {
                    $status = 'paid';
                } elseif ($totalPaid > 0) {
                    $status = 'partial';
                }

                return [
                    'id' => $pendingPayment->id,
                    'sale_id' => $sale->invoice_no,
                    'date' => $sale->sold_at,
                    'total_credit' => $totalCredit,  // Original bill_total amount
                    'total_paid' => $totalPaid,
                    'remaining_credit' => $remainingCredit,  // Current pending amount
                    'status' => $status,
                    'payments' => $sale->creditPayments->map(function ($payment) {
                        return [
                            'id' => $payment->id,
                            'amount' => $payment->amount,
                            'payment_date' => $payment->payment_date,
                            'note' => $payment->note,
                        ];
                    }),
                ];
            })
            ->filter() // Remove nulls
            ->values();

        return compact('advances', 'creditHistory');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return Inertia::render('Customers/Edit', [
            'customer' => $customer
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Convert empty strings to null for optional fields
        $validated['email'] = $validated['email'] ?: null;
        $validated['phone'] = $validated['phone'] ?: null;
        $validated['address'] = $validated['address'] ?: null;
        $validated['city'] = $validated['city'] ?: null;
        $validated['postal_code'] = $validated['postal_code'] ?: null;
        $validated['credit_limit'] = $validated['credit_limit'] ?: 0;
        $validated['opening_balance'] = $validated['opening_balance'] ?: 0;
        $validated['notes'] = $validated['notes'] ?: null;
        $validated['customer_type'] = 'individual'; // Set default since field is removed from form

        // Handle Opening Balance Update
        $oldOpeningBalance = $customer->opening_balance;
        $newOpeningBalance = $validated['opening_balance'];

        if ($oldOpeningBalance != $newOpeningBalance) {
            // Find existing Opening Balance Sale
            $invoiceNo = 'OPB-' . str_pad($customer->id, 5, '0', STR_PAD_LEFT);
            $sale = Sale::where('invoice_no', $invoiceNo)->first();

            if ($sale) {
                // Update existing sale
                $diff = $newOpeningBalance - $oldOpeningBalance;
                
                $sale->update([
                    'subtotal' => $newOpeningBalance,
                    'grand_total' => $newOpeningBalance,
                    'current_balance' => -$newOpeningBalance, // Negative means due
                    'bill_total' => $newOpeningBalance,
                ]);

                // Update Pending Payment (find and update existing one)
                $payment = \App\Models\PendingPayment::where('sale_id', $sale->id)
                    ->where('customer_id', $customer->id)
                    ->first();
                    
                if ($payment) {
                    $payment->update([
                        'amount_due' => $newOpeningBalance,
                        'amount' => $newOpeningBalance,
                        'settled' => $newOpeningBalance <= 0, // Mark settled if balance is 0
                    ]);
                } elseif ($newOpeningBalance > 0) {
                    // Create pending payment if it doesn't exist but opening balance is > 0
                    \App\Models\PendingPayment::create([
                        'sale_id' => $sale->id,
                        'customer_id' => $customer->id,
                        'amount_due' => $newOpeningBalance,
                        'amount' => $newOpeningBalance,
                        'settled' => false,
                        'note' => 'Opening Balance',
                    ]);
                }

                // Correctly update customer credit used with the difference
                // First get current credit_used from database (excluding OPB)
                $currentCreditUsed = \App\Models\PendingPayment::where('customer_id', $customer->id)
                    ->where('settled', false)
                    ->whereHas('sale', function($q) {
                        $q->where('invoice_no', 'not like', 'OPB-%');
                    })
                    ->sum('amount_due');
                
                // Set the correct credit_used (current pending + new opening balance)
                $customer->credit_used = $currentCreditUsed + $newOpeningBalance;
                
            } elseif ($newOpeningBalance > 0) {
                // Create new sale if it didn't exist (e.g. opening balance was 0 initially)
                $sale = Sale::create([
                    'invoice_no' => $invoiceNo,
                    'customer_id' => $customer->id,
                    'user_id' => auth()->id(),
                    'sold_at' => now(),
                    'payment_type' => 'credit',
                    'subtotal' => $newOpeningBalance,
                    'grand_total' => $newOpeningBalance,
                    'paid_amount' => 0,
                    'current_balance' => -$newOpeningBalance,
                    'notes' => 'Opening Balance',
                    'bill_total' => $newOpeningBalance,
                ]);

                \App\Models\PendingPayment::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $customer->id,
                    'amount_due' => $newOpeningBalance,
                    'amount' => $newOpeningBalance,
                    'settled' => false,
                    'note' => 'Opening Balance',
                ]);

                // Recalculate credit_used properly
                $currentCreditUsed = \App\Models\PendingPayment::where('customer_id', $customer->id)
                    ->where('settled', false)
                    ->sum('amount_due');
                    
                $customer->credit_used = $currentCreditUsed;
            } elseif ($newOpeningBalance == 0 && $sale) {
                // Opening balance set to 0, delete the OPB sale and pending payment
                $payment = \App\Models\PendingPayment::where('sale_id', $sale->id)->first();
                if ($payment) {
                    $payment->delete();
                }
                $sale->delete();
                
                // Recalculate credit_used
                $currentCreditUsed = \App\Models\PendingPayment::where('customer_id', $customer->id)
                    ->where('settled', false)
                    ->sum('amount_due');
                    
                $customer->credit_used = $currentCreditUsed;
            }
        }

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        if ($customer->name === 'Walk-in Customer') {
            return redirect()->route('customers.index')
                ->with('error', 'Cannot delete the default Walk-in Customer.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    /**
     * Get customers data for DataTable
     */
    public function tableData(Request $request)
    {
        $query = Customer::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Filter by customer type (if we add this column later)
        if ($request->has('customer_type') && $request->customer_type) {
            // Note: customer_type column doesn't exist yet
            // $query->where('customer_type', $request->customer_type);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $customers = $query->paginate($request->get('per_page', 15));

        // Add credit status to each customer
        $customers->through(function ($customer) {
            $customer->credit_status = $customer->credit_status;
            $customer->credit_used = $customer->credit_used;
            $customer->available_credit = $customer->available_credit;
            return $customer;
        });

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    /**
     * Search customers for POS/Sales
     */
    public function search(Request $request)
    {
        $input = $request->get('q', '');
        // Replace spaces with % to allow partial matching (e.g. "Ali  Raza" matches "Ali Raza", "Ali Ahmad Raza")
        $search = str_replace(' ', '%', $input);
        
        $customers = Customer::where(function($query) use ($search, $input) {
                $query->where('name', 'like', "%{$search}%")
                      // Keep phone search strict/original or also loose? usually phone is no spaces. 
                      // Let's use strict input for phone to avoid matching everything if someone types "0300 123" with space
                      ->orWhere('phone', 'like', "%{$input}%")
                      ->orWhere('whatsapp', 'like', "%{$input}%");
            })
            ->orderBy('name', 'asc')
            ->limit(50)
            ->get(['id', 'name', 'phone', 'whatsapp']);

        return response()->json($customers);
    }

    /**
     * API endpoint to get all customers for dropdowns
     */
    public function apiIndex()
    {
        $customers = Customer::select('id', 'name', 'phone', 'email')
            ->orderBy('name')
            ->get();
            
        return response()->json($customers);
    }

    /**
     * API endpoint to store a new customer
     */
    public function apiStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
            ]);

            $customer = Customer::create($validated);

            // Format data to match searchCustomers response
            $formattedData = [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'display_name' => $customer->name . ($customer->phone ? " ({$customer->phone})" : ""),
                'total_pending' => "0.00",
                'advance_balance' => "0.00",
                'advance_balance_raw' => 0,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully',
                'data' => $formattedData
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export customers to Excel
     */
    public function export()
    {
        return Excel::download(new CustomersExport, 'customers.xlsx');
    }

    /**
     * Import customers from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new CustomersImport, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Customers imported successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer info for POS display
     */
    public function getPosInfo(Customer $customer)
    {
        // **CRITICAL FIX: Get fresh balance directly from database, bypassing all caches**
        // Use the getBalanceAttribute logic which correctly accounts for Payments and Advances
        // We refresh the customer to ensure we get the latest data
        $customer->refresh();
        $freshCreditUsed = $customer->balance;
        
        // Get fresh advance balance
        $freshAdvanceBalance = \App\Models\CustomerAdvance::where('customer_id', $customer->id)
            ->sum('amount');
        
        return response()->json([
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'credit_limit' => (float) $customer->credit_limit,
            'credit_used' => $freshCreditUsed, // Use fresh balance
            'available_credit' => max(0, (float) $customer->credit_limit - $freshCreditUsed),
            'credit_status' => $this->calculateCreditStatus($customer->credit_limit, $freshCreditUsed),
            'advance' => $freshAdvanceBalance, // Use fresh query result
        ]);
    }
    
    /**
     * Calculate credit status based on current usage
     */
    private function calculateCreditStatus($creditLimit, $creditUsed)
    {
        if ($creditLimit <= 0) {
            return 'safe';
        }
        
        if ($creditUsed > $creditLimit) {
            return 'exceeded';
        }
        
        $threshold = $creditLimit * 0.9;
        if ($creditUsed >= $threshold) {
            return 'warning';
        }
        
        return 'safe';
    }

    /**
     * Store advance payment for customer
     */
    public function storeAdvance(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:1000',
        ]);

        $customer->advances()->create([
            'date' => $validated['date'],
            'amount' => $validated['amount'],
            'note' => $validated['note'],
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('customers.account', $customer)
            ->with('success', 'Advance payment added successfully.');
    }

    /**
     * Store credit payment for a specific sale
     */
    /**
     * Store credit payment for a specific pending payment
     */
    /**
     * Store credit payment for a specific pending payment
     */
    public function storeCreditPayment(Request $request, $customerId, $pendingPaymentId)
    {
        $customer = Customer::findOrFail($customerId);
        $pendingPayment = \App\Models\PendingPayment::findOrFail($pendingPaymentId);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'note' => 'nullable|string|max:1000',
        ]);

        // Get the remaining credit for this pending payment
        $remainingCredit = $pendingPayment->amount_due;

        // Validate payment amount doesn't exceed remaining credit (allow 0.01 buffer)
        if ($validated['amount'] > ($remainingCredit + 0.01)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amount' => ['Payment amount cannot exceed remaining credit of PKR ' . number_format($remainingCredit, 2)]
            ]);
        }

        DB::transaction(function () use ($customer, $pendingPayment, $validated) {
            // Create Credit Payment Record
            $payment = $customer->creditPayments()->create([
                'sale_id' => $pendingPayment->sale_id, // Link to original sale
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'note' => $validated['note'],
                'user_id' => auth()->id(),
            ]);

            // Update Pending Payment
            $pendingPayment->decrement('amount_due', $validated['amount']);
            
            // Check if fully settled (allowing for small floating point differences)
            if ($pendingPayment->amount_due <= 0.01) {
                $pendingPayment->update(['settled' => true, 'amount_due' => 0]);
            }

            // Recalculate Customer Credit Used from scratch to ensure accuracy
            $customer->credit_used = \App\Models\PendingPayment::where('customer_id', $customer->id)
                ->where('settled', false)
                ->sum('amount_due');
            $customer->save();
        });

        return redirect()->route('customers.account', $customer->id)
            ->with('success', 'Credit payment recorded successfully.');
    }
}
