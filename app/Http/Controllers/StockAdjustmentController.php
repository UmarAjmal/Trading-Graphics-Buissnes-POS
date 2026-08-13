<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockBatch;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class StockAdjustmentController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Show the form for creating a new stock adjustment.
     */
    public function create()
    {
        $products = Product::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'stock_quantity', 'type']);

        return Inertia::render('StockAdjustments/Create', [
            'products' => $products
        ]);
    }

    /**
     * Store a newly created stock adjustment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_id' => 'nullable|exists:stock_batches,id',
            'delta' => 'required|numeric|not_in:0',
            'reason' => 'required|in:damage,shrinkage,correction,other',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $product = Product::findOrFail($validated['product_id']);
            
            // Validate batch belongs to product if specified
            if (isset($validated['batch_id']) && $validated['batch_id']) {
                $batch = StockBatch::where('id', $validated['batch_id'])
                    ->where('product_id', $product->id)
                    ->firstOrFail();
            }

            $this->inventoryService->adjust(
                $product,
                $validated['delta'],
                $validated['batch_id'] ?? null,
                $validated['reason'],
                $validated['notes'] ?? null
            );

            return redirect()->route('inventory.index')
                ->with('success', 'Stock adjustment applied successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to apply adjustment: ' . $e->getMessage()]);
        }
    }

    /**
     * Get available batches for a product
     */
    public function getBatches(Product $product): JsonResponse
    {
        $batches = StockBatch::where('product_id', $product->id)
            ->orderBy('received_at', 'desc')
            ->get()
            ->map(function ($batch) use ($product) {
                if ($product->type === 'simple') {
                    return [
                        'id' => $batch->id,
                        'label' => $batch->batch_no . ' (' . $batch->qty_remaining . ' pcs remaining)',
                        'batch_no' => $batch->batch_no,
                        'remaining' => $batch->qty_remaining,
                        'unit' => 'pcs',
                    ];
                } else {
                    return [
                        'id' => $batch->id,
                        'label' => $batch->batch_no . ' (' . number_format($batch->meters_remaining, 2) . ' m remaining)',
                        'batch_no' => $batch->batch_no,
                        'remaining' => $batch->meters_remaining,
                        'unit' => 'm',
                    ];
                }
            });

        return response()->json($batches);
    }
}
