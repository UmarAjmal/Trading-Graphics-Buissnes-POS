<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Customer;
use App\Models\User;
use App\Models\PendingPayment;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }
    /**
     * Display sales history with filters
     */
    public function index(Request $request): Response
    {
        $query = Sale::with(['customer', 'user', 'returns'])
            ->where('invoice_no', 'not like', 'OPB-%')
            ->orderBy('sold_at', 'desc');

        // Apply filters
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('sold_at', [
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_to)->endOfDay(),
            ]);
        } elseif ($request->filled('date_range')) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('sold_at', today());
                    break;
                case '7days':
                    $query->whereDate('sold_at', '>=', today()->subDays(7));
                    break;
                case 'month':
                    $query->whereMonth('sold_at', now()->month)
                          ->whereYear('sold_at', now()->year);
                    break;
            }
        }

        if ($request->filled('payment_type') && $request->payment_type !== 'all') {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $sales = $query->paginate(20)->withQueryString();

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['date_range', 'date_from', 'date_to', 'payment_type', 'customer_id', 'user_id', 'search']),
        ]);
    }

    /**
     * Show individual sale with line items
     */
    public function show(Sale $sale): Response
    {
        $sale->load(['customer', 'user', 'saleItems.product', 'returns.user']);
        
        return Inertia::render('Sales/Show', [
            'sale' => $sale,
        ]);
    }

    /**
     * Show return form for a sale
     */
    public function createReturn(Sale $sale)
    {
        $sale->load(['customer', 'user', 'saleItems.product.unit', 'saleItems.returnItems']);

        // Calculate remaining returnable items
        $returnableItems = $sale->saleItems->map(function ($item) {
            return [
                'id' => $item->id,
                'product_name' => $item->product->name,
                'description' => $item->formatted_description,
                'is_panaflex' => $item->product->type === 'panaflex_roll',
                'original_quantity' => $item->quantity,
                'original_units_sqft' => $item->units_sqft,
                'returned_quantity' => $item->returned_quantity,
                'returned_units_sqft' => $item->returned_units,
                'remaining_quantity' => $item->remaining_quantity,
                'remaining_units_sqft' => $item->remaining_units,
                'rate' => $item->rate,
                'can_return' => $item->product->type === 'panaflex_roll' 
                    ? $item->remaining_units > 0 
                    : $item->remaining_quantity > 0,
            ];
        })->filter(function ($item) {
            return $item['can_return'];
        })->values();

        if ($returnableItems->isEmpty()) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'This sale has no returnable items.');
        }

        return Inertia::render('Sales/ReturnForm', [
            'sale' => $sale,
            'returnableItems' => $returnableItems,
        ]);
    }

    /**
     * Store return for a sale
     */
    public function storeReturn(Request $request, Sale $sale): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.return_quantity' => 'nullable|integer|min:0',
            'items.*.return_units_sqft' => 'nullable|numeric|min:0',
            'items.*.note' => 'nullable|string|max:255',
            'other_adjustments' => 'nullable|numeric',
        ]);

        // Validate that at least one item has return quantity/units
        $hasReturnableItems = collect($validated['items'])->some(function ($item) {
            return ($item['return_quantity'] ?? 0) > 0 || ($item['return_units_sqft'] ?? 0) > 0;
        });

        if (!$hasReturnableItems) {
            throw ValidationException::withMessages([
                'items' => 'At least one item must have a return quantity or units greater than 0.'
            ]);
        }

        // Create return record
        $saleReturn = SaleReturn::create([
            'sale_id' => $sale->id,
            'user_id' => auth()->id(),
            'return_no' => SaleReturn::generateReturnNo(),
            'returned_at' => now(),
            'reason' => $validated['reason'],
            'other_adjustments' => $validated['other_adjustments'] ?? 0,
        ]);

        $subtotal = 0;

        foreach ($validated['items'] as $itemData) {
            $saleItem = $sale->saleItems()->findOrFail($itemData['sale_item_id']);
            
            $returnQuantity = $itemData['return_quantity'] ?? 0;
            $returnUnitsSqft = $itemData['return_units_sqft'] ?? 0;

            // Skip if no return quantity/units
            if ($returnQuantity <= 0 && $returnUnitsSqft <= 0) {
                continue;
            }

            // Validate return limits
            if ($saleItem->product->type === 'panaflex_roll') {
                if ($returnUnitsSqft > $saleItem->remaining_units) {
                    throw ValidationException::withMessages([
                        'items' => "Return units for {$saleItem->product->name} exceeds remaining units."
                    ]);
                }
                $lineTotal = (-1) * $returnUnitsSqft * $saleItem->rate;
            } else {
                if ($returnQuantity > $saleItem->remaining_quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Return quantity for {$saleItem->product->name} exceeds remaining quantity."
                    ]);
                }
                $lineTotal = (-1) * $returnQuantity * $saleItem->rate;
            }

            SaleReturnItem::create([
                'sale_return_id' => $saleReturn->id,
                'sale_item_id' => $saleItem->id,
                'quantity' => $returnQuantity,
                'units_sqft' => $returnUnitsSqft,
                'rate' => $saleItem->rate,
                'line_total' => $lineTotal,
                'note' => $itemData['note'],
                'length_input' => $saleItem->length_input,
                'length_unit' => $saleItem->length_unit,
                'width_input' => $saleItem->width_input,
                'width_unit' => $saleItem->width_unit,
            ]);

            $subtotal += $lineTotal;
        }

        // Restock inventory once all return items are stored
        $saleReturn->load(['saleReturnItems.saleItem.product']);
        if ($saleReturn->saleReturnItems->isNotEmpty()) {
            $this->inventoryService->restockForReturn($saleReturn);
        }

        // Update return totals
        $saleReturn->update([
            'subtotal' => $subtotal,
            'grand_total' => $subtotal + ($validated['other_adjustments'] ?? 0),
        ]);

        // Adjust pending payment if this is a credit sale
        if ($sale->is_credit && $sale->pendingPayment) {
            $returnAmount = abs($saleReturn->grand_total);
            $pendingPayment = $sale->pendingPayment;
            
            $newAmountDue = $pendingPayment->amount_due - $returnAmount;
            
            if ($newAmountDue <= 0) {
                $pendingPayment->update([
                    'amount_due' => 0,
                    'settled' => true,
                    'notes' => ($pendingPayment->notes ?? '') . "\nSettled via return {$saleReturn->return_no}",
                ]);
            } else {
                $pendingPayment->update([
                    'amount_due' => $newAmountDue,
                    'notes' => ($pendingPayment->notes ?? '') . "\nPartial payment via return {$saleReturn->return_no} (PKR " . number_format($returnAmount, 2) . ")",
                ]);
            }
        }

        return redirect()->route('sales.show', $sale)
            ->with('success', "Return {$saleReturn->return_no} created successfully.");
    }
    /**
     * Delete a sale and restore inventory
     */
    public function destroy(Sale $sale): RedirectResponse
    {
        DB::transaction(function () use ($sale) {
            // 1. Restore Inventory
            $this->inventoryService->reverseSale($sale);

            // 2. Delete related records
            $sale->saleItems()->delete();
            
            // 3. Handle Pending Payment (Credit Sale)
            if ($sale->pendingPayment) {
                $sale->pendingPayment->delete();
            }

            // 4. Delete Payments (Cash Received at Checkout)
            // This removes the entry from "Payments Cash Received In" and Ledger
            $sale->payments()->delete();

            // 5. Delete Credit Payments (Payments made later for this sale)
            $sale->creditPayments()->delete();

            // 6. Reverse Advance Usage
            // Find advance usage records by note containing the invoice number
            // This is necessary because we don't have a direct sale_id link in customer_advances
            \App\Models\CustomerAdvance::where('customer_id', $sale->customer_id)
                ->where('amount', '<', 0) // Usage is negative
                ->where('note', 'like', "%#{$sale->invoice_no}%")
                ->delete();

            // 7. Delete the sale
            $sale->delete();
            
            // 8. Update Customer Balance (Trigger recalculation if needed, though getBalanceAttribute is dynamic)
            // We touch the customer to update the updated_at timestamp
            if ($sale->customer) {
                $sale->customer->touch();
            }
        });

        return redirect()->route('sales.index')
            ->with('success', 'Sale deleted successfully. Inventory restored and ledger updated.');
    }
}
