<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMove;
use App\Models\StockAdjustment;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display inventory dashboard
     */
    public function index(): Response
    {
        // Get inventory statistics
        $stats = [
            'total_products' => Product::count(),
            'low_stock_products' => Product::where(function ($query) {
                $query->where('type', 'simple')
                      ->whereColumn('stock_quantity', '<=', 'min_qty')
                      ->orWhere(function ($subQuery) {
                          $subQuery->where('type', 'panaflex_roll')
                                   ->whereColumn('stock_meters', '<=', 'min_meters');
                      });
            })->count(),
            'out_of_stock_products' => Product::where(function ($query) {
                $query->where('type', 'simple')
                      ->where('stock_quantity', '<=', 0)
                      ->orWhere(function ($subQuery) {
                          $subQuery->where('type', 'panaflex_roll')
                                   ->where('stock_meters', '<=', 0);
                      });
            })->count(),
            'total_stock_value' => Product::where('active', true)
                ->sum(DB::raw('
                    CASE
                        WHEN products.type = "simple" THEN products.stock_quantity * products.purchase_rate
                        WHEN products.type = "panaflex_roll" THEN products.stock_meters * products.purchase_rate
                        ELSE 0
                    END
                ')),
        ];

        // Get categories and suppliers for filters
        $categories = \App\Models\Category::select('id', 'name')->orderBy('name')->get();
        $suppliers = \App\Models\Supplier::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Inventory/Index', [
            'stats' => $stats,
            'categories' => $categories,
            'suppliers' => $suppliers
        ]);
    }

    /**
     * Get inventory data for tables
     */
    public function getData(Request $request): JsonResponse
    {
        $type = $request->get('type', 'all'); // all, panaflex, simple, low_stock, adjustments

        switch ($type) {
            case 'all':
                return $this->getAllInventoryData($request);
            case 'panaflex':
                return $this->getPanaflexData($request);
            case 'simple':
                return $this->getSimpleData($request);
            case 'low_stock':
                return $this->getLowStockData($request);
            case 'adjustments':
                return $this->getAdjustmentsData($request);
            default:
                return $this->getAllInventoryData($request);
        }
    }

    /**
     * Get all inventory data (general overview)
     */
    private function getAllInventoryData(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'unit', 'panaflexSpec', 'stockBatches']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Stock status filter
        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'low_stock':
                    $query->where(function ($q) {
                        $q->where(function ($subQuery) {
                            $subQuery->where('type', 'simple')
                                     ->whereColumn('stock_quantity', '<=', 'min_qty');
                        })->orWhere(function ($subQuery) {
                            $subQuery->where('type', 'panaflex_roll')
                                     ->whereColumn('stock_meters', '<=', 'min_meters');
                        });
                    });
                    break;
                case 'out_of_stock':
                    $query->where(function ($q) {
                        $q->where(function ($subQuery) {
                            $subQuery->where('type', 'simple')
                                     ->where('stock_quantity', '<=', 0);
                        })->orWhere(function ($subQuery) {
                            $subQuery->where('type', 'panaflex_roll')
                                     ->where('stock_meters', '<=', 0);
                        });
                    });
                    break;
                case 'in_stock':
                    $query->where(function ($q) {
                        $q->where(function ($subQuery) {
                            $subQuery->where('type', 'simple')
                                     ->where('stock_quantity', '>', 0);
                        })->orWhere(function ($subQuery) {
                            $subQuery->where('type', 'panaflex_roll')
                                     ->where('stock_meters', '>', 0);
                        });
                    });
                    break;
            }
        }

        // Supplier filter
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $products = $query->get()->map(function (Product $product) {
            // Use direct database fields instead of service summary for performance
            if ($product->type === 'panaflex_roll') {
                $currentStockValue = $product->stock_meters ?? 0;
                $minMetersValue = $product->min_meters ?? 0;
                
                $widthFt = 1;
                if ($product->panaflexSpec && $product->panaflexSpec->roll_width_inch) {
                    $widthFt = $product->panaflexSpec->roll_width_inch / 12;
                }
                
                $currentStockSqFt = $currentStockValue * 3.28 * $widthFt;
                $minStockSqFt = $minMetersValue * 3.28 * $widthFt;
                
                $currentStock = number_format($currentStockSqFt, 2) . ' Sq.Ft';
                $minStock = number_format($minStockSqFt, 2) . ' Sq.Ft';
                
                $stockStatus = $currentStockValue <= 0 ? 'Out of Stock' : 
                              ($currentStockValue <= $minMetersValue ? 'Low Stock' : 'In Stock');
                
                // Value = Meters * Rate
                $stockValue = $currentStockValue * ($product->purchase_rate ?? 0);
            } else {
                $currentStockValue = $product->stock_quantity ?? 0;
                $currentStock = number_format($currentStockValue, 0) . ' ' . ($product->unit->code ?? 'PCS');
                $minStock = number_format($product->min_qty ?? 0, 0) . ' ' . ($product->unit->code ?? 'PCS');
                $stockStatus = $currentStockValue <= 0 ? 'Out of Stock' : 
                              ($currentStockValue <= ($product->min_qty ?? 0) ? 'Low Stock' : 'In Stock');
                $stockValue = $currentStockValue * ($product->purchase_rate ?? 0);
            }

            // Get available batches count
            $availableBatches = $product->stockBatches()->where(function ($query) use ($product) {
                if ($product->type === 'panaflex_roll') {
                    $query->where('meters_remaining', '>', 0);
                } else {
                    $query->where('qty_remaining', '>', 0);
                }
            })->count();

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => [
                    'name' => $product->category->name ?? 'Uncategorized'
                ],
                'type' => ucfirst(str_replace('_', ' ', $product->type)),
                'stock_quantity' => $currentStock,
                'min_stock' => $minStock,
                'stock_quantity_raw' => $currentStockValue,
                'min_stock_raw' => $product->type === 'panaflex_roll' ? ($product->min_meters ?? 0) : ($product->min_qty ?? 0),
                'unit_code' => $product->unit->code ?? ($product->type === 'panaflex_roll' ? 'M' : 'PCS'),
                'unit' => $product->unit->code ?? ($product->type === 'panaflex_roll' ? 'M' : 'PCS'),
                'stock_status' => $stockStatus,
                'stock_value' => $stockValue,
                'available_batches' => $availableBatches,
            ];
        });

        return response()->json([
            'data' => $products,
            'total' => $products->count(),
        ]);
    }

    /**
     * Calculate stock value for a product
     */
    private function calculateStockValue($product, $summary)
    {
        if ($product->type === 'panaflex_roll') {
            return ($summary['remaining_meters'] ?? 0) * ($product->purchase_rate ?? 0);
        } else {
            return ($summary['remaining_qty'] ?? 0) * ($product->purchase_rate ?? 0);
        }
    }

    /**
     * Get panaflex inventory data
     */
    private function getPanaflexData(Request $request): JsonResponse
    {
        $query = Product::where('type', 'panaflex_roll')
            ->with(['category', 'panaflexSpec', 'stockBatches']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->get()->map(function (Product $product) {
            $summary = $this->inventoryService->getStockSummary($product);
            $remainingMeters = $summary['remaining_meters'] ?? 0;
            $minMetersValue = $product->min_meters ?? 0;
            
            $widthFt = 1;
            if ($product->panaflexSpec && $product->panaflexSpec->roll_width_inch) {
                $widthFt = $product->panaflexSpec->roll_width_inch / 12;
            }
            
            $remainingSqFt = $remainingMeters * 3.28 * $widthFt;
            $minSqFt = $minMetersValue * 3.28 * $widthFt;
            
            // Value = Meters * Rate
            $stockValue = $remainingMeters * ($product->purchase_rate ?? 0);
            
            $stockStatus = $remainingMeters <= 0 ? 'Out of Stock' : 
                          ($remainingMeters <= $minMetersValue ? 'Low Stock' : 'In Stock');

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => [
                    'name' => $product->category->name ?? 'Uncategorized'
                ],
                'type' => 'Panaflex Roll',
                'stock_quantity' => number_format($remainingSqFt, 2) . ' Sq.Ft',
                'min_stock' => number_format($minSqFt, 2) . ' Sq.Ft',
                'stock_quantity_raw' => $remainingMeters, // Keep meters for raw if needed elsewhere
                'min_stock_raw' => $minMetersValue,
                'stock_value' => $stockValue,
                'stock_status' => $stockStatus,
                'available_batches' => $summary['available_batches'] ?? 0,
                
                // Extra fields specific to panaflex view if needed
                'roll_width_inch' => $product->panaflexSpec->roll_width_inch ?? 0,
                'roll_width_ft' => round($widthFt, 2),
                'remaining_meters' => $remainingMeters,
            ];
        });

        return response()->json([
            'data' => $products,
            'total' => $products->count(),
        ]);
    }

    /**
     * Get simple inventory data
     */
    private function getSimpleData(Request $request): JsonResponse
    {
        $query = Product::where('type', 'simple')
            ->with(['category', 'unit', 'stockBatches']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->get()->map(function (Product $product) {
            $summary = $this->inventoryService->getStockSummary($product);
            $remainingQty = $summary['remaining_qty'] ?? 0;
            
            $stockValue = $remainingQty * ($product->purchase_rate ?? 0);
            
            $stockStatus = $remainingQty <= 0 ? 'Out of Stock' : 
                          ($remainingQty <= ($product->min_qty ?? 0) ? 'Low Stock' : 'In Stock');

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => [
                    'name' => $product->category->name ?? 'Uncategorized'
                ],
                'type' => 'Simple',
                'stock_quantity' => number_format($remainingQty, 0) . ' ' . ($product->unit->code ?? 'PCS'),
                'min_stock' => number_format($product->min_qty ?? 0, 0) . ' ' . ($product->unit->code ?? 'PCS'),
                'stock_quantity_raw' => $remainingQty,
                'min_stock_raw' => $product->min_qty ?? 0,
                'stock_value' => $stockValue,
                'stock_status' => $stockStatus,
                'available_batches' => $summary['available_batches'] ?? 0,
                
                // Extra fields
                'unit' => $product->unit->code ?? 'PCS',
                'total_qty' => $summary['total_qty'] ?? 0,
                'remaining_qty' => $remainingQty,
            ];
        });

        return response()->json([
            'data' => $products,
            'total' => $products->count(),
        ]);
    }

    /**
     * Get low stock data
     */
    private function getLowStockData(Request $request): JsonResponse
    {
        $lowStock = $this->inventoryService->getLowStockProducts();
        
        $data = collect();
        
        // Add simple products
        foreach ($lowStock['simple'] as $product) {
            $summary = $this->inventoryService->getStockSummary($product);
            $data->push([
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'type' => 'Simple',
                'current_stock' => $summary['remaining_qty'] . ' ' . ($product->unit->code ?? 'PCS'),
                'min_stock' => $product->min_qty . ' ' . ($product->unit->code ?? 'PCS'),
                'shortage' => ($product->min_qty - $summary['remaining_qty']) . ' ' . ($product->unit->code ?? 'PCS'),
            ]);
        }

        // Add panaflex products
        foreach ($lowStock['panaflex'] as $product) {
            $summary = $this->inventoryService->getStockSummary($product);
            
            $widthFt = 1;
            if ($product->panaflexSpec && $product->panaflexSpec->roll_width_inch) {
                $widthFt = $product->panaflexSpec->roll_width_inch / 12;
            }
            
            $currentSqFt = ($summary['remaining_meters'] ?? 0) * 3.28 * $widthFt;
            $minSqFt = ($product->min_meters ?? 0) * 3.28 * $widthFt;
            $shortageSqFt = max(0, $minSqFt - $currentSqFt);
            
            $data->push([
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'type' => 'Panaflex Roll',
                'current_stock' => number_format($currentSqFt, 2) . ' Sq.Ft',
                'min_stock' => number_format($minSqFt, 2) . ' Sq.Ft',
                'shortage' => number_format($shortageSqFt, 2) . ' Sq.Ft',
            ]);
        }

        return response()->json([
            'data' => $data,
            'total' => $data->count(),
        ]);
    }

    /**
     * Get adjustments data
     */
    private function getAdjustmentsData(Request $request): JsonResponse
    {
        $query = StockAdjustment::with(['product', 'user']);

        // Date filter
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($request->date_from)->startOfDay(),
                \Carbon\Carbon::parse($request->date_to)->endOfDay(),
            ]);
        }

        $adjustments = $query->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($adjustment) {
                return [
                    'id' => $adjustment->id,
                    'product_name' => $adjustment->product->name,
                    'product_sku' => $adjustment->product->sku,
                    'reason' => $adjustment->reason_label,
                    'delta' => $adjustment->formatted_delta,
                    'note' => $adjustment->note,
                    'user_name' => $adjustment->user->name,
                    'created_at' => $adjustment->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'data' => $adjustments,
            'total' => $adjustments->count(),
        ]);
    }

    /**
     * Get batches for a product
     */
    public function getBatches(Product $product): JsonResponse
    {
        $batches = StockBatch::where('product_id', $product->id)
            ->with(['purchaseItem.purchase.supplier'])
            ->orderBy('received_at', 'desc')
            ->get()
            ->map(function ($batch) use ($product) {
                $widthInches = $batch->roll_width_inch ?? $product->panaflexSpec->roll_width_inch ?? 12;
                $widthFt = $widthInches / 12;
                $remainingSqFt = $batch->meters_remaining * 3.28 * $widthFt;

                $data = [
                    'id' => $batch->id,
                    'batch_no' => $batch->batch_no,
                    'batch_code' => $batch->batch_no,
                    'received_at' => $batch->received_at,
                    'created_at' => $batch->created_at,
                    'supplier' => $batch->purchaseItem->purchase->supplier->name ?? 'Unknown',
                    'purchase_no' => $batch->purchaseItem->purchase->purchase_no ?? 'N/A',
                    'unit_cost' => $batch->purchaseItem->rate ?? $product->purchase_rate ?? 0,
                    'expiry_date' => null,
                ];

                if ($product->type === 'simple') {
                    $data['qty_total'] = $batch->qty_total;
                    $data['qty_remaining'] = $batch->qty_remaining;
                    $data['quantity'] = $batch->qty_remaining;
                    $data['status'] = $batch->qty_remaining > 0 ? 'Available' : 'Empty';
                } else {
                    $data['meters_total'] = number_format($batch->meters_total, 2);
                    $data['meters_remaining'] = number_format($batch->meters_remaining, 2);
                    $data['roll_width_inch'] = $batch->roll_width_inch;
                    $data['quantity'] = number_format($remainingSqFt, 2) . ' Sq.Ft';
                    $data['status'] = $batch->meters_remaining > 0 ? 'Available' : 'Empty';
                }

                return $data;
            });

        return response()->json($batches);
    }

    /**
     * Get stock history for a product
     */
    public function getHistory(Product $product): JsonResponse
    {
        $moves = StockMove::where('product_id', $product->id)
            ->with(['user', 'stockBatch'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($move) {
                return [
                    'id' => $move->id,
                    'type' => ucfirst($move->type),
                    'change' => $move->formatted_change,
                    'batch_no' => $move->stockBatch->batch_no ?? 'N/A',
                    'note' => $move->note,
                    'user_name' => $move->user->name ?? 'System',
                    'created_at' => $move->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json($moves);
    }
}


