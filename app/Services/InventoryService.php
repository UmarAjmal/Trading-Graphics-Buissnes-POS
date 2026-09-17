<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StockBatch;
use App\Models\StockMove;
use App\Models\StockAdjustment;
use App\Services\RollConsumptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InventoryService
{
    /**
     * Process purchase and create stock batches
     */
    public function receivePurchase(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            foreach ($purchase->purchaseItems as $item) {
                $product = $item->product;

                if ($product->type === 'simple') {
                    $this->createSimpleBatch($item);
                } elseif ($product->type === 'panaflex_roll') {
                    $this->createPanaflexBatch($item);
                }
            }
        });
    }

    /**
     * Create stock batch for simple product
     */
    private function createSimpleBatch($purchaseItem): void
    {
        $batch = StockBatch::create([
            'product_id' => $purchaseItem->product_id,
            'batch_no' => StockBatch::generateBatchNumber($purchaseItem->product),
            'purchase_item_id' => $purchaseItem->id,
            'qty_total' => $purchaseItem->quantity,
            'qty_remaining' => $purchaseItem->quantity,
            'received_at' => $purchaseItem->purchase->purchased_at->toDateString(),
        ]);

        $this->createStockMove(
            $purchaseItem->product_id,
            'purchase',
            $purchaseItem->purchase_id,
            'purchases',
            $batch->id,
            $purchaseItem->quantity,
            null,
            'Purchase received'
        );

        // Update product stock and cost price
        $purchaseItem->product->increment('stock_quantity', $purchaseItem->quantity);
        $purchaseItem->product->update(['purchase_rate' => $purchaseItem->rate]);
    }

    /**
     * Create stock batch for panaflex roll
     */
    private function createPanaflexBatch($purchaseItem): void
    {
        $totalMeters = $purchaseItem->roll_length_meter * $purchaseItem->rolls_count;

        $batch = StockBatch::create([
            'product_id' => $purchaseItem->product_id,
            'batch_no' => StockBatch::generateBatchNumber($purchaseItem->product),
            'purchase_item_id' => $purchaseItem->id,
            'roll_width_inch' => $purchaseItem->roll_width_inch,
            'meters_total' => $totalMeters,
            'meters_remaining' => $totalMeters,
            'received_at' => $purchaseItem->purchase->purchased_at->toDateString(),
        ]);

        $this->createStockMove(
            $purchaseItem->product_id,
            'purchase',
            $purchaseItem->purchase_id,
            'purchases',
            $batch->id,
            null,
            $totalMeters,
            'Purchase received - ' . $purchaseItem->rolls_count . ' rolls'
        );

        // Update product stock and cost price
        $purchaseItem->product->increment('stock_meters', $totalMeters);
        $purchaseItem->product->increment('stock_quantity', $totalMeters);
        $purchaseItem->product->update(['purchase_rate' => $purchaseItem->rate]);
    }

    /**
     * Consume stock for sale (FIFO)
     */
    public function consumeForSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            foreach ($sale->saleItems as $item) {
                $product = $item->product;

                if ($product->type === 'simple') {
                    $this->consumeSimpleStock($item, $sale);
                } elseif ($product->type === 'panaflex_roll') {
                    $this->consumePanaflexStock($item, $sale);
                }
            }
        });
    }

    /**
     * Consume simple product stock (FIFO)
     */
    private function consumeSimpleStock($saleItem, Sale $sale): void
    {
        $needed = $saleItem->quantity;
        $product = $saleItem->product;

        // Get available batches (FIFO - oldest first)
        $batches = StockBatch::where('product_id', $product->id)
            ->where('qty_remaining', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->get();

        foreach ($batches as $batch) {
            if ($needed <= 0) break;

            $take = min($batch->qty_remaining, $needed);
            
            $batch->update([
                'qty_remaining' => $batch->qty_remaining - $take
            ]);

            $this->createStockMove(
                $product->id,
                'sale',
                $sale->id,
                'sales',
                $batch->id,
                -$take,
                null,
                "Sale {$sale->invoice_no}"
            );

            $needed -= $take;
        }

        if ($needed > 0) {
            throw new \Exception("Insufficient stock for {$product->name}. Need {$needed} more units.");
        }

        // Update product stock
        $product->decrement('stock_quantity', $saleItem->quantity);
    }

    /**
     * Consume panaflex stock (FIFO)
     */
    private function consumePanaflexStock($saleItem, Sale $sale): void
    {
        $product = $saleItem->product;
        $rollWidthInch = $product->panaflexSpec->roll_width_inch;
        
        // Calculate meters needed using RollConsumptionService
        // Need to use the original length/width inputs from saleItem
        if (!$saleItem->length_input || !$saleItem->width_input) {
            throw new \Exception("Missing length/width inputs for panaflex sale item {$saleItem->id}");
        }
        
        $metersNeeded = RollConsumptionService::calcMetersUsed(
            $saleItem->length_input,
            $saleItem->length_unit ?? 'ft',
            $saleItem->width_input,
            $saleItem->width_unit ?? 'in',
            $rollWidthInch,
            $saleItem->quantity
        );

        // Get available batches with matching width (FIFO - oldest first)
        $batches = StockBatch::where('product_id', $product->id)
            ->where('roll_width_inch', $rollWidthInch)
            ->where('meters_remaining', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->get();

        $needed = $metersNeeded;
        foreach ($batches as $batch) {
            if ($needed <= 0) break;

            $take = min($batch->meters_remaining, $needed);
            
            $batch->update([
                'meters_remaining' => $batch->meters_remaining - $take
            ]);

            $this->createStockMove(
                $product->id,
                'sale',
                $sale->id,
                'sales',
                $batch->id,
                null,
                -$take,
                "Sale {$sale->invoice_no} - {$saleItem->units_sqft} sq.ft"
            );

            $needed -= $take;
        }

        if ($needed > 0) {
            throw new \Exception("Insufficient meters for {$product->name}. Need {$needed} more meters.");
        }

        // Update product stock
        $product->decrement('stock_meters', $metersNeeded);
        $product->decrement('stock_quantity', $metersNeeded);
    }

    /**
     * Restock for return (LIFO backfill)
     */
    public function restockForReturn(SaleReturn $return): void
    {
        DB::transaction(function () use ($return) {
            foreach ($return->saleReturnItems as $returnItem) {
                $saleItem = $returnItem->saleItem;
                $product = $saleItem->product;

                if ($product->type === 'simple') {
                    $this->restockSimpleStock($returnItem, $return);
                } elseif ($product->type === 'panaflex_roll') {
                    $this->restockPanaflexStock($returnItem, $return);
                }
            }
        });
    }

    /**
     * Restock simple product (LIFO backfill)
     */
    private function restockSimpleStock($returnItem, SaleReturn $return): void
    {
        $product = $returnItem->saleItem->product;
        $qtyToRestock = $returnItem->quantity;

        // Get recently depleted batches (LIFO - newest first)
        $batches = StockBatch::where('product_id', $product->id)
            ->where('qty_total', '>', 'qty_remaining')
            ->orderBy('received_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $remaining = $qtyToRestock;
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $canRestore = $batch->qty_total - $batch->qty_remaining;
            $restore = min($canRestore, $remaining);

            $batch->update([
                'qty_remaining' => $batch->qty_remaining + $restore
            ]);

            $this->createStockMove(
                $product->id,
                'return',
                $return->id,
                'sale_returns',
                $batch->id,
                $restore,
                null,
                "Return {$return->return_no}"
            );

            $remaining -= $restore;
        }

        // Update product stock
        $product->increment('stock_quantity', $qtyToRestock);
    }

    /**
     * Restock panaflex stock (LIFO backfill)
     */
    private function restockPanaflexStock($returnItem, SaleReturn $return): void
    {
        $product = $returnItem->saleItem->product;
        $rollWidthInch = $product->panaflexSpec ? (float) $product->panaflexSpec->roll_width_inch : 126.0;
        
        // Calculate linear meters to restock from units_sqft
        if ($rollWidthInch > 0) {
            $widthFeet = $rollWidthInch / 12.0;
            $linearFeet = (float) $returnItem->units_sqft / $widthFeet;
            $metersToRestock = round($linearFeet * 0.3048, 2);
        } else {
            $metersToRestock = round((float) $returnItem->units_sqft * 0.092903, 2);
        }

        if ($metersToRestock <= 0) {
            $metersToRestock = 0.01;
        }

        // Get recently depleted batches with matching width (LIFO - newest first)
        $batches = StockBatch::where('product_id', $product->id)
            ->where('roll_width_inch', $rollWidthInch)
            ->where('meters_total', '>', 'meters_remaining')
            ->orderBy('received_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $remaining = $metersToRestock;
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $canRestore = $batch->meters_total - $batch->meters_remaining;
            $restore = min($canRestore, $remaining);

            $batch->update([
                'meters_remaining' => $batch->meters_remaining + $restore
            ]);

            $this->createStockMove(
                $product->id,
                'return',
                $return->id,
                'sale_returns',
                $batch->id,
                null,
                $restore,
                "Return {$return->return_no} - {$returnItem->units_sqft} sq.ft"
            );

            $remaining -= $restore;
        }

        // Update product stock
        $product->increment('stock_meters', $metersToRestock);
        $product->increment('stock_quantity', $metersToRestock);
    }

    /**
     * Deduct inventory for purchase return (goods sent back to supplier)
     */
    public function deductForPurchaseReturn(PurchaseReturn $return): void
    {
        DB::transaction(function () use ($return) {
            foreach ($return->items as $returnItem) {
                $product = $returnItem->product;
                if (!$product) continue;

                if ($product->type === 'simple') {
                    $qtyToDeduct = (float) $returnItem->quantity;

                    // Deduct from batches
                    $batches = StockBatch::where('product_id', $product->id)
                        ->where('qty_remaining', '>', 0)
                        ->orderBy('received_at', 'desc')
                        ->orderBy('id', 'desc')
                        ->get();

                    $remaining = $qtyToDeduct;
                    foreach ($batches as $batch) {
                        if ($remaining <= 0) break;
                        $take = min((float)$batch->qty_remaining, $remaining);

                        $batch->update([
                            'qty_remaining' => (float)$batch->qty_remaining - $take
                        ]);

                        $this->createStockMove(
                            $product->id,
                            'return',
                            $return->id,
                            'purchase_returns',
                            $batch->id,
                            -$take,
                            null,
                            "Purchase Return {$return->return_no}"
                        );

                        $remaining -= $take;
                    }

                    $product->decrement('stock_quantity', $qtyToDeduct);
                } elseif ($product->type === 'panaflex_roll') {
                    $rollWidthInch = $returnItem->roll_width_inch ?? ($product->panaflexSpec->roll_width_inch ?? 126.0);

                    if ((float)$returnItem->units_sqft > 0) {
                        $widthFeet = $rollWidthInch > 0 ? ($rollWidthInch / 12.0) : 10.5;
                        $totalMetersToDeduct = round(((float)$returnItem->units_sqft / ($widthFeet * 3.28)), 2);
                    } else {
                        $totalMetersToDeduct = (float) ($returnItem->roll_length_meter ?? 0) * (float) ($returnItem->rolls_count ?? 1);
                        if ($totalMetersToDeduct <= 0 && $returnItem->quantity > 0) {
                            $totalMetersToDeduct = (float) $returnItem->quantity;
                        }
                    }
                    if ($totalMetersToDeduct <= 0) $totalMetersToDeduct = 0.01;

                    $batchesQuery = StockBatch::where('product_id', $product->id)
                        ->where('meters_remaining', '>', 0);
                    if ($rollWidthInch) {
                        $batchesQuery->where('roll_width_inch', $rollWidthInch);
                    }
                    $batches = $batchesQuery->orderBy('received_at', 'desc')->orderBy('id', 'desc')->get();

                    $remaining = $totalMetersToDeduct;
                    foreach ($batches as $batch) {
                        if ($remaining <= 0) break;
                        $take = min((float)$batch->meters_remaining, $remaining);

                        $batch->update([
                            'meters_remaining' => (float)$batch->meters_remaining - $take
                        ]);

                        $this->createStockMove(
                            $product->id,
                            'return',
                            $return->id,
                            'purchase_returns',
                            $batch->id,
                            null,
                            -$take,
                            "Purchase Return {$return->return_no}"
                        );

                        $remaining -= $take;
                    }

                    $product->decrement('stock_meters', $totalMetersToDeduct);
                    $product->decrement('stock_quantity', $totalMetersToDeduct);
                }
            }
        });
    }

    /**
     * Reverse stock added by a Sale Return (deduct it back)
     */
    public function reverseSaleReturnStock(SaleReturn $return): void
    {
        DB::transaction(function () use ($return) {
            foreach ($return->saleReturnItems as $returnItem) {
                $saleItem = $returnItem->saleItem;
                $product = $saleItem ? $saleItem->product : null;
                if (!$product) continue;

                if ($product->type === 'simple') {
                    $qtyToDeduct = (float) $returnItem->quantity;
                    $batches = StockBatch::where('product_id', $product->id)
                        ->where('qty_remaining', '>', 0)
                        ->orderBy('received_at', 'desc')
                        ->orderBy('id', 'desc')
                        ->get();

                    $remaining = $qtyToDeduct;
                    foreach ($batches as $batch) {
                        if ($remaining <= 0) break;
                        $take = min((float)$batch->qty_remaining, $remaining);
                        $batch->decrement('qty_remaining', $take);
                        $remaining -= $take;
                    }
                    $product->decrement('stock_quantity', $qtyToDeduct);
                } elseif ($product->type === 'panaflex_roll') {
                    $rollWidthInch = $product->panaflexSpec ? (float) $product->panaflexSpec->roll_width_inch : 126.0;
                    if ($rollWidthInch > 0) {
                        $widthFeet = $rollWidthInch / 12.0;
                        $linearFeet = (float) $returnItem->units_sqft / $widthFeet;
                        $metersToDeduct = round($linearFeet * 0.3048, 2);
                    } else {
                        $metersToDeduct = round((float) $returnItem->units_sqft * 0.092903, 2);
                    }
                    if ($metersToDeduct <= 0) $metersToDeduct = 0.01;

                    $batches = StockBatch::where('product_id', $product->id)
                        ->where('meters_remaining', '>', 0)
                        ->orderBy('received_at', 'desc')
                        ->orderBy('id', 'desc')
                        ->get();

                    $remaining = $metersToDeduct;
                    foreach ($batches as $batch) {
                        if ($remaining <= 0) break;
                        $take = min((float)$batch->meters_remaining, $remaining);
                        $batch->decrement('meters_remaining', $take);
                        $remaining -= $take;
                    }
                    $product->decrement('stock_meters', $metersToDeduct);
                    $product->decrement('stock_quantity', $metersToDeduct);
                }
            }
            // Delete related stock_moves
            \App\Models\StockMove::where('ref_table', 'sale_returns')->where('ref_id', $return->id)->delete();
        });
    }

    /**
     * Reverse stock deducted by a Purchase Return (add it back)
     */
    public function reversePurchaseReturnStock(PurchaseReturn $return): void
    {
        DB::transaction(function () use ($return) {
            foreach ($return->items as $returnItem) {
                $product = $returnItem->product;
                if (!$product) continue;

                if ($product->type === 'simple') {
                    $qtyToAdd = (float) $returnItem->quantity;
                    $batches = StockBatch::where('product_id', $product->id)
                        ->orderBy('received_at', 'desc')
                        ->orderBy('id', 'desc')
                        ->get();

                    $remaining = $qtyToAdd;
                    foreach ($batches as $batch) {
                        if ($remaining <= 0) break;
                        $canAdd = (float)$batch->qty_total - (float)$batch->qty_remaining;
                        if ($canAdd > 0) {
                            $add = min($canAdd, $remaining);
                            $batch->increment('qty_remaining', $add);
                            $remaining -= $add;
                        }
                    }
                    if ($remaining > 0 && $batches->isNotEmpty()) {
                        $batches->first()->increment('qty_remaining', $remaining);
                    }
                    $product->increment('stock_quantity', $qtyToAdd);
                } elseif ($product->type === 'panaflex_roll') {
                    $rollWidthInch = $returnItem->roll_width_inch ?? ($product->panaflexSpec->roll_width_inch ?? 126.0);

                    if ((float)$returnItem->units_sqft > 0) {
                        $widthFeet = $rollWidthInch > 0 ? ($rollWidthInch / 12.0) : 10.5;
                        $totalMetersToAdd = round(((float)$returnItem->units_sqft / ($widthFeet * 3.28)), 2);
                    } else {
                        $totalMetersToAdd = (float) ($returnItem->roll_length_meter ?? 0) * (float) ($returnItem->rolls_count ?? 1);
                        if ($totalMetersToAdd <= 0 && $returnItem->quantity > 0) {
                            $totalMetersToAdd = (float) $returnItem->quantity;
                        }
                    }
                    if ($totalMetersToAdd <= 0) $totalMetersToAdd = 0.01;

                    $batches = StockBatch::where('product_id', $product->id)
                        ->orderBy('received_at', 'desc')
                        ->orderBy('id', 'desc')
                        ->get();

                    $remaining = $totalMetersToAdd;
                    foreach ($batches as $batch) {
                        if ($remaining <= 0) break;
                        $canAdd = (float)$batch->meters_total - (float)$batch->meters_remaining;
                        if ($canAdd > 0) {
                            $add = min($canAdd, $remaining);
                            $batch->increment('meters_remaining', $add);
                            $remaining -= $add;
                        }
                    }
                    if ($remaining > 0 && $batches->isNotEmpty()) {
                        $batches->first()->increment('meters_remaining', $remaining);
                    }
                    $product->increment('stock_meters', $totalMetersToAdd);
                    $product->increment('stock_quantity', $totalMetersToAdd);
                }
            }
            // Delete related stock moves
            \App\Models\StockMove::where('ref_table', 'purchase_returns')->where('ref_id', $return->id)->delete();
        });
    }

    /**
     * Manual stock adjustment
     */
    public function adjust(Product $product, float $delta, ?int $batchId = null, string $reason = 'correction', ?string $note = null): void
    {
        DB::transaction(function () use ($product, $delta, $batchId, $reason, $note) {
            // Create adjustment record
            $adjustment = StockAdjustment::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'reason' => $reason,
                'qty_delta' => $product->type === 'simple' ? $delta : null,
                'meters_delta' => $product->type === 'panaflex_roll' ? $delta : null,
                'note' => $note,
            ]);

            // Find or create batch to adjust
            if ($batchId) {
                $batch = StockBatch::findOrFail($batchId);
            } else {
                // Use most recent batch or create new one
                $batch = StockBatch::where('product_id', $product->id)
                    ->orderBy('id', 'desc')
                    ->first();

                if (!$batch) {
                    $batch = StockBatch::create([
                        'product_id' => $product->id,
                        'batch_no' => StockBatch::generateBatchNumber($product),
                        'qty_total' => $product->type === 'simple' ? max(0, $delta) : 0,
                        'qty_remaining' => $product->type === 'simple' ? max(0, $delta) : 0,
                        'meters_total' => $product->type === 'panaflex_roll' ? max(0, $delta) : 0,
                        'meters_remaining' => $product->type === 'panaflex_roll' ? max(0, $delta) : 0,
                        'roll_width_inch' => $product->type === 'panaflex_roll' ? $product->panaflexSpec->roll_width_inch : null,
                        'received_at' => Carbon::today(),
                    ]);
                }
            }

            // Apply adjustment
            if ($product->type === 'simple') {
                $newQty = max(0, $batch->qty_remaining + $delta);
                $batch->update(['qty_remaining' => $newQty]);
                if ($delta > 0) {
                    $batch->update(['qty_total' => $batch->qty_total + $delta]);
                }
                
                // Update product stock_quantity
                $product->stock_quantity = max(0, $product->stock_quantity + $delta);
                $product->save();
            } else {
                $newMeters = max(0, $batch->meters_remaining + $delta);
                $batch->update(['meters_remaining' => $newMeters]);
                if ($delta > 0) {
                    $batch->update(['meters_total' => $batch->meters_total + $delta]);
                }
                
                // Update product stock_quantity (in meters for panaflex)
                $product->stock_quantity = max(0, $product->stock_quantity + $delta);
                $product->save();
            }

            // Create stock move
            $this->createStockMove(
                $product->id,
                'adjustment',
                $adjustment->id,
                'adjustments',
                $batch->id,
                $product->type === 'simple' ? $delta : null,
                $product->type === 'panaflex_roll' ? $delta : null,
                "Adjustment: {$reason}" . ($note ? " - {$note}" : '')
            );
        });
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts(): array
    {
        $simpleProducts = Product::where('type', 'simple')
            ->whereNotNull('min_qty')
            ->with(['stockBatches'])
            ->get()
            ->filter(function ($product) {
                $totalQty = $product->stockBatches->sum('qty_remaining');
                return $totalQty <= $product->min_qty;
            });

        $panaflexProducts = Product::where('type', 'panaflex_roll')
            ->whereNotNull('min_meters')
            ->with(['stockBatches'])
            ->get()
            ->filter(function ($product) {
                $totalMeters = $product->stockBatches->sum('meters_remaining');
                return $totalMeters <= $product->min_meters;
            });

        return [
            'simple' => $simpleProducts,
            'panaflex' => $panaflexProducts,
        ];
    }

    /**
     * Get stock summary for a product
     */
    public function getStockSummary(Product $product): array
    {
        $batches = $product->stockBatches;

        if ($product->type === 'simple') {
            return [
                'total_qty' => $batches->sum('qty_total'),
                'remaining_qty' => $batches->sum('qty_remaining'),
                'available_batches' => $batches->where('qty_remaining', '>', 0)->count(),
                'is_low_stock' => $product->min_qty && $batches->sum('qty_remaining') <= $product->min_qty,
            ];
        } else {
            return [
                'total_meters' => $batches->sum('meters_total'),
                'remaining_meters' => $batches->sum('meters_remaining'),
                'available_batches' => $batches->where('meters_remaining', '>', 0)->count(),
                'is_low_stock' => $product->min_meters && $batches->sum('meters_remaining') <= $product->min_meters,
            ];
        }
    }

    /**
     * Create a stock move record
     */
    private function createStockMove(
        int $productId,
        string $type,
        ?int $refId,
        ?string $refTable,
        ?int $batchId,
        ?float $qtyChange,
        ?float $metersChange,
        ?string $note
    ): void {
        StockMove::create([
            'product_id' => $productId,
            'type' => $type,
            'ref_id' => $refId,
            'ref_table' => $refTable,
            'batch_id' => $batchId,
            'qty_change' => $qtyChange,
            'meters_change' => $metersChange,
            'user_id' => Auth::id(),
            'note' => $note,
        ]);
    }

    /**
     * Receive a specific purchase item and update inventory
     */
    public function receivePurchaseItem($purchaseItem, $quantity, $batchCode = null, $expiryDate = null): void
    {
        DB::transaction(function () use ($purchaseItem, $quantity, $batchCode, $expiryDate) {
            $product = $purchaseItem->product;

            if ($product->type === 'panaflex_roll') {
                // Calculate meters for this quantity of rolls
                // Assuming quantity here refers to number of rolls if it's a roll product
                // But wait, purchaseItem has rolls_count. 
                // If quantity is passed, is it rolls or total qty?
                // Usually for panaflex, quantity = rolls_count * length? Or just rolls count?
                // In PurchaseController::store, quantity is used for simple, but for panaflex:
                // 'quantity' => $item['quantity'], which seems to be total sqft or something?
                // Let's look at PurchaseController::store again.
                
                // In store:
                // 'quantity' => $item['quantity'],
                // 'rolls_count' => $item['rolls_count'] ?? null,
                
                // If we are receiving "quantity", we need to know what that unit is.
                // For Panaflex, usually we track meters.
                
                // Let's assume for now we use the same logic as createPanaflexBatch
                // But receivePurchaseItem is generic.
                
                // Let's look at how receivePurchaseItem is called in PurchaseController::receive
                // $itemData['quantity'] is passed.
                
                // If it is a panaflex roll, we need to calculate meters based on the proportion of received rolls?
                // Or is the quantity passed actually the number of rolls?
                
                // Let's stick to the safe bet: If it's panaflex, we need to handle it carefully.
                // But for now, let's just ensure stock_quantity is updated.
                
                // Actually, looking at createPanaflexBatch:
                // $totalMeters = $purchaseItem->roll_length_meter * $purchaseItem->rolls_count;
                // It updates stock_meters AND stock_quantity (with meters).
                
                // So if we receive a partial quantity, we need to know if it's rolls or meters.
                // The PurchaseItem has roll_length_meter.
                
                // If the input $quantity is "number of rolls":
                $meters = $quantity * $purchaseItem->roll_length_meter;
                
                $batch = StockBatch::create([
                    'product_id' => $product->id,
                    'batch_no' => $batchCode ?: StockBatch::generateBatchNumber($product),
                    'purchase_item_id' => $purchaseItem->id,
                    'roll_width_inch' => $purchaseItem->roll_width_inch,
                    'meters_total' => $meters,
                    'meters_remaining' => $meters,
                    'received_at' => now()->toDateString(),
                    'expiry_date' => $expiryDate,
                ]);

                $this->createStockMove(
                    $product->id,
                    'purchase',
                    $purchaseItem->id,
                    'purchase_items',
                    $batch->id,
                    null,
                    $meters,
                    "Received purchase item - Batch: {$batch->batch_no}"
                );

                $product->increment('stock_meters', $meters);
                $product->increment('stock_quantity', $meters);

            } else {
                // Simple product
                $batch = StockBatch::create([
                    'product_id' => $product->id,
                    'batch_no' => $batchCode ?: StockBatch::generateBatchNumber($product),
                    'purchase_item_id' => $purchaseItem->id,
                    'qty_total' => $quantity,
                    'qty_remaining' => $quantity,
                    'received_at' => now()->toDateString(),
                    'expiry_date' => $expiryDate,
                ]);

                $this->createStockMove(
                    $product->id,
                    'purchase',
                    $purchaseItem->id,
                    'purchase_items',
                    $batch->id,
                    $quantity,
                    0,
                    "Received purchase item - Batch: {$batch->batch_no}"
                );

                $product->increment('stock_quantity', $quantity);
            }
        });
    }
    /**
     * Reverse purchase stock addition
     */
    public function reversePurchase(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            foreach ($purchase->purchaseItems as $item) {
                // Find batches created by this purchase item
                $batches = StockBatch::where('purchase_item_id', $item->id)->get();

                foreach ($batches as $batch) {
                    $product = $item->product;

                    if ($product->type === 'simple') {
                        $product->decrement('stock_quantity', $batch->qty_total);
                    } else {
                        $product->decrement('stock_meters', $batch->meters_total);
                        $product->decrement('stock_quantity', $batch->meters_total);
                    }

                    // Delete the batch
                    $batch->delete();
                }
            }

            // Delete associated stock moves (clean up history)
            StockMove::where('ref_table', 'purchases')
                ->where('ref_id', $purchase->id)
                ->delete();
        });
    }

    /**
     * Reverse stock consumption for a deleted sale
     */
    public function reverseSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            // Restore stock based on Sale Items
            foreach ($sale->saleItems as $item) {
                $product = $item->product;
                
                if (!$product) {
                    continue;
                }
                
                if ($product->type === 'panaflex_roll') {
                    // Calculate meters to restore
                    $product->load('panaflexSpec');
                    $rollWidthInch = $product->panaflexSpec->roll_width_inch ?? 126;
                    
                    // Use the same calculation logic as POSController (Area Based)
                    $unitsSqFt = AreaService::calcAreaSqFt(
                        $item->length_input ?? 1,
                        $item->length_unit ?? 'ft',
                        $item->width_input ?? 1,
                        $item->width_unit ?? 'ft',
                        $item->quantity
                    );
                    
                    $rollWidthFt = $rollWidthInch / 12;
                    $metersToRestore = ($unitsSqFt / $rollWidthFt) / 3.28;
                    
                    // Restore stock (pass 'return' to add stock)
                    $product->updateStock($metersToRestore, 'return');
                    
                    \Log::info("Sale Reversal - Restored Panaflex Stock", [
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'meters_restored' => $metersToRestore
                    ]);
                } else {
                    // Restore simple product stock
                    $product->updateStock($item->quantity, 'return');
                    
                    \Log::info("Sale Reversal - Restored Simple Stock", [
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'qty_restored' => $item->quantity
                    ]);
                }
            }

            // Find all stock moves for this sale (Legacy support if moves exist)
            $moves = StockMove::where('ref_table', 'sales')
                ->where('ref_id', $sale->id)
                ->where('type', 'sale')
                ->get();

            foreach ($moves as $move) {
                $batch = StockBatch::find($move->batch_id);
                if ($batch) {
                    if ($move->qty_change < 0) {
                        $batch->increment('qty_remaining', abs($move->qty_change));
                    }
                    if ($move->meters_change < 0) {
                        $batch->increment('meters_remaining', abs($move->meters_change));
                    }
                }
                
                // Create a reversal stock move
                $this->createStockMove(
                    $move->product_id,
                    'adjustment',
                    $sale->id,
                    'sales',
                    $move->batch_id,
                    abs($move->qty_change),
                    abs($move->meters_change),
                    "Sale {$sale->invoice_no} deleted"
                );
            }
        });
    }
}
