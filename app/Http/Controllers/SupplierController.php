<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Purchase;
use App\Exports\SuppliersExport;
use App\Imports\SuppliersImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('Suppliers/Index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Suppliers/Form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:suppliers,name',
                'phone' => 'nullable|string|max:20',
                'whatsapp' => 'nullable|string|max:20',
                'email' => 'nullable|email|unique:suppliers,email',
                'address' => 'nullable|string|max:500',
                'contact_person' => 'nullable|string|max:255',
                'opening_balance' => 'nullable|numeric|min:0',
            ]);

            $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
            $supplier = Supplier::create($validated);

            // Create dummy purchase for opening balance
            if ($validated['opening_balance'] > 0) {
                $dummyPurchase = Purchase::create([
                    'purchase_no' => 'OPB-' . $supplier->id,
                    'supplier_id' => $supplier->id,
                    'user_id' => auth()->id(),
                    'purchased_at' => now(),
                    'subtotal' => $validated['opening_balance'],
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'other_charges' => 0,
                    'grand_total' => $validated['opening_balance'],
                    'notes' => 'Opening Balance',
                ]);

                // Create pending payment record
                \App\Models\PendingPayment::create([
                    'purchase_id' => $dummyPurchase->id,
                    'supplier_id' => $supplier->id,
                    'amount_due' => $validated['opening_balance'],
                    'settled' => false,
                ]);
            }

            return redirect()->route('suppliers.index')
                ->with('success', 'Supplier created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation failed - errors will be automatically returned to form
            throw $e;
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Supplier creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return with actual error message for debugging
            return back()->withInput()->withErrors([
                'name' => 'Error creating supplier: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier): Response
    {
        $supplier->load(['purchases' => function ($query) {
            $query->orderBy('purchased_at', 'desc')->limit(10);
        }]);

        return Inertia::render('Suppliers/Show', [
            'supplier' => $supplier,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier): Response
    {
        return Inertia::render('Suppliers/Form', [
            'supplier' => $supplier,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers,name,' . $supplier->id,
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:suppliers,email,' . $supplier->id,
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $oldOpeningBalance = $supplier->opening_balance;
        $newOpeningBalance = $validated['opening_balance'];

        $supplier->update($validated);

        // Handle opening balance changes
        if ($oldOpeningBalance != $newOpeningBalance) {
            // Find dummy purchase
            $dummyPurchase = Purchase::where('supplier_id', $supplier->id)
                ->where('purchase_no', 'OPB-' . $supplier->id)
                ->first();

            if ($dummyPurchase) {
                // Update existing dummy purchase
                $dummyPurchase->update([
                    'grand_total' => $newOpeningBalance,
                ]);

                // Update pending payment
                $pendingPayment = \App\Models\PendingPayment::where('purchase_id', $dummyPurchase->id)->first();
                if ($pendingPayment) {
                    $pendingPayment->update(['amount_due' => $newOpeningBalance]);
                }

                // Delete if balance is 0
                if ($newOpeningBalance == 0) {
                    $pendingPayment?->delete();
                    $dummyPurchase->delete();
                }
            } elseif ($newOpeningBalance > 0) {
                // Create new dummy purchase
                $dummyPurchase = Purchase::create([
                    'purchase_no' => 'OPB-' . $supplier->id,
                    'supplier_id' => $supplier->id,
                    'user_id' => auth()->id(),
                    'purchased_at' => now(),
                    'subtotal' => $newOpeningBalance,
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'other_charges' => 0,
                    'grand_total' => $newOpeningBalance,
                    'notes' => 'Opening Balance',
                ]);

                \App\Models\PendingPayment::create([
                    'purchase_id' => $dummyPurchase->id,
                    'supplier_id' => $supplier->id,
                    'amount_due' => $newOpeningBalance,
                    'settled' => false,
                ]);
            }
        }

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        // Check if supplier has purchases
        if ($supplier->purchases()->count() > 0) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Cannot delete supplier with existing purchases.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    /**
     * Get suppliers for DataTable
     */
    public function tableData(Request $request): JsonResponse
    {
        $query = Supplier::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Get total count before pagination
        $totalRecords = $query->count();

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $suppliers = $query->withCount('purchases')
            ->orderBy('name')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return response()->json([
            'data' => $suppliers,
            'total' => $totalRecords,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($totalRecords / $perPage),
        ]);
    }

    /**
     * Search suppliers for select components
     */
    public function search(Request $request): JsonResponse
    {
        $input = $request->get('q', '');
        $query = str_replace(' ', '%', $input);
        
        $suppliers = Supplier::when($query, function ($q) use ($query, $input) {
                $q->where(function($subQ) use ($query, $input) {
                    $subQ->where('name', 'like', "%{$query}%")
                         ->orWhere('phone', 'like', "%{$input}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'phone']);

        return response()->json($suppliers);
    }

    /**
     * Export suppliers to Excel.
     */
    public function export(Request $request)
    {
        return Excel::download(new SuppliersExport, 'suppliers.xlsx');
    }

    /**
     * Import suppliers from Excel.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        try {
            Excel::import(new SuppliersImport, $request->file('file'));
            
            return back()->with('success', 'Suppliers imported successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle supplier active status
     */
    public function toggleStatus(Supplier $supplier): RedirectResponse
    {
        $supplier->update(['is_active' => !$supplier->is_active]);
        
        $status = $supplier->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Supplier {$status} successfully.");
    }
    /**
     * Get supplier account details for API
     */
    public function apiAccount(Supplier $supplier): JsonResponse
    {
        // Get prepayments (PendingPayment with is_prepayment = true)
        $prepayments = \App\Models\PendingPayment::where('supplier_id', $supplier->id)
            ->where('is_prepayment', true)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all purchases for this supplier and calculate remaining amounts
        $pendingInvoices = \App\Models\Purchase::where('supplier_id', $supplier->id)
            ->orderBy('purchased_at', 'desc')
            ->get()
            ->map(function ($purchase) {
                // Calculate paid amount from PendingPayments linked to this purchase
                $paidAmount = \App\Models\PendingPayment::where('purchase_id', $purchase->id)
                    ->where('is_prepayment', false)
                    ->sum('amount');
                
                $purchase->remaining_amount = $purchase->grand_total - $paidAmount;
                
                // Calculate payment status
                if ($paidAmount >= $purchase->grand_total - 0.01) {
                    $purchase->payment_status = 'paid';
                } elseif ($paidAmount > 0) {
                    $purchase->payment_status = 'partial';
                } else {
                    $purchase->payment_status = 'unpaid';
                }
                
                return $purchase;
            })
            ->filter(function ($purchase) {
                return $purchase->remaining_amount > 0.01; // Only show unpaid/partial invoices
            })
            ->values();

        return response()->json([
            'supplier' => $supplier,
            'prepayments' => $prepayments,
            'pendingInvoices' => $pendingInvoices
        ]);
    }

    /**
     * Store a prepayment for a supplier
     */
    public function storePrepayment(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => 'nullable|string'
        ]);

        \App\Models\PendingPayment::create([
            'supplier_id' => $supplier->id,
            'amount' => $validated['amount'],
            'amount_due' => 0,
            'is_prepayment' => true,
            'note' => $validated['note'],
            'created_at' => $validated['date'], // Use provided date
            'payment_method' => 'cash', // Default to cash for now
            'settled' => false
        ]);

        return back()->with('success', 'Prepayment added successfully.');
    }

    /**
     * Store a payment for a specific purchase
     */
    public function storePayment(Request $request, Supplier $supplier, Purchase $purchase): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'note' => 'nullable|string'
        ]);

        // Verify purchase belongs to supplier
        if ($purchase->supplier_id !== $supplier->id) {
            return back()->withErrors(['error' => 'Invalid purchase for this supplier.']);
        }

        // Check if payment amount exceeds remaining amount
        $paidAmount = \App\Models\PendingPayment::where('purchase_id', $purchase->id)
            ->where('is_prepayment', false)
            ->sum('amount');
        
        $remaining = $purchase->grand_total - $paidAmount;

        if ($validated['amount'] > $remaining + 0.01) { // Tolerance
            return back()->withErrors(['amount' => 'Payment amount cannot exceed remaining balance.']);
        }

        // Create payment record
        \App\Models\PendingPayment::create([
            'supplier_id' => $supplier->id,
            'purchase_id' => $purchase->id,
            'amount' => $validated['amount'],
            'amount_due' => 0,
            'is_prepayment' => false,
            'note' => $validated['note'],
            'created_at' => $validated['payment_date'],
            'payment_method' => 'cash',
            'settled' => true
        ]);

        return back()->with('success', 'Payment recorded successfully.');
    }
}
