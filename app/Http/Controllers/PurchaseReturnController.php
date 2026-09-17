<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Models\Payment;
use App\Models\RegisterSession;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display listing of all purchase returns
     */
    public function index(Request $request): Response
    {
        $query = PurchaseReturn::with(['purchase.supplier', 'supplier', 'user', 'items.product.unit'])
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

        // Supplier filter
        if ($request->filled('supplier_id') && $request->supplier_id !== 'all') {
            $query->where('supplier_id', $request->supplier_id);
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
                  ->orWhereHas('purchase', function ($pq) use ($search) {
                      $pq->where('purchase_no', 'like', "%{$search}%");
                  })
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
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
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'phone']);

        return Inertia::render('Returns/Purchases/Index', [
            'returns' => $returns,
            'suppliers' => $suppliers,
            'summary' => [
                'total_amount' => (float) $totalReturnAmount,
                'total_count' => $totalReturnCount,
                'cash_refunded' => (float) $cashRefundTotal,
                'credit_adjusted' => (float) $creditRefundTotal,
                'bank_refunded' => (float) $bankRefundTotal,
            ],
            'filters' => $request->only(['search', 'start_date', 'end_date', 'period', 'supplier_id', 'refund_type'])
        ]);
    }

    /**
     * Show the create return form
     */
    public function create(Request $request): Response
    {
        $purchaseId = $request->input('purchase_id');
        $initialPurchase = null;

        if ($purchaseId) {
            $purchase = Purchase::with(['supplier', 'user', 'items.product.unit', 'items.product.panaflexSpec', 'items.returnItems', 'returns'])->find($purchaseId);
            if ($purchase) {
                $initialPurchase = $this->formatPurchaseForReturn($purchase);
            }
        }

        // Active register session check
        $activeSession = RegisterSession::where('user_id', Auth::id())
            ->where('status', 'open')
            ->first();

        // Recent purchases for quick dropdown pick
        $recentPurchases = Purchase::with(['supplier'])
            ->where('status', '!=', 'cancelled')
            ->orderBy('purchased_at', 'desc')
            ->limit(30)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'purchase_no' => $p->purchase_no,
                    'supplier_name' => $p->supplier->name ?? 'Direct Supplier',
                    'purchased_at' => $p->purchased_at ? $p->purchased_at->format('Y-m-d') : '',
                    'grand_total' => (float) $p->grand_total,
                ];
            });

        return Inertia::render('Returns/Purchases/Create', [
            'initialPurchase' => $initialPurchase,
            'recentPurchases' => $recentPurchases,
            'hasOpenRegister' => $activeSession !== null,
        ]);
    }

    /**
     * Search Purchase by Number or ID for Return Loading
     */
    public function searchPurchase(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (empty($query)) {
            return response()->json(['success' => false, 'message' => 'Please enter a purchase number.'], 400);
        }

        $purchase = Purchase::with([
            'supplier', 
            'user', 
            'items.product.unit', 
            'items.product.panaflexSpec',
            'items.returnItems', 
            'returns'
        ])
        ->where('purchase_no', $query)
        ->orWhere('id', $query)
        ->first();

        if (!$purchase) {
            // Try fuzzy search
            $purchase = Purchase::with([
                'supplier', 
                'user', 
                'items.product.unit', 
                'items.product.panaflexSpec',
                'items.returnItems', 
                'returns'
            ])
            ->where('purchase_no', 'like', "%{$query}%")
            ->orderBy('id', 'desc')
            ->first();
        }

        if (!$purchase) {
            return response()->json([
                'success' => false, 
                'message' => "Purchase '{$query}' not found."
            ], 404);
        }

        $formatted = $this->formatPurchaseForReturn($purchase);

        return response()->json([
            'success' => true,
            'purchase' => $formatted
        ]);
    }

    /**
     * Format purchase and items with return limits
     */
    private function formatPurchaseForReturn(Purchase $purchase): array
    {
        $items = $purchase->items->map(function ($item) {
            $isPanaflex = $item->product && $item->product->type === 'panaflex_roll';
            
            $rollWidthInch = (float) ($item->roll_width_inch ?: ($item->product->panaflexSpec->roll_width_inch ?? 126.0));
            $rollLengthMeter = (float) ($item->roll_length_meter ?: ($item->product->panaflexSpec->roll_length_meter ?? 50.0));
            $rollsCount = (float) ($item->rolls_count ?: 1.0);

            if ($isPanaflex) {
                // In purchase_items, quantity stores total sqft
                $originalSqft = (float) $item->quantity;
                if ($originalSqft <= 0 && $rollWidthInch > 0 && $rollLengthMeter > 0) {
                    $originalSqft = ($rollWidthInch / 12.0) * ($rollLengthMeter * 3.28) * $rollsCount;
                }

                // Calculate returned sqft (sum of units_sqft or fallback from rolls_count)
                $returnedSqft = (float) $item->returnItems->sum(function($ri) use ($rollWidthInch, $rollLengthMeter) {
                    if ((float)$ri->units_sqft > 0) return (float)$ri->units_sqft;
                    if ((float)$ri->rolls_count > 0) {
                        return ($rollWidthInch / 12.0) * ($rollLengthMeter * 3.28) * (float)$ri->rolls_count;
                    }
                    return 0;
                });

                $remainingSqft = max(0, $originalSqft - $returnedSqft);
                $canReturn = ($remainingSqft > 0.01);

                return [
                    'id' => $item->id,
                    'purchase_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Product',
                    'sku' => $item->product->sku ?? '',
                    'unit' => 'sq.ft',
                    'type' => 'panaflex_roll',
                    'is_panaflex' => true,
                    'rate' => (float) $item->rate,
                    'roll_width_inch' => $rollWidthInch,
                    'roll_length_meter' => $rollLengthMeter,
                    'rolls_count' => $rollsCount,
                    'original_units_sqft' => $originalSqft,
                    'returned_units_sqft' => $returnedSqft,
                    'remaining_units_sqft' => $remainingSqft,
                    'width_input' => $rollWidthInch,
                    'width_unit' => 'in',
                    'length_input' => $rollLengthMeter,
                    'length_unit' => 'm',
                    'pieces' => $rollsCount,
                    'return_units_sqft' => 0,
                    'return_quantity' => 0,
                    'return_width_input' => $rollWidthInch,
                    'return_width_unit' => 'in',
                    'return_length_input' => $rollLengthMeter,
                    'return_length_unit' => 'm',
                    'return_pieces' => 1,
                    'line_total' => 0,
                    'can_return' => $canReturn,
                    'note' => '',
                ];
            } else {
                $originalQty = (float) ($item->received_quantity ?: $item->quantity);
                $returnedQty = (float) $item->returnItems->sum('quantity');
                $remainingQty = max(0, $originalQty - $returnedQty);
                $canReturn = ($remainingQty > 0.01);

                return [
                    'id' => $item->id,
                    'purchase_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Product',
                    'sku' => $item->product->sku ?? '',
                    'unit' => $item->product->unit->symbol ?? 'pcs',
                    'type' => 'simple',
                    'is_panaflex' => false,
                    'rate' => (float) $item->rate,
                    'original_quantity' => $originalQty,
                    'returned_quantity' => $returnedQty,
                    'remaining_quantity' => $remainingQty,
                    'return_quantity' => 0,
                    'line_total' => 0,
                    'can_return' => $canReturn,
                    'note' => '',
                ];
            }
        })->values();

        return [
            'id' => $purchase->id,
            'purchase_no' => $purchase->purchase_no,
            'purchased_at' => $purchase->purchased_at ? $purchase->purchased_at->format('Y-m-d H:i') : ($purchase->created_at ? $purchase->created_at->format('Y-m-d H:i') : ''),
            'grand_total' => (float) $purchase->grand_total,
            'supplier' => $purchase->supplier ? [
                'id' => $purchase->supplier->id,
                'name' => $purchase->supplier->name,
                'phone' => $purchase->supplier->phone,
                'balance' => (float) $purchase->supplier->balance,
            ] : null,
            'items' => $items,
            'previous_returns_count' => $purchase->returns->count(),
        ];
    }

    /**
     * Store a newly created purchase return
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'reason' => 'required|string|max:500',
            'refund_type' => 'required|in:cash,credit,bank',
            'other_adjustments' => 'nullable|numeric',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.return_quantity' => 'nullable|numeric|min:0',
            'items.*.return_units_sqft' => 'nullable|numeric|min:0',
            'items.*.length_input' => 'nullable|numeric',
            'items.*.length_unit' => 'nullable|in:m,ft',
            'items.*.width_input' => 'nullable|numeric',
            'items.*.width_unit' => 'nullable|in:in,ft',
            'items.*.return_pieces' => 'nullable|numeric',
            'items.*.note' => 'nullable|string|max:255',
        ]);

        $purchase = Purchase::with(['supplier', 'items.product'])->findOrFail($validated['purchase_id']);

        // Check that at least one item has a return amount
        $validItems = collect($validated['items'])->filter(function ($it) {
            return ($it['return_quantity'] ?? 0) > 0 || ($it['return_units_sqft'] ?? 0) > 0;
        });

        if ($validItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Please enter at least one item return quantity or dimensions.'
            ]);
        }

        $purchaseReturn = DB::transaction(function () use ($validated, $purchase, $validItems) {
            // 1. Create PurchaseReturn header
            $purchaseReturn = PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'user_id' => Auth::id(),
                'return_no' => PurchaseReturn::generateReturnNo(),
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
                $purchaseItem = $purchase->items()->findOrFail($itemData['purchase_item_id']);
                $returnQty = (float) ($itemData['return_quantity'] ?? 0);
                $returnUnits = (float) ($itemData['return_units_sqft'] ?? 0);

                if ($purchaseItem->product && $purchaseItem->product->type === 'panaflex_roll') {
                    // Check against remaining units_sqft
                    $rollWidthInch = (float) ($purchaseItem->roll_width_inch ?: ($purchaseItem->product->panaflexSpec->roll_width_inch ?? 126.0));
                    $rollLengthMeter = (float) ($purchaseItem->roll_length_meter ?: ($purchaseItem->product->panaflexSpec->roll_length_meter ?? 50.0));
                    $rollsCount = (float) ($purchaseItem->rolls_count ?: 1.0);

                    $originalSqft = (float) $purchaseItem->quantity;
                    if ($originalSqft <= 0 && $rollWidthInch > 0 && $rollLengthMeter > 0) {
                        $originalSqft = ($rollWidthInch / 12.0) * ($rollLengthMeter * 3.28) * $rollsCount;
                    }

                    $alreadyReturned = (float) $purchaseItem->returnItems->sum(function($ri) use ($rollWidthInch, $rollLengthMeter) {
                        if ((float)$ri->units_sqft > 0) return (float)$ri->units_sqft;
                        if ((float)$ri->rolls_count > 0) {
                            return ($rollWidthInch / 12.0) * ($rollLengthMeter * 3.28) * (float)$ri->rolls_count;
                        }
                        return 0;
                    });

                    $remaining = max(0, $originalSqft - $alreadyReturned);
                    if ($returnUnits > $remaining + 0.01) {
                        throw ValidationException::withMessages([
                            'items' => "Return units for {$purchaseItem->product->name} ({$returnUnits} sq.ft) exceeds available remaining {$remaining} sq.ft."
                        ]);
                    }
                    $lineTotal = $returnUnits * (float) $purchaseItem->rate;
                } else {
                    // Simple item
                    $alreadyReturned = (float) $purchaseItem->returnItems()->sum('quantity');
                    $remaining = max(0, (float)($purchaseItem->received_quantity ?: $purchaseItem->quantity) - $alreadyReturned);
                    if ($returnQty > $remaining + 0.001) {
                        $pName = $purchaseItem->product ? $purchaseItem->product->name : 'Item';
                        throw ValidationException::withMessages([
                            'items' => "Return quantity for {$pName} ({$returnQty}) exceeds available remaining {$remaining}."
                        ]);
                    }
                    $lineTotal = $returnQty * (float) $purchaseItem->rate;
                }

                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'product_id' => $purchaseItem->product_id,
                    'quantity' => $returnQty,
                    'units_sqft' => $returnUnits,
                    'roll_width_inch' => $purchaseItem->roll_width_inch,
                    'roll_length_meter' => $purchaseItem->roll_length_meter,
                    'rolls_count' => (float)($itemData['return_pieces'] ?? 1),
                    'length_input' => !empty($itemData['length_input']) ? $itemData['length_input'] : $purchaseItem->roll_length_meter,
                    'length_unit' => !empty($itemData['length_unit']) ? $itemData['length_unit'] : 'm',
                    'width_input' => !empty($itemData['width_input']) ? $itemData['width_input'] : $purchaseItem->roll_width_inch,
                    'width_unit' => !empty($itemData['width_unit']) ? $itemData['width_unit'] : 'in',
                    'rate' => $purchaseItem->rate,
                    'line_total' => $lineTotal,
                    'note' => $itemData['note'] ?? null,
                ]);

                $subtotal += $lineTotal;
            }

            $otherAdj = (float) ($validated['other_adjustments'] ?? 0);
            $grandTotal = max(0, $subtotal + $otherAdj);

            $purchaseReturn->update([
                'subtotal' => $subtotal,
                'grand_total' => $grandTotal,
            ]);

            // 3. Deduct stock from inventory (Stock decreases on Purchase Return)
            $purchaseReturn->load(['items.product', 'items.purchaseItem']);
            $this->inventoryService->deductForPurchaseReturn($purchaseReturn);

            // 4. Financial Refund & Ledger Updates
            $refundType = $validated['refund_type'];

            if ($refundType === 'cash') {
                // Cash received from supplier (Cash In)
                if ($purchase->supplier) {
                    Payment::create([
                        'supplier_id' => $purchase->supplier_id,
                        'purchase_id' => null, // On-account payment to adjust ledger correctly
                        'amount' => $grandTotal,
                        'type' => 'received', // Cash received from supplier as refund
                        'payment_date' => now(),
                        'payment_method' => 'cash',
                        'note' => "Cash Refund from Supplier for Purchase Return #{$purchaseReturn->return_no} (Bill #{$purchase->purchase_no})",
                        'user_id' => Auth::id(),
                    ]);
                }
            } elseif ($refundType === 'bank') {
                // Bank received from supplier
                if ($purchase->supplier) {
                    Payment::create([
                        'supplier_id' => $purchase->supplier_id,
                        'purchase_id' => null,
                        'amount' => $grandTotal,
                        'type' => 'received',
                        'payment_date' => now(),
                        'payment_method' => 'bank',
                        'note' => "Bank Refund from Supplier for Purchase Return #{$purchaseReturn->return_no} (Bill #{$purchase->purchase_no})",
                        'user_id' => Auth::id(),
                    ]);
                }
            } elseif ($refundType === 'credit') {
                // When refund_type is credit, PurchaseReturn record itself directly reduces the supplier balance via Supplier::getBalanceAttribute()
                if ($purchase->supplier) {
                    $purchase->supplier->touch(); // triggers timestamp update
                }
            }

            return $purchaseReturn;
        });

        return redirect()->route('returns.purchases.show', $purchaseReturn)
            ->with('success', "Purchase Return {$purchaseReturn->return_no} processed successfully. Stock deducted and supplier ledger updated.");
    }

    /**
     * Show Purchase Return details with print options
     */
    public function show(PurchaseReturn $purchaseReturn): Response
    {
        $purchaseReturn->load([
            'purchase.supplier',
            'supplier',
            'user',
            'items.product.unit',
            'items.product.panaflexSpec',
            'items.purchaseItem'
        ]);

        return Inertia::render('Returns/Purchases/Show', [
            'purchaseReturn' => $purchaseReturn,
        ]);
    }

    /**
     * Delete and reverse a purchase return
     */
    public function destroy(PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $purchase = $purchaseReturn->purchase;
        $supplier = $purchaseReturn->supplier ?: ($purchase ? $purchase->supplier : null);
        $grandTotal = (float) $purchaseReturn->grand_total;
        $refundType = $purchaseReturn->refund_type;
        $returnNo = $purchaseReturn->return_no;

        DB::transaction(function () use ($purchaseReturn, $purchase, $supplier, $grandTotal, $refundType, $returnNo) {
            // 1. Reverse stock changes (restock items sent back)
            $purchaseReturn->load(['items.product']);
            $this->inventoryService->reversePurchaseReturnStock($purchaseReturn);

            // 2. Reverse Financial & Ledger updates
            if ($refundType === 'cash' || $refundType === 'bank') {
                // Delete the cash received refund payment record
                if ($supplier) {
                    Payment::where('supplier_id', $supplier->id)
                        ->where('type', 'received')
                        ->where('note', 'like', "%#{$returnNo}%")
                        ->delete();
                }
            } elseif ($refundType === 'credit') {
                // Deleting the PurchaseReturn record automatically restores supplier balance in Supplier::getBalanceAttribute()
                if ($supplier) {
                    $supplier->touch();
                }
            }

            // 3. Delete return items and return record
            $purchaseReturn->items()->delete();
            $purchaseReturn->delete();
        });

        return redirect()->route('returns.purchases.index')
            ->with('success', "Purchase Return #{$returnNo} deleted and reversed successfully. Stock and supplier ledger restored.");
    }
}
