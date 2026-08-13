<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Exports\PurchasesExport;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('Purchases/Index', [
            'suppliers' => Supplier::all(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Purchases/Form', [
            'suppliers' => Supplier::all(['id', 'name']),
            'products' => Product::with(['unit', 'panaflexSpec'])->where('active', true)->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchased_at' => 'required|date',
            'expected_date' => 'nullable|date',
            'subtotal' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
            'tax_total' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'shipping_charges' => 'nullable|numeric|min:0',
            'grand_total' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,pending,ordered,received,cancelled',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'nullable|numeric|min:0.1',
            'items.*.roll_width_inch' => 'nullable|numeric|min:0',
            'items.*.roll_length_meter' => 'nullable|numeric|min:0',
            'items.*.rolls_count' => 'nullable|numeric|min:0.1',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.line_total' => 'required|numeric|min:0',
        ]);

        $itemsCollection = collect($validated['items'])
            ->map(fn ($item) => $this->normalizeItemPayload($item));
        $validated['items'] = $itemsCollection->toArray();
        $validated = array_merge($validated, $this->calculateTotals($itemsCollection, $validated));

        $shouldReceiveImmediately = ($validated['status'] ?? 'pending') === 'received';

        DB::transaction(function () use ($validated, $shouldReceiveImmediately) {
            // Create purchase
            $purchase = Purchase::create([
                'purchase_no' => Purchase::generatePurchaseNumber(),
                'supplier_id' => $validated['supplier_id'],
                'user_id' => Auth::id(),
                'purchased_at' => Carbon::parse($validated['purchased_at']),
                'expected_date' => isset($validated['expected_date']) ? Carbon::parse($validated['expected_date']) : null,
                'subtotal' => $validated['subtotal'],
                'discount_total' => $validated['discount_total'] ?? 0,
                'tax_total' => $validated['tax_total'] ?? 0,
                'other_charges' => $validated['other_charges'] ?? 0,
                'shipping_charges' => $validated['shipping_charges'] ?? 0,
                'grand_total' => $validated['grand_total'],
                'status' => $validated['status'] ?? 'pending',
                'notes' => $validated['notes'],
            ]);

            // Create purchase items
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Validate item data based on product type
                if ($product->type === 'simple' && empty($item['quantity'])) {
                    throw new \Exception("Quantity is required for simple product: {$product->name}");
                }
                
                if ($product->type === 'panaflex_roll') {
                    if (empty($item['rolls_count']) || empty($item['roll_length_meter']) || empty($item['roll_width_inch'])) {
                        throw new \Exception("Roll details are required for panaflex product: {$product->name}");
                    }
                }

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'roll_width_inch' => $item['roll_width_inch'] ?? null,
                    'roll_length_meter' => $item['roll_length_meter'] ?? null,
                    'rolls_count' => $item['rolls_count'] ?? null,
                    'rate' => $item['rate'],
                    'line_total' => $item['line_total'],
                ]);
            }

            if ($shouldReceiveImmediately) {
                $purchase->load('purchaseItems.product');

                foreach ($purchase->purchaseItems as $purchaseItem) {
                    $purchaseItem->update([
                        'received_quantity' => $purchaseItem->quantity ?? 0,
                    ]);
                }

                $this->inventoryService->receivePurchase($purchase);
            }
        });

        $message = $shouldReceiveImmediately
            ? 'Purchase created successfully and stock updated.'
            : 'Purchase created successfully.';

        return redirect()->route('purchases.index')
            ->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase): Response
    {
        $purchase->load([
            'supplier',
            'user',
            'purchaseItems.product.unit',
            'purchaseItems.product.panaflexSpec',
            'purchaseItems.stockBatches'
        ]);

        // Ensure purchaseItems is included in the response
        return Inertia::render('Purchases/Show', [
            'purchase' => array_merge($purchase->toArray(), [
                'purchaseItems' => $purchase->purchaseItems->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'rate' => $item->rate,
                        'line_total' => $item->line_total,
                        'received_quantity' => $item->received_quantity,
                        'rolls_count' => $item->rolls_count,
                        'roll_width_inch' => $item->roll_width_inch,
                        'roll_length_meter' => $item->roll_length_meter,
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'sku' => $item->product->sku,
                            'type' => $item->product->type,
                            'unit' => $item->product->unit ? [
                                'id' => $item->product->unit->id,
                                'name' => $item->product->unit->name,
                                'abbreviation' => $item->product->unit->abbreviation,
                            ] : null,
                        ] : null,
                    ];
                })->toArray()
            ])
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase): Response
    {
        $purchase->load(['supplier', 'purchaseItems.product.unit', 'purchaseItems.product.panaflexSpec']);

        return Inertia::render('Purchases/Form', [
            'purchase' => array_merge($purchase->toArray(), [
                'purchaseItems' => $purchase->purchaseItems->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'rate' => $item->rate,
                        'line_total' => $item->line_total,
                        'rolls_count' => $item->rolls_count,
                        'roll_width_inch' => $item->roll_width_inch,
                        'roll_length_meter' => $item->roll_length_meter,
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'sku' => $item->product->sku,
                            'type' => $item->product->type,
                            'stock_quantity' => $item->product->stock_quantity,
                            'unit' => $item->product->unit ? [
                                'id' => $item->product->unit->id,
                                'name' => $item->product->unit->name,
                                'abbreviation' => $item->product->unit->abbreviation,
                            ] : null,
                        ] : null,
                    ];
                })->toArray()
            ]),
            'suppliers' => Supplier::all(['id', 'name']),
            'products' => Product::with(['unit', 'panaflexSpec'])->where('active', true)->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        // Debug: Log request data
        \Log::info('Purchase Update Request Data:', [
            'purchase_id' => $purchase->id,
            'request_data' => $request->all(),
            'items_count' => count($request->input('items', [])),
            'current_items_count' => $purchase->purchaseItems()->count()
        ]);
        
        if (in_array($purchase->status, ['received', 'partial'])) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Cannot edit a purchase that has already been received.');
        }

        // For existing data safety, also restrict editing purchases that have created stock batches
        if ($purchase->purchaseItems()->whereHas('stockBatches')->exists()) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Cannot edit purchase that has created stock batches.');
        }

        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchased_at' => 'required|date',
            'expected_date' => 'nullable|date',
            'subtotal' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
            'tax_total' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'shipping_charges' => 'nullable|numeric|min:0',
            'grand_total' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'nullable|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.line_total' => 'required|numeric|min:0',
            'items.*.rolls_count' => 'nullable|integer|min:1',
            'items.*.roll_width_inch' => 'nullable|numeric|min:0.01',
            'items.*.roll_length_meter' => 'nullable|numeric|min:0.01',
        ]);

        $itemsCollection = collect($validated['items'])
            ->map(fn ($item) => $this->normalizeItemPayload($item));
        $products = Product::whereIn('id', $itemsCollection->pluck('product_id'))
            ->get()
            ->keyBy('id');

        foreach ($itemsCollection as $index => $item) {
            $product = $products->get($item['product_id']);

            if (!$product) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => 'The selected product is no longer available.',
                ]);
            }

            if ($product->type === 'simple' && (empty($item['quantity']) || $item['quantity'] < 0.01)) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => 'Quantity is required for simple products.',
                ]);
            }

            if ($product->type === 'panaflex_roll') {
                if (empty($item['rolls_count']) || empty($item['roll_length_meter']) || empty($item['roll_width_inch'])) {
                    throw ValidationException::withMessages([
                        "items.$index.rolls_count" => 'Roll details (count, width, length) are required for panaflex rolls.',
                    ]);
                }
            }
        }

        $validated['items'] = $itemsCollection->toArray();
        $validated = array_merge($validated, $this->calculateTotals($itemsCollection, $validated));

        DB::transaction(function () use ($validated, $purchase) {
            \Log::info('Purchase Update Transaction Started:', [
                'validated_items' => $validated['items'],
                'current_items_before_delete' => $purchase->purchaseItems()->get(['id', 'product_id', 'quantity'])->toArray()
            ]);
            
            // Update purchase
            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'purchased_at' => Carbon::parse($validated['purchased_at']),
                'expected_date' => $validated['expected_date'] ? Carbon::parse($validated['expected_date']) : null,
                'subtotal' => $validated['subtotal'],
                'discount_total' => $validated['discount_total'] ?? 0,
                'tax_total' => $validated['tax_total'] ?? 0,
                'other_charges' => $validated['other_charges'] ?? 0,
                'shipping_charges' => $validated['shipping_charges'] ?? 0,
                'grand_total' => $validated['grand_total'],
                'notes' => $validated['notes'],
            ]);

            \Log::info('Purchase header updated successfully');

            // Delete existing items and recreate
            $deletedCount = $purchase->purchaseItems()->count();
            $purchase->purchaseItems()->delete();
            \Log::info("Deleted {$deletedCount} existing purchase items");

            // Create new items
            foreach ($validated['items'] as $index => $item) {
                \Log::info("Creating purchase item {$index}:", $item);
                
                $created = $purchase->purchaseItems()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'line_total' => $item['line_total'],
                    'rolls_count' => $item['rolls_count'] ?? null,
                    'roll_width_inch' => $item['roll_width_inch'] ?? null,
                    'roll_length_meter' => $item['roll_length_meter'] ?? null,
                ]);
                
                \Log::info("Created purchase item:", $created->toArray());
            }
            
            $finalCount = $purchase->purchaseItems()->count();
            \Log::info("Transaction completed. Final items count: {$finalCount}");
        });

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase): RedirectResponse
    {
        DB::transaction(function () use ($purchase) {
            // 1. Reverse Stock
            $this->inventoryService->reversePurchase($purchase);

            // 2. Reverse Supplier Ledger (Delete Pending Payment)
            \App\Models\PendingPayment::where('purchase_id', $purchase->id)->delete();

            // 3. Delete Purchase (Items will be deleted via cascade usually, but let's be safe)
            $purchase->purchaseItems()->delete();
            $purchase->delete();
        });

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase deleted and stock reversed successfully.');
    }

    /**
     * Get purchases for DataTable
     */
    public function tableData(Request $request): JsonResponse
    {
        // Initial query
        $query = Purchase::with(['supplier', 'user']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('purchase_no', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Date filter
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('purchased_at', [
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_to)->endOfDay(),
            ]);
        }

        // Supplier filter
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Exclude Opening Balance entries (dummy purchases)
        $query->where('purchase_no', 'not like', 'OPB-%');

        // Sorting
        $sortColumn = $request->get('sort_by');
        $sortDirection = $request->get('sort_order', 'desc');

        $sortableColumns = [
            'po_number' => 'purchase_no',
            'order_date' => 'purchased_at',
            'total_amount' => 'grand_total',
            'status' => 'status'
        ];

        if ($sortColumn && array_key_exists($sortColumn, $sortableColumns)) {
            $query->orderBy($sortableColumns[$sortColumn], $sortDirection);
        } else {
            // Default sort: PO Number Sequence (Desc) to ensure invoices appear in creation sequence
            $query->orderBy('purchase_no', 'desc');
        }

        // Get total count before pagination
        $totalRecords = $query->count();

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $purchases = $query->with(['purchaseItems.product'])
            ->withCount('purchaseItems')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'po_number' => $purchase->purchase_no,
                    'purchase_no' => $purchase->purchase_no,
                    'supplier' => $purchase->supplier,
                    'total_amount' => $purchase->grand_total,
                    'grand_total' => $purchase->grand_total,
                    'status' => $purchase->status,
                    'order_date' => $purchase->purchased_at,
                    'purchased_at' => $purchase->purchased_at,
                    'expected_date' => $purchase->expected_date,
                    'notes' => $purchase->notes,
                    'user' => $purchase->user,
                    'purchase_items_count' => $purchase->purchase_items_count,
                    'items' => $purchase->purchaseItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => $item->product,
                            'quantity' => $item->quantity,
                            'received_quantity' => $item->received_quantity ?? 0,
                            'rate' => $item->rate,
                            'line_total' => $item->line_total,
                            'roll_width_inch' => $item->roll_width_inch,
                            'roll_length_meter' => $item->roll_length_meter,
                            'rolls_count' => $item->rolls_count,
                        ];
                    }),
                ];
            });

        return response()->json([
            'data' => $purchases,
            'total' => $totalRecords,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($totalRecords / $perPage),
        ]);
    }

    /**
     * Export purchases to Excel
     */
    public function export()
    {
        return Excel::download(new PurchasesExport, 'purchases.xlsx');
    }

    /**
     * Receive purchase items
     */
    public function receive(Request $request, Purchase $purchase): RedirectResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.batch_code' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($validated, $purchase) {
            foreach ($validated['items'] as $itemData) {
                $purchaseItem = PurchaseItem::findOrFail($itemData['purchase_item_id']);
                
                // Update received quantity
                $purchaseItem->received_quantity = ($purchaseItem->received_quantity ?? 0) + $itemData['quantity'];
                $purchaseItem->save();

                // Update inventory through service
                if ($itemData['quantity'] > 0) {
                    $this->inventoryService->receivePurchaseItem(
                        $purchaseItem,
                        $itemData['quantity'],
                        $itemData['batch_code'] ?? null,
                        $itemData['expiry_date'] ?? null
                    );
                }
            }

            // Update purchase status based on received quantities
            $totalOrdered = $purchase->purchaseItems->sum('quantity');
            $totalReceived = $purchase->purchaseItems->sum('received_quantity');

            if ($totalReceived >= $totalOrdered) {
                $purchase->status = 'received';
            } elseif ($totalReceived > 0) {
                $purchase->status = 'partial';
            }
            $purchase->save();
        });

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase items received successfully and inventory updated.');
    }

    /**
     * Cancel a purchase order
     */
    public function cancel(Purchase $purchase): RedirectResponse
    {
        if (!in_array($purchase->status, ['pending', 'ordered'])) {
            return redirect()->route('purchases.index')
                ->with('error', 'Only pending or ordered purchases can be cancelled.');
        }

        $purchase->update(['status' => 'cancelled']);

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase order cancelled successfully.');
    }

    /**
     * Update purchase status
     */
    public function updateStatus(Request $request, Purchase $purchase): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,pending,ordered,received,cancelled'
        ]);

        // Prevent changing status if already received
        if ($purchase->status === 'received') {
            return redirect()->back()
                ->with('error', 'Cannot change status of a received purchase. Please delete the purchase to reverse it.');
        }

        $oldStatus = $purchase->status;
        $newStatus = $validated['status'];

        // Update purchase status
        $purchase->update(['status' => $newStatus]);

        // If status changed to "received", add items to inventory
        if ($newStatus === 'received' && $oldStatus !== 'received') {
            // Use the centralized inventory service to handle receiving
            // This ensures consistent logic for stock updates and batch creation
            $this->inventoryService->receivePurchase($purchase);

            // Also mark all items as fully received
            foreach ($purchase->purchaseItems as $item) {
                $item->update([
                    'received_quantity' => $item->quantity
                ]);
            }

            return redirect()->route('purchases.index')
                ->with('success', 'Purchase status updated to Received. Items added to inventory successfully.');
        }

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase status updated successfully.');
    }

    /**
     * Normalize numeric fields for each item to ensure consistent calculations.
     */
    private function normalizeItemPayload(array $item): array
    {
        $quantity = array_key_exists('quantity', $item) && $item['quantity'] !== '' && $item['quantity'] !== null
            ? (float) $item['quantity']
            : null;

        $rate = round((float) ($item['rate'] ?? 0), 2);

        $lineTotal = $quantity !== null
            ? round($quantity * $rate, 2)
            : round((float) ($item['line_total'] ?? 0), 2);

        // Ensure exact units are used directly
        $rollWidthInch = $item['roll_width_inch'] ?? null;
        $rollLengthMeter = $item['roll_length_meter'] ?? null;

        return [
            'product_id' => $item['product_id'],
            'quantity' => $quantity,
            'rate' => $rate,
            'line_total' => $lineTotal,
            'rolls_count' => $item['rolls_count'] ?? null,
            'roll_width_inch' => $rollWidthInch,
            'roll_length_meter' => $rollLengthMeter,
        ];
    }

    /**
     * Recalculate subtotal, tax and grand total on the server to avoid stale client values.
     */
    private function calculateTotals(Collection $items, array $validated): array
    {
        $subtotal = $items->sum(fn ($item) => (float) ($item['line_total'] ?? 0));
        $discount = (float) ($validated['discount_total'] ?? 0);
        $shipping = (float) ($validated['shipping_charges'] ?? 0);
        $other = (float) ($validated['other_charges'] ?? 0);
        $taxRate = (float) ($validated['tax_rate'] ?? 0);

        $taxable = max($subtotal - $discount, 0);
        $taxTotal = round($taxRate > 0 ? $taxable * ($taxRate / 100) : 0, 2);
        $grandTotal = round($subtotal + $taxTotal + $shipping + $other - $discount, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
        ];
    }
}
