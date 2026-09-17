<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PendingPayment;
use App\Models\RegisterSession;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SaleReturnController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display listing of all sale returns
     */
    public function index(Request $request): Response
    {
        $query = SaleReturn::with(['sale.customer', 'user', 'items.saleItem.product'])
            ->orderBy('returned_at', 'desc');

        // Date Filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('returned_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        } elseif ($request->filled('period')) {
            $now = Carbon::now();
            switch ($request->period) {
                case 'today':
                    $query->whereDate('returned_at', today());
                    break;
                case 'yesterday':
                    $query->whereDate('returned_at', today()->subDay());
                    break;
                case '7days':
                    $query->whereDate('returned_at', '>=', today()->subDays(6));
                    break;
                case 'month':
                    $query->whereMonth('returned_at', $now->month)
                          ->whereYear('returned_at', $now->year);
                    break;
            }
        }

        // Customer filter
        if ($request->filled('customer_id') && $request->customer_id !== 'all') {
            $query->whereHas('sale', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }

        // Refund type filter
        if ($request->filled('refund_type') && $request->refund_type !== 'all') {
            $query->where('refund_type', $request->refund_type);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('return_no', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('sale', function ($sq) use ($search) {
                      $sq->where('invoice_no', 'like', "%{$search}%")
                         ->orWhereHas('customer', function ($cq) use ($search) {
                             $cq->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                         });
                  });
            });
        }

        $allMatchingReturns = (clone $query)->get();

        // Calculate KPI summary
        $totalReturnAmount = $allMatchingReturns->sum('grand_total');
        $totalReturnCount = $allMatchingReturns->count();
        $cashRefundTotal = $allMatchingReturns->where('refund_type', 'cash')->sum('grand_total');
        $creditRefundTotal = $allMatchingReturns->where('refund_type', 'credit')->sum('grand_total');
        $bankRefundTotal = $allMatchingReturns->where('refund_type', 'bank')->sum('grand_total');

        $returns = $query->paginate(15)->withQueryString();
        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone']);

        return Inertia::render('Returns/Sales/Index', [
            'returns' => $returns,
            'customers' => $customers,
            'summary' => [
                'total_amount' => (float) $totalReturnAmount,
                'total_count' => $totalReturnCount,
                'cash_refunded' => (float) $cashRefundTotal,
                'credit_adjusted' => (float) $creditRefundTotal,
                'bank_refunded' => (float) $bankRefundTotal,
            ],
            'filters' => $request->only(['search', 'start_date', 'end_date', 'period', 'customer_id', 'refund_type'])
        ]);
    }

    /**
     * Show the create return form
     */
    public function create(Request $request): Response
    {
        $saleId = $request->input('sale_id');
        $initialSale = null;

        if ($saleId) {
            $sale = Sale::with(['customer', 'user', 'saleItems.product.unit', 'saleItems.returnItems', 'pendingPayment'])->find($saleId);
            if ($sale) {
                $initialSale = $this->formatSaleForReturn($sale);
            }
        }

        // Active register session check
        $activeSession = RegisterSession::where('user_id', Auth::id())
            ->where('status', 'open')
            ->first();

        // Recent sales for quick dropdown pick
        $recentSales = Sale::with(['customer'])
            ->where('invoice_no', 'not like', 'OPB-%')
            ->orderBy('sold_at', 'desc')
            ->limit(30)
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'invoice_no' => $s->invoice_no,
                    'customer_name' => $s->customer->name ?? 'Walk-in Customer',
                    'sold_at' => $s->sold_at ? $s->sold_at->format('Y-m-d') : '',
                    'bill_total' => (float) $s->bill_total,
                ];
            });

        return Inertia::render('Returns/Sales/Create', [
            'initialSale' => $initialSale,
            'recentSales' => $recentSales,
            'hasOpenRegister' => $activeSession !== null,
        ]);
    }

    /**
     * Search Invoice by Number or Customer for Return Loading
     */
    public function searchInvoice(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (empty($query)) {
            return response()->json(['success' => false, 'message' => 'Please enter an invoice number.'], 400);
        }

        $sale = Sale::with([
            'customer', 
            'user', 
            'saleItems.product.unit', 
            'saleItems.product.panaflexSpec',
            'saleItems.returnItems', 
            'pendingPayment',
            'returns'
        ])
        ->where('invoice_no', $query)
        ->orWhere('id', $query)
        ->first();

        if (!$sale) {
            // Try fuzzy search
            $sale = Sale::with([
                'customer', 
                'user', 
                'saleItems.product.unit', 
                'saleItems.product.panaflexSpec',
                'saleItems.returnItems', 
                'pendingPayment',
                'returns'
            ])
            ->where('invoice_no', 'like', "%{$query}%")
            ->orderBy('id', 'desc')
            ->first();
        }

        if (!$sale) {
            return response()->json([
                'success' => false, 
                'message' => "Invoice '{$query}' not found."
            ], 404);
        }

        $formatted = $this->formatSaleForReturn($sale);

        return response()->json([
            'success' => true,
            'sale' => $formatted
        ]);
    }

    /**
     * Format sale and items with return limits
     */
    private function formatSaleForReturn(Sale $sale): array
    {
        $items = $sale->saleItems->map(function ($item) {
            $isPanaflex = $item->product && $item->product->type === 'panaflex_roll';
            
            // Calculate already returned amounts
            $returnedQty = (float) $item->returnItems->sum('quantity');
            $returnedUnits = (float) $item->returnItems->sum('units_sqft');

            $originalQty = (float) $item->quantity;
            $originalUnits = (float) $item->units_sqft;

            $remainingQty = max(0, $originalQty - $returnedQty);
            $remainingUnits = max(0, $originalUnits - $returnedUnits);

            $canReturn = $isPanaflex ? ($remainingUnits > 0) : ($remainingQty > 0);

            return [
                'id' => $item->id,
                'sale_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? ($item->description ?: 'Custom Item'),
                'sku' => $item->product->sku ?? '',
                'unit' => $item->product->unit->symbol ?? 'pcs',
                'type' => $item->product->type ?? 'simple',
                'is_panaflex' => $isPanaflex,
                'rate' => (float) $item->rate,
                'original_quantity' => $originalQty,
                'original_units_sqft' => $originalUnits,
                'returned_quantity' => $returnedQty,
                'returned_units_sqft' => $returnedUnits,
                'remaining_quantity' => $remainingQty,
                'remaining_units_sqft' => $remainingUnits,
                'return_quantity' => 0,
                'return_units_sqft' => 0,
                'line_total' => 0,
                'can_return' => $canReturn,
                'note' => '',
                'length_input' => $item->length_input,
                'length_unit' => $item->length_unit ?? 'm',
                'width_input' => $item->width_input,
                'width_unit' => $item->width_unit ?? 'in',
                'return_width_input' => null,
                'return_width_unit' => $item->width_unit ?? 'in',
                'return_length_input' => null,
                'return_length_unit' => $item->length_unit ?? 'm',
                'return_pieces' => 1,
                'input_mode' => 'dimensions',
            ];
        })->values();

        return [
            'id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'sold_at' => $sale->sold_at ? $sale->sold_at->format('Y-m-d H:i') : '',
            'payment_type' => $sale->payment_type,
            'bill_total' => (float) $sale->bill_total,
            'paid_amount' => (float) $sale->paid_amount,
            'discount' => (float) ($sale->discount ?? 0),
            'customer' => $sale->customer ? [
                'id' => $sale->customer->id,
                'name' => $sale->customer->name,
                'phone' => $sale->customer->phone,
                'balance' => (float) $sale->customer->balance,
                'credit_limit' => (float) $sale->customer->credit_limit,
            ] : null,
            'pending_due' => $sale->pendingPayment ? (float) $sale->pendingPayment->amount_due : 0,
            'items' => $items,
            'previous_returns_count' => $sale->returns->count(),
        ];
    }

    /**
     * Store a newly created sale return
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'reason' => 'required|string|max:500',
            'refund_type' => 'required|in:cash,credit,bank',
            'other_adjustments' => 'nullable|numeric',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.return_quantity' => 'nullable|numeric|min:0',
            'items.*.return_units_sqft' => 'nullable|numeric|min:0',
            'items.*.length_input' => 'nullable|numeric|min:0',
            'items.*.length_unit' => 'nullable|string|in:m,ft',
            'items.*.width_input' => 'nullable|numeric|min:0',
            'items.*.width_unit' => 'nullable|string|in:in,ft',
            'items.*.note' => 'nullable|string|max:255',
        ]);

        $sale = Sale::with(['customer', 'saleItems.product', 'pendingPayment'])->findOrFail($validated['sale_id']);

        // Check that at least one item has a return amount
        $validItems = collect($validated['items'])->filter(function ($it) {
            return ($it['return_quantity'] ?? 0) > 0 || ($it['return_units_sqft'] ?? 0) > 0;
        });

        if ($validItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Please enter at least one item return quantity.'
            ]);
        }

        $saleReturn = DB::transaction(function () use ($validated, $sale, $validItems) {
            // 1. Create SaleReturn header
            $saleReturn = SaleReturn::create([
                'sale_id' => $sale->id,
                'user_id' => Auth::id(),
                'return_no' => SaleReturn::generateReturnNo(),
                'returned_at' => now(),
                'reason' => $validated['reason'],
                'refund_type' => $validated['refund_type'],
                'other_adjustments' => $validated['other_adjustments'] ?? 0,
                'subtotal' => 0,
                'grand_total' => 0,
            ]);

            $subtotal = 0;

            // 2. Process Line Items
            foreach ($validItems as $itemData) {
                $saleItem = $sale->saleItems()->findOrFail($itemData['sale_item_id']);
                $returnQty = (float) ($itemData['return_quantity'] ?? 0);
                $returnUnits = (float) ($itemData['return_units_sqft'] ?? 0);

                if ($saleItem->product && $saleItem->product->type === 'panaflex_roll') {
                    // Check against remaining units
                    $alreadyReturned = (float) $saleItem->returnItems()->sum('units_sqft');
                    $remaining = max(0, (float)$saleItem->units_sqft - $alreadyReturned);
                    if ($returnUnits > $remaining + 0.001) {
                        throw ValidationException::withMessages([
                            'items' => "Return units for {$saleItem->product->name} ({$returnUnits} sq.ft) exceeds available remaining {$remaining} sq.ft."
                        ]);
                    }
                    $lineTotal = $returnUnits * (float) $saleItem->rate;
                } else {
                    // Simple item
                    $alreadyReturned = (float) $saleItem->returnItems()->sum('quantity');
                    $remaining = max(0, (float)$saleItem->quantity - $alreadyReturned);
                    if ($returnQty > $remaining + 0.001) {
                        $pName = $saleItem->product ? $saleItem->product->name : ($saleItem->description ?: 'Item');
                        throw ValidationException::withMessages([
                            'items' => "Return quantity for {$pName} ({$returnQty}) exceeds available remaining {$remaining}."
                        ]);
                    }
                    $lineTotal = $returnQty * (float) $saleItem->rate;
                }

                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id' => $saleItem->id,
                    'quantity' => $returnQty,
                    'units_sqft' => $returnUnits,
                    'rate' => $saleItem->rate,
                    'line_total' => $lineTotal,
                    'note' => $itemData['note'] ?? null,
                    'length_input' => !empty($itemData['length_input']) ? $itemData['length_input'] : $saleItem->length_input,
                    'length_unit' => !empty($itemData['length_unit']) ? $itemData['length_unit'] : ($saleItem->length_unit ?? 'm'),
                    'width_input' => !empty($itemData['width_input']) ? $itemData['width_input'] : $saleItem->width_input,
                    'width_unit' => !empty($itemData['width_unit']) ? $itemData['width_unit'] : ($saleItem->width_unit ?? 'in'),
                ]);

                $subtotal += $lineTotal;
            }

            $otherAdj = (float) ($validated['other_adjustments'] ?? 0);
            $grandTotal = max(0, $subtotal + $otherAdj);

            $saleReturn->update([
                'subtotal' => $subtotal,
                'grand_total' => $grandTotal,
            ]);

            // 3. Restock inventory (Stock increases on Sale Return)
            $saleReturn->load(['saleReturnItems.saleItem.product']);
            $this->inventoryService->restockForReturn($saleReturn);

            // 4. Financial Refund & Ledger Updates
            $refundType = $validated['refund_type'];

            if ($refundType === 'cash') {
                // Cash refunded to customer (Disbursement / Cash Out)
                if ($sale->customer) {
                    Payment::create([
                        'customer_id' => $sale->customer_id,
                        'sale_id' => $sale->id,
                        'amount' => $grandTotal,
                        'type' => 'paid', // Cash paid out as refund
                        'payment_date' => now(),
                        'payment_method' => 'cash',
                        'note' => "Cash Refund for Sale Return #{$saleReturn->return_no} (Invoice #{$sale->invoice_no})",
                        'user_id' => Auth::id(),
                    ]);
                }
            } elseif ($refundType === 'bank') {
                // Bank transfer refund to customer
                if ($sale->customer) {
                    Payment::create([
                        'customer_id' => $sale->customer_id,
                        'sale_id' => $sale->id,
                        'amount' => $grandTotal,
                        'type' => 'paid',
                        'payment_date' => now(),
                        'payment_method' => 'bank',
                        'note' => "Bank Refund for Sale Return #{$saleReturn->return_no} (Invoice #{$sale->invoice_no})",
                        'user_id' => Auth::id(),
                    ]);
                }
            } elseif ($refundType === 'credit') {
                // Adjust customer credit/debt
                if ($sale->pendingPayment) {
                    $pending = $sale->pendingPayment;
                    $newDue = max(0, (float)$pending->amount_due - $grandTotal);
                    $pending->update([
                        'amount_due' => $newDue,
                        'settled' => ($newDue <= 0.01),
                        'note' => ($pending->note ?? '') . "\nAdjusted Rs " . number_format($grandTotal, 2) . " via Sale Return #{$saleReturn->return_no}",
                    ]);
                }

                if ($sale->customer) {
                    $sale->customer->credit_used = \App\Models\PendingPayment::where('customer_id', $sale->customer_id)
                        ->where('settled', false)
                        ->sum('amount_due');
                    $sale->customer->save();
                }
            }

            return $saleReturn;
        });

        return redirect()->route('returns.sales.show', $saleReturn)
            ->with('success', "Sale Return {$saleReturn->return_no} processed successfully. Stock and ledger updated.");
    }

    /**
     * Show Sale Return details with print options
     */
    public function show(SaleReturn $saleReturn): Response
    {
        $saleReturn->load([
            'sale.customer',
            'sale.user',
            'user',
            'items.saleItem.product.unit',
            'items.saleItem.product.panaflexSpec'
        ]);

        return Inertia::render('Returns/Sales/Show', [
            'saleReturn' => $saleReturn,
        ]);
    }

    /**
     * Delete and reverse a sale return
     */
    public function destroy(SaleReturn $saleReturn): RedirectResponse
    {
        $sale = $saleReturn->sale;
        $customer = $sale ? $sale->customer : null;
        $grandTotal = (float) $saleReturn->grand_total;
        $refundType = $saleReturn->refund_type;
        $returnNo = $saleReturn->return_no;

        DB::transaction(function () use ($saleReturn, $sale, $customer, $grandTotal, $refundType, $returnNo) {
            // 1. Reverse stock changes (deduct back the restocked inventory)
            $saleReturn->load(['saleReturnItems.saleItem.product']);
            $this->inventoryService->reverseSaleReturnStock($saleReturn);

            // 2. Reverse Financial & Ledger updates
            if ($refundType === 'cash' || $refundType === 'bank') {
                // Delete the refund payment record
                Payment::where('sale_id', $sale->id)
                    ->where('type', 'paid')
                    ->where('note', 'like', "%#{$returnNo}%")
                    ->delete();
            } elseif ($refundType === 'credit') {
                // If there was a pending payment on the sale, restore amount_due
                if ($sale && $sale->pendingPayment) {
                    $pending = $sale->pendingPayment;
                    $pending->amount_due = (float) $pending->amount_due + $grandTotal;
                    $pending->settled = false;
                    $pending->note = ($pending->note ?? '') . "\nReversed Sale Return #{$returnNo} (+Rs {$grandTotal})";
                    $pending->save();
                }

                if ($customer) {
                    $customer->credit_used = \App\Models\PendingPayment::where('customer_id', $customer->id)
                        ->where('settled', false)
                        ->sum('amount_due');
                    $customer->save();
                }
            }

            // 3. Delete return items and return record
            $saleReturn->saleReturnItems()->delete();
            $saleReturn->delete();
        });

        return redirect()->route('returns.sales.index')
            ->with('success', "Sale Return #{$returnNo} deleted and reversed successfully. Stock and ledger restored.");
    }
}
