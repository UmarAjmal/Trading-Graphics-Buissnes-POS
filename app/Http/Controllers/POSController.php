<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\PendingPayment;
use App\Models\CompanySetting;
use App\Models\RegisterSession;
use App\Services\AreaService;
use App\Services\RollConsumptionService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class POSController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }
    /**
     * Show the POS interface
     */
    public function index(Request $request): Response
    {
        $activeSession = RegisterSession::getActiveSession(Auth::id());
        
        return Inertia::render('Pos/Index', [
            'activeRegisterSession' => $activeSession,
            'hasOpenRegister' => $activeSession !== null,
        ]);
    }

    /**
     * Show the new Sales interface
     */
    public function create(): Response
    {
        $activeSession = RegisterSession::getActiveSession(Auth::id());
        
        $editSale = null;
        if (request()->has('edit_sale_id')) {
            $saleId = request('edit_sale_id');
            $editSale = Sale::with(['saleItems.product', 'customer', 'payments'])->find($saleId);
        }

        return Inertia::render('Sales/Create', [
            'activeRegisterSession' => $activeSession,
            'hasOpenRegister' => $activeSession !== null,
            'editSale' => $editSale,
        ]);
    }

    /**
     * Search products for POS
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all'); // all, simple, panaflex_roll

        $products = Product::with(['category', 'unit', 'panaflexSpec'])
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('sku', 'like', "%{$query}%")
                      ->orWhere('barcode', 'like', "%{$query}%");
                });
            })
            ->when($type !== 'all', function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->where('active', true)
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'type' => $product->type,
                    'price' => $product->sale_rate,
                    'sale_rate' => $product->sale_rate, // Add this for frontend compatibility
                    'unit_symbol' => $product->unit->symbol ?? 'pcs',
                    'category_name' => $product->category->name ?? '',
                    'stock_quantity' => $product->stock_quantity,
                    'stock_meters' => $product->stock_meters,
                    'current_stock' => $product->current_stock,
                    
                    // Panaflex specific data
                    'roll_width_inch' => $product->panaflexSpec->roll_width_inch ?? null,
                    'roll_length_meter' => $product->panaflexSpec->roll_length_meter ?? null,
                    'rate_per_sqft' => $product->type === 'panaflex_roll' ? $product->sale_rate : null,
                ];
            });

        return response()->json($products);
    }

    /**
     * Calculate Panaflex area and consumption
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:simple,panaflex_roll',
            'length' => 'required_if:type,panaflex_roll|numeric|min:0',
            'length_unit' => 'required_if:type,panaflex_roll|in:m,ft',
            'width' => 'required_if:type,panaflex_roll|numeric|min:0',
            'width_unit' => 'required_if:type,panaflex_roll|in:in,ft',
            'qty' => 'required|integer|min:1',
            'rate' => 'required|numeric|min:0',
        ]);

        $product = Product::with('panaflexSpec')->findOrFail($request->product_id);

        if ($request->type === 'simple') {
            return response()->json([
                'units_sqft' => (float) $request->qty,
                'meters_hint' => null,
                'valid_width' => true,
                'utilization_percent' => null,
            ]);
        }

        // Panaflex calculations
        $length = (float) $request->length;
        $lengthUnit = $request->length_unit;
        $width = (float) $request->width;
        $widthUnit = $request->width_unit;
        $qty = (int) $request->qty;

        // Calculate area using AreaService
        $unitsSqFt = AreaService::calcAreaSqFt($length, $lengthUnit, $width, $widthUnit, $qty);

        // Get roll specifications
        $rollWidthInch = $product->panaflexSpec->roll_width_inch ?? 126;

        // Calculate consumption (now supports tiling/multiple strips)
        $metersUsed = RollConsumptionService::calcMetersUsed(
            $length, $lengthUnit, $width, $widthUnit, $rollWidthInch, $qty
        );
        $utilization = RollConsumptionService::calcRollUtilization($width, $widthUnit, $rollWidthInch);

        $rollWidthFt = round($rollWidthInch / 12, 2);

        return response()->json([
            'units_sqft' => $unitsSqFt,
            'meters_hint' => $metersUsed,
            'valid_width' => true, // Always valid now
            'utilization_percent' => $utilization,
            'roll_width_ft' => $rollWidthFt,
            'roll_width_inch' => $rollWidthInch,
        ]);
    }

    /**
     * Update an existing sale
     */
    public function update(Request $request, $id): JsonResponse
    {
        $sale = Sale::with(['saleItems', 'customer'])->findOrFail($id);
        
        DB::beginTransaction();
        
        try {
            // 1. Revert Inventory
            foreach ($sale->saleItems as $item) {
                $product = Product::with('panaflexSpec')->find($item->product_id);
                if ($product) {
                    if ($product->type === 'panaflex_roll') {
                        // Revert meters
                        $rollWidthInch = $product->panaflexSpec->roll_width_inch ?? 126;
                        $rollWidthFt = $rollWidthInch / 12;
                        // Avoid division by zero
                        if ($rollWidthFt > 0) {
                            $sqFt = $item->units_sqft;
                            $metersConsumed = ($sqFt / $rollWidthFt) / 3.28;
                            $product->stock_meters += $metersConsumed;
                            $product->save();
                        }
                    } else {
                        // Revert quantity
                        $product->stock_quantity += $item->quantity;
                        $product->save();
                    }
                }
            }
            
            // 2. Revert Customer Financials
            $customer = $sale->customer;
            if ($customer) {
                // Revert Advance Used
                if ($sale->advance_used > 0) {
                    $customer->advances()->create([
                        'date' => now(),
                        'amount' => $sale->advance_used,
                        'note' => "Reversal for updated sale #{$sale->invoice_no}",
                        'user_id' => auth()->id(),
                    ]);
                }
                
                // Delete associated Payments (Credit Side)
                \App\Models\Payment::where('sale_id', $sale->id)->delete();
                
                // Delete associated Pending Payments (Debit Side / Due)
                PendingPayment::where('sale_id', $sale->id)->delete();
            }
            
            // 3. Delete Old Sale Items
            $sale->saleItems()->delete();
            
            // 3. Process New Data (Similar to Checkout)
            
            // Validate Stock First
            foreach ($request->items as $item) {
                if (($item['type'] === 'custom') || empty($item['product_id'])) {
                    continue;
                }
                $product = Product::with('panaflexSpec')->findOrFail($item['product_id']);
                
                if ($item['type'] === 'panaflex_roll') {

                    // Calculate required meters
                    $rollWidthInch = $product->panaflexSpec->roll_width_inch ?? 126;
                    $rollWidthFt = $rollWidthInch / 12;
                    
                    $unitsSqFt = AreaService::calcAreaSqFt(
                        $item['length'] ?? 1, 
                        $item['length_unit'] ?? 'ft', 
                        $item['width'] ?? 1, 
                        $item['width_unit'] ?? 'ft', 
                        $item['qty']
                    );
                    
                    $metersConsumed = ($unitsSqFt / $rollWidthFt) / 3.28;
                    
                    if ($product->stock_meters < $metersConsumed) {
                        throw new \Exception("Insufficient stock for {$product->name}. Available: " . round($product->stock_meters, 2) . "m");
                    }
                } else {
                    if ($product->stock_quantity < $item['qty']) {
                        throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->stock_quantity}");
                    }
                }
            }
            
            // Calculate Totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                // Handle Custom Item Dimensions for Subtotal Calculation
                if ($item['type'] === 'custom' && !empty($item['width']) && !empty($item['height']) && $item['width'] > 0 && $item['height'] > 0) {
                     $len = $item['height']; 
                     $wid = $item['width'];
                     $unitsSqFt = $len * $wid * $item['qty'];
                } elseif ($item['type'] === 'panaflex_roll' && isset($item['length']) && isset($item['width'])) {
                    $unitsSqFt = AreaService::calcAreaSqFt(
                        $item['length'] ?? 1, 
                        $item['length_unit'] ?? 'ft', 
                        $item['width'] ?? 1, 
                        $item['width_unit'] ?? 'ft', 
                        $item['qty']
                    );
                } else {
                    $unitsSqFt = (float) $item['qty'];
                }
                
                $lineTotal = ($unitsSqFt * $item['rate']) - ($item['discount'] ?? 0) + ($item['tax'] ?? 0);
                $subtotal += $lineTotal;
            }
            
            $discountTotal = $request->discount_total ?? 0;
            $taxTotal = $request->tax_total ?? 0;
            $utilitiesCharges = $request->utilities_charges ?? 0;
            $otherCharges = $request->other_charges ?? 0;
            
            $billTotal = $subtotal - $discountTotal + $taxTotal + $utilitiesCharges + $otherCharges;
            
            // Payment Logic
            $previousBalance = 0;
            $grandTotal = $billTotal;
            // FIX: Default to 0 if not provided, do not assume full payment to prevent accidental cash entries
            $paidAmount = $request->paid_amount ?? 0;
            $currentBalance = 0;
            $advanceUsed = $request->advance_used ?? 0;
            
            if ($customer) {
                // Refresh customer to get latest balance (after reversals)
                $customer->unsetRelation('pendingPayments');
                $customer = $customer->fresh();
                
                // Handle Advance
                if ($advanceUsed > 0) {
                    // Check if enough advance available
                    if ($advanceUsed > $customer->advance_balance) {
                         throw new \Exception("Insufficient advance balance.");
                    }
                    
                    $customer->advances()->create([
                        'date' => now(),
                        'amount' => -$advanceUsed,
                        'note' => "Used in updated sale #{$sale->invoice_no}",
                        'user_id' => auth()->id(),
                    ]);
                }
                
                $previousBalance = $customer->balance;
                
                if ($request->payment_type === 'credit') {
                    $grandTotal = $billTotal + $previousBalance - $advanceUsed;
                    $paidAmount = $advanceUsed;
                    // FIX: Balance is positive for Debit.
                    $currentBalance = $previousBalance + $billTotal - $advanceUsed;
                } else {
                    // Cash or Bank
                    $totalCovered = $paidAmount + $advanceUsed;
                    $remainingDue = $billTotal - $totalCovered;
                    
                    if ($remainingDue > 0) {
                        // FIX: Balance increases by the remaining debt
                        $currentBalance = $previousBalance + $remainingDue;
                    } else {
                        // Full Payment
                        $currentBalance = $previousBalance;
                    }
                }
                
                $customer->save();
            }
            
            // Update Sale Record
            $sale->update([
                'customer_id' => $customer ? $customer->id : null,
                'invoice_date' => $request->invoice_date,
                'payment_type' => $request->payment_type,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'utilities_charges' => $utilitiesCharges,
                'other_charges' => $otherCharges,
                'bill_total' => $billTotal,
                'previous_balance' => $previousBalance,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount + $advanceUsed,
                'current_balance' => $currentBalance,
                'advance_used' => $advanceUsed,
                'notes' => $request->notes,
                'system_description' => $request->system_description,
            ]);
            
            // Create Payment Record (Cash/Bank)
            if (in_array($request->payment_type, ['cash', 'bank']) && $paidAmount > 0 && $customer) {
                 \App\Models\Payment::create([
                    'customer_id' => $customer->id,
                    'sale_id' => $sale->id,
                    'amount' => $paidAmount,
                    'type' => 'received',
                    'payment_date' => now(),
                    'payment_method' => $request->payment_type,
                    'note' => "Payment for Updated Invoice #{$sale->invoice_no}",
                    'user_id' => Auth::id(),
                    'current_balance' => $currentBalance, // Save the balance snapshot
                ]);
            }
            
            // Create Pending Payment (Partial)
            if (in_array($request->payment_type, ['cash', 'bank']) && isset($remainingDue) && $remainingDue > 0 && $customer) {
                 PendingPayment::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $customer->id,
                    'due_date' => now()->addDays(30),
                    'amount_due' => $remainingDue,
                    'amount' => $remainingDue,
                    'settled' => false,
                ]);
            }

            // Create Pending Payment for Credit Sales (MISSING LOGIC FIX)
            if ($request->payment_type === 'credit' && $customer) {
                // Calculate due amount (Bill Total - Advance Used)
                // Note: Previous balance is handled separately via OPB/existing records
                $amountDue = $billTotal - $advanceUsed;
                
                if ($amountDue > 0) {
                     PendingPayment::create([
                        'sale_id' => $sale->id,
                        'customer_id' => $customer->id,
                        'due_date' => now()->addDays(30),
                        'amount_due' => $amountDue,
                        'amount' => $amountDue,
                        'settled' => false,
                    ]);
                }
            }
            
            // Create Sale Items & Consume Inventory
            foreach ($request->items as $item) {
                // Safely get description or default to empty string
                $itemDescription = $item['description'] ?? '';

                if (($item['type'] === 'custom') || empty($item['product_id'])) {
                    // For Custom Items: Store "Name|Description" to separate them later
                    $descriptionToStore = $item['name'];
                    if ($itemDescription) {
                        $descriptionToStore .= '|' . $itemDescription;
                    }

                    // Calculate Dimensions
                    $width = (float)($item['width'] ?? 0);
                    $height = (float)($item['height'] ?? $item['length'] ?? 0);
                    $qty = (float)$item['qty'];
                    $rate = (float)$item['rate'];
                    $discount = (float)($item['discount'] ?? 0);
                    
                    $unitsSqFt = 0;
                    $calcTotal = 0;

                    // If both width and height > 0, calculate as Area
                    if ($width > 0 && $height > 0) {
                        $unitsSqFt = $width * $height * $qty;
                        $calcTotal = ($unitsSqFt * $rate) - $discount;
                        $widthUnit = 'ft';
                        $lengthUnit = 'ft';
                    } else {
                        // Otherwise standard Qty
                        $unitsSqFt = $qty; 
                        $calcTotal = ($qty * $rate) - $discount;
                        $widthUnit = null;
                        $lengthUnit = null;
                    }

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => null,
                        'description' => $descriptionToStore,
                        'quantity' => $qty,
                        'rate' => $rate,
                        'discount' => $discount,
                        'tax' => $item['tax'] ?? 0,
                        'units_sqft' => ($width > 0 && $height > 0) ? $unitsSqFt : 0, 
                        'line_total' => $calcTotal,
                        'length_input' => $height > 0 ? $height : null,
                        'length_unit' => $lengthUnit,
                        'width_input' => $width > 0 ? $width : null,
                        'width_unit' => $widthUnit,
                    ]);
                    continue;
                }

                $product = Product::with('panaflexSpec')->findOrFail($item['product_id']);
                
                $userDescription = $item['description'] ?? null;
                
                if ($item['type'] === 'panaflex_roll' && isset($item['length']) && isset($item['width'])) {
                    $unitsSqFt = AreaService::calcAreaSqFt(
                        $item['length'] ?? 1, 
                        $item['length_unit'] ?? 'ft', 
                        $item['width'] ?? 1, 
                        $item['width_unit'] ?? 'ft', 
                        $item['qty']
                    );
                    
                    // DO NOT bake dimensions into description anymore
                    // We will reconstruct them in the view using length_input/width_input columns
                    $description = $userDescription;
                } else {
                    $unitsSqFt = (float) $item['qty'];
                    $description = $userDescription;
                }

                $lineTotal = ($unitsSqFt * $item['rate']) - ($item['discount'] ?? 0) + ($item['tax'] ?? 0);

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'description' => $description,
                    'quantity' => $item['qty'],
                    'rate' => $item['rate'],
                    'discount' => $item['discount'] ?? 0,
                    'tax' => $item['tax'] ?? 0,
                    'units_sqft' => $unitsSqFt,
                    'line_total' => $lineTotal,
                    'length_input' => $item['length'] ?? null,
                    'length_unit' => $item['length_unit'] ?? null,
                    'width_input' => $item['width'] ?? null,
                    'width_unit' => $item['width_unit'] ?? null,
                ]);

                // Consume inventory
                if ($item['type'] === 'panaflex_roll') {
                    $rollWidthInch = $product->panaflexSpec->roll_width_inch ?? 126;
                    $rollWidthFt = $rollWidthInch / 12;
                    $metersConsumed = ($unitsSqFt / $rollWidthFt) / 3.28;
                    
                    $product->stock_meters -= $metersConsumed;
                    $product->save();
                } else {
                    $product->stock_quantity -= $item['qty'];
                    $product->save();
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Sale updated successfully',
                'invoice_no' => $sale->invoice_no,
                'sale_id' => $sale->id,
                'printable_urls' => [
                    'a4' => route('pos.print.a4', $sale->id),
                    '80mm' => route('pos.print.80mm', $sale->id),
                ],
                'preview_urls' => [
                    'a4' => route('pos.preview.a4', $sale->id),
                    '80mm' => route('pos.preview.80mm', $sale->id),
                ],
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating sale: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer details including advance balance
     */
    public function getCustomerDetails(Request $request): JsonResponse
    {
        $customerId = $request->get('customer_id');
        
        if (!$customerId) {
            return response()->json([
                'customer' => null,
                'advance_balance' => 0,
                'advance_balance_formatted' => 'PKR 0.00'
            ]);
        }
        
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            return response()->json([
                'customer' => null,
                'advance_balance' => 0,
                'advance_balance_formatted' => 'PKR 0.00'
            ]);
        }
        
        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'display_name' => $customer->display_name,
            ],
            'advance_balance' => $customer->advance_balance,
            'advance_balance_formatted' => 'PKR ' . number_format($customer->advance_balance, 2),
        ]);
    }

    /**
     * Search customers for POS
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        $customers = Customer::search($query)
            ->limit(5000)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'display_name' => $customer->display_name,
                    'total_pending' => number_format($customer->total_pending, 2),
                    'advance_balance' => number_format($customer->advance_balance, 2),
                    'advance_balance_raw' => $customer->advance_balance,
                ];
            });

        return response()->json($customers);
    }

    /**
     * Process checkout and create sale
     */
    public function checkout(Request $request): JsonResponse
    {
        // **CRITICAL: Check if user has an active register session**
        $activeSession = RegisterSession::getActiveSession(Auth::id());
        
        if (!$activeSession) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot process sale. Please open cash register first.',
                'error_code' => 'REGISTER_NOT_OPEN'
            ], 422);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.type' => 'required|in:simple,panaflex_roll,custom',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
            'items.*.length' => 'nullable|numeric|min:0',
            'items.*.length_unit' => 'nullable|in:m,ft',
            'items.*.width' => 'nullable|numeric|min:0',
            'items.*.width_unit' => 'nullable|in:in,ft',
            'items.*.description' => 'nullable|string|max:500',
            'discount_total' => 'nullable|numeric|min:0',
            'tax_total' => 'nullable|numeric|min:0',
            'utilities_charges' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'payment_type' => 'required|in:cash,credit,bank',
            'customer_id' => 'nullable|exists:customers,id',
            'advance_used' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'invoice_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'system_description' => 'nullable|string',
            'custom_customer_name' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($request, $activeSession) {
            // Check stock availability first
            foreach ($request->items as $item) {
                if (($item['type'] === 'custom') || empty($item['product_id'])) {
                    continue;
                }

                $product = Product::findOrFail($item['product_id']);
                
                // Calculate quantity based on type
                if ($item['type'] === 'panaflex_roll' && isset($item['length']) && isset($item['width'])) {
                    // Calculate required area in sq.ft for pricing
                    $requiredAreaSqFt = AreaService::calcAreaSqFt(
                        $item['length'] ?? 1, 
                        $item['length_unit'] ?? 'ft', 
                        $item['width'] ?? 1, 
                        $item['width_unit'] ?? 'ft', 
                        $item['qty']
                    );
                    
                    // Calculate required meters of roll material using RollConsumptionService
                    $product->load('panaflexSpec');
                    $rollWidthInch = $product->panaflexSpec->roll_width_inch ?? 126;
                    
                    // Calculate required meters
                    // NEW LOGIC: Deduct based on Area Sold (SqFt) converted to linear meters
                    // This ensures stock deduction matches the exact SqFt sold, ignoring waste/strips
                    $rollWidthFt = $rollWidthInch / 12;
                    $requiredMeters = ($requiredAreaSqFt / $rollWidthFt) / 3.28;
                    
                    // For Panaflex, check meters available (stock_meters is in linear meters)
                    if ($product->stock_meters < $requiredMeters) {
                        return response()->json([
                            'success' => false,
                            'message' => "Insufficient stock for {$product->name}. Available: " . round($product->stock_meters, 2) . " meters, Required: " . round($requiredMeters, 2) . " meters"
                        ], 422);
                    }
                } else {
                    $requiredQty = (float) $item['qty'];
                    
                    // For simple products, check quantity available
                    if ($product->stock_quantity < $requiredQty) {
                        return response()->json([
                            'success' => false,
                            'message' => "Insufficient stock for {$product->name}. Available: {$product->stock_quantity}, Required: {$requiredQty}"
                        ], 422);
                    }
                }
            }

            // Generate invoice number
            $invoiceNo = Sale::generateInvoiceNumber();
            
            // Calculate totals first
            $subtotal = 0;
            foreach ($request->items as $item) {
                // Handle Custom Item Dimensions for Subtotal Calculation
                if ($item['type'] === 'custom' && !empty($item['width']) && !empty($item['height']) && $item['width'] > 0 && $item['height'] > 0) {
                     $len = $item['height']; // Frontend sends 'height', backend maps to length
                     $wid = $item['width'];
                     $unitsSqFt = $len * $wid * $item['qty'];
                } elseif ($item['type'] === 'panaflex_roll' && isset($item['length']) && isset($item['width'])) {
                    $unitsSqFt = AreaService::calcAreaSqFt(
                        $item['length'] ?? 1, 
                        $item['length_unit'] ?? 'ft', 
                        $item['width'] ?? 1, 
                        $item['width_unit'] ?? 'ft', 
                        $item['qty']
                    );
                } else {
                    $unitsSqFt = (float) $item['qty'];
                }
                
                $lineTotal = ($unitsSqFt * $item['rate']) - ($item['discount'] ?? 0) + ($item['tax'] ?? 0);
                $subtotal += $lineTotal;
            }
            
            // Calculate bill totals
            $discountTotal = $request->discount_total ?? 0;
            $taxTotal = $request->tax_total ?? 0;
            $utilitiesCharges = $request->utilities_charges ?? 0;
            $otherCharges = $request->other_charges ?? 0;
            
            $billTotal = $subtotal - $discountTotal + $taxTotal + $utilitiesCharges + $otherCharges;
            
            // Step-by-Step Payment Logic with Automatic Advance Usage
            $previousBalance = 0;
            $grandTotal = $billTotal;
            // FIX: Default to 0 if not provided, do not assume full payment to prevent accidental cash entries
            $paidAmount = $request->paid_amount ?? 0;
            $currentBalance = 0;
            $advanceUsed = $request->advance_used ?? 0;
            
            // Resolve Customer ID (Handle Walk-in)
            $customerId = $request->customer_id;
            if (!$customerId) {
                $walkIn = Customer::where('name', 'Walk-in Customer')->first();
                if ($walkIn) {
                    $customerId = $walkIn->id;
                }
            }

            if ($customerId) {
                // **CRITICAL FIX: Always fetch fresh customer data from database**
                // This ensures we get the latest balance after any cash vouchers/payments
                $customer = Customer::findOrFail($customerId);
                
                // **IMPORTANT: Clear cached relationships using Laravel's method**
                // The credit_used accessor queries pendingPayments, so we must clear its cache
                $customer->unsetRelation('pendingPayments');
                $customer = $customer->fresh();
                
                // Track explicit advance usage to determine if we need to create PendingPayment
                $explicitAdvanceUsed = 0;

                // For cash/bank sales: Automatically use available advance balance
                if (in_array($request->payment_type, ['cash', 'bank'])) {
                    $availableAdvance = $customer->advance_balance;
                    
                    // Also consider negative ledger balance as available advance
                    // This handles legacy data or pure ledger-based advances
                    $ledgerAdvance = ($customer->balance < 0) ? abs($customer->balance) : 0;
                    $totalAvailableAdvance = max($availableAdvance, $ledgerAdvance);
                    
                    if ($totalAvailableAdvance > 0) {
                        // Calculate how much advance to use (up to bill total)
                        // FIX: Subtract cash paid from bill total before calculating advance usage
                        // This ensures we don't use more advance than needed if user pays some cash
                        $cashPaid = $paidAmount; // Use the variable defined at top of transaction
                        
                        // Explicitly force advance usage to 0 if cash covers the bill
                        if ($cashPaid >= $billTotal) {
                            $advanceToUse = 0;
                        } else {
                            $amountNeeded = max(0, $billTotal - $cashPaid);
                            $advanceToUse = min($totalAvailableAdvance, $amountNeeded);
                        }
                        
                        $advanceUsed = $advanceToUse;
                        
                        // Split usage: Prefer Explicit Advance, then Ledger Advance
                        if ($availableAdvance > 0) {
                            $explicitAdvanceUsed = min($advanceToUse, $availableAdvance);
                        }
                        
                        // Only create an advance usage record if we are using the explicit advance balance
                        if ($explicitAdvanceUsed > 0) {
                            $customer->advances()->create([
                                'date' => now(),
                                'amount' => -$explicitAdvanceUsed,
                                'note' => "Auto-used in cash sale #{$invoiceNo}",
                                'user_id' => auth()->id(),
                            ]);
                        }
                        
                        // Adjust payment amounts
                        // If user paid less than bill total (partial payment)
                        // The remaining amount should be treated as credit
                        $remainingCash = $billTotal - $advanceToUse; // Remaining cash needed
                        
                        // Log for debugging
                        \Log::info("Advance Auto-Usage", [
                            'invoice_no' => $invoiceNo,
                            'customer_id' => $customer->id,
                            'bill_total' => $billTotal,
                            'available_advance' => $availableAdvance,
                            'ledger_advance' => $ledgerAdvance,
                            'advance_used' => $advanceToUse,
                            'explicit_advance_used' => $explicitAdvanceUsed,
                            'remaining_cash' => $remainingCash
                        ]);
                    }
                } else {
                    // Manual advance usage for credit sales (existing logic) 
                    if ($advanceUsed > 0) {
                        // Validate advance amount
                        $availableAdvance = $customer->advance_balance;
                        if ($advanceUsed > $availableAdvance) {
                            return response()->json([
                                'success' => false,
                                'message' => "Insufficient advance balance. Available: PKR {$availableAdvance}, Requested: PKR {$advanceUsed}"
                            ], 422);
                        }
                        
                        // Create advance usage record (negative advance)
                        $customer->advances()->create([
                            'date' => now(),
                            'amount' => -$advanceUsed,
                            'note' => "Used in sale #{$invoiceNo}",
                            'user_id' => auth()->id(),
                        ]);
                        
                        // For credit sales, we assume all advance used is explicit because of the validation above
                        $explicitAdvanceUsed = $advanceUsed;
                    }
                }
                
                if ($request->payment_type === 'credit') {
                    // **CRITICAL FIX: Get fresh credit_used directly from database, bypassing stale column**
                    // Use customer balance which accounts for Payments and Advances
                    $previousBalance = $customer->balance;
                    
                    $grandTotal = $billTotal + $previousBalance - $advanceUsed;
                    
                    // Check credit limit
                    $creditLimit = $customer->credit_limit ?? 0;
                    
                    // Update credit_used: previous + new bill - advance
                    // But we'll recalculate from actual pending payments after creating new one
                    $paidAmount = $advanceUsed;
                    // FIX: Balance is positive for Debit.
                    // New Balance = Old Balance + Bill - Advance
                    $currentBalance = $previousBalance + $billTotal - $advanceUsed;
                    
                    // Note: credit_used will be updated after PendingPayment is created
                    // to ensure it reflects actual database state
                } else if (in_array($request->payment_type, ['cash', 'bank'])) {
                    // **CRITICAL FIX: Get fresh previous balance from database for cash/bank payment too**
                    // For cash/bank payments with customer - show and clear previous balance
                    
                    \Log::info("=== CASH/BANK PAYMENT DEBUG ===", [
                        'customer_id' => $customerId,
                        'invoice_no' => $invoiceNo,
                        'payment_type' => $request->payment_type,
                    ]);
                    
                    $previousBalance = $customer->balance;
                    
                    \Log::info("Previous Balance Calculated", [
                        'previous_balance' => $previousBalance,
                        'customer_id' => $customerId,
                    ]);
                    
                    // Handle Partial Payment Logic
                    // If user pays less than the bill total, the difference is added to credit
                    $totalReceived = $paidAmount; // This includes advance used if any? No, paidAmount is cash from user
                    // Wait, paidAmount from request is what user gave in CASH.
                    
                    // FIX: Total covered = Cash Given + Explicit Advance Used.
                    // Ledger Advance Used is NOT considered "Covered" here because we want it to generate a PendingPayment (Debt)
                    // to offset the Negative Balance (Credit).
                    $totalCovered = $paidAmount + $explicitAdvanceUsed;
                    $remainingDue = $billTotal - $totalCovered;
                    
                    if ($remainingDue > 0) {
                        // Partial Payment: Remaining amount goes to credit
                        // Create Pending Payment for the remaining amount
                        // We need to link this to the sale, but sale is created later.
                        // So we will create this PendingPayment AFTER the sale is created below.
                        // Just set a flag or variable to use later.
                        
                        // FIX: Balance increases by the remaining debt
                        $currentBalance = $previousBalance + $remainingDue; 
                    } else {
                        // Full Payment or Overpayment
                        // Balance reduces by the overpayment amount (becomes negative/credit)
                        // remainingDue is negative or zero here.
                        // Example: Previous 0. Remaining -100. Current = -100.
                        $currentBalance = $previousBalance + $remainingDue; 
                    }

                    // Log cash payment details
                    \Log::info("Cash Payment Details", [
                        'invoice_no' => $invoiceNo,
                        'bill_total' => $billTotal,
                        'previous_balance' => $previousBalance,
                        'advance_used' => $advanceUsed,
                        'explicit_advance_used' => $explicitAdvanceUsed,
                        'cash_received' => $paidAmount,
                        'remaining_due' => $remainingDue ?? 0
                    ]);
                }
                
                $customer->save();
            }

            // Handle custom customer name for walk-in
            $notes = $request->notes;
            if ($request->custom_customer_name) {
                $notes = "Walk-in Name: " . $request->custom_customer_name . "\n" . $notes;
            }

            // Create sale
            $sale = Sale::create([
                'invoice_no' => $invoiceNo,
                'customer_id' => $customerId,
                'user_id' => Auth::id(),
                'register_session_id' => $activeSession->id,
                'sold_at' => now(),
                'invoice_date' => $request->invoice_date,
                'payment_type' => $request->payment_type,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'utilities_charges' => $utilitiesCharges,
                'other_charges' => $otherCharges,
                'bill_total' => $billTotal,
                'previous_balance' => $previousBalance,
                'grand_total' => $grandTotal, // This might need adjustment for partial payment display
                'paid_amount' => $paidAmount + $advanceUsed, // Total paid (Cash + Advance)
                'current_balance' => $currentBalance,
                'advance_used' => $advanceUsed,
                'notes' => $notes,
                'system_description' => $request->system_description,
            ]);

            // Create Payment Record for Cash/Bank Received
            // This ensures the ledger reflects the cash/bank payment
            if (in_array($request->payment_type, ['cash', 'bank']) && $paidAmount > 0 && $customerId) {
                 \App\Models\Payment::create([
                    'customer_id' => $customerId,
                    'sale_id' => $sale->id,
                    'amount' => $paidAmount,
                    'type' => 'received',
                    'payment_date' => now(),
                    'payment_method' => $request->payment_type,
                    'note' => "Payment for Invoice #{$invoiceNo}",
                    'user_id' => Auth::id(),
                    'current_balance' => $currentBalance, // Save the balance snapshot
                ]);
            }

            // Create Pending Payment for Partial Cash/Bank Payment
            if (in_array($request->payment_type, ['cash', 'bank']) && isset($remainingDue) && $remainingDue > 0 && $customerId) {
                 PendingPayment::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $customerId,
                    'due_date' => now()->addDays(30), // Default due date
                    'amount_due' => $remainingDue,
                    'amount' => $remainingDue,
                    'settled' => false,
                ]);
                
                // Update customer credit used
                $customer->refresh();
                $customer->credit_used = $customer->balance;
                $customer->save();
            }

            // Create sale items and consume inventory
            foreach ($request->items as $item) {
                // Safely get description or default to empty string
                $itemDescription = $item['description'] ?? '';

                if (($item['type'] === 'custom') || empty($item['product_id'])) {
                    // For Custom Items: Store "Name|Description" to separate them later
                    $descriptionToStore = $item['name'];
                    if ($itemDescription) {
                        $descriptionToStore .= '|' . $itemDescription;
                    }

                    // Calculate Dimensions
                    $width = (float)($item['width'] ?? 0);
                    $height = (float)($item['height'] ?? $item['length'] ?? 0);
                    $qty = (float)$item['qty'];
                    $rate = (float)$item['rate'];
                    $discount = (float)($item['discount'] ?? 0);
                    
                    $unitsSqFt = 0;
                    $calcTotal = 0;

                    // If both width and height > 0, calculate as Area
                    if ($width > 0 && $height > 0) {
                        $unitsSqFt = $width * $height * $qty;
                        $calcTotal = ($unitsSqFt * $rate) - $discount;
                        $widthUnit = 'ft';
                        $lengthUnit = 'ft';
                    } else {
                        // Otherwise standard Qty
                        $unitsSqFt = $qty; // Or 0? Usually just Qty used.
                        // Wait, SaleItem 'units_sqft' col is for area. If simple item, maybe 0?
                        // Let's keep it 0 if not area based to avoid confusion, or store Qty?
                        // But for line_total calculation logic above we used Qty.
                        // Let's stick to calculating line_total correctly.
                        $calcTotal = ($qty * $rate) - $discount;
                        $widthUnit = null;
                        $lengthUnit = null;
                    }

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => null,
                        'description' => $descriptionToStore,
                        'quantity' => $qty,
                        'rate' => $rate,
                        'discount' => $discount,
                        'tax' => $item['tax'] ?? 0,
                        'units_sqft' => ($width > 0 && $height > 0) ? $unitsSqFt : 0, 
                        'line_total' => $calcTotal,
                        'length_input' => $height > 0 ? $height : null,
                        'length_unit' => $lengthUnit,
                        'width_input' => $width > 0 ? $width : null,
                        'width_unit' => $widthUnit,
                    ]);
                    continue;
                }

                $product = Product::with('panaflexSpec')->findOrFail($item['product_id']);
                
                // Calculate units and description
                $userDescription = $item['description'] ?? null;
                
                if ($item['type'] === 'panaflex_roll' && isset($item['length']) && isset($item['width'])) {
                    $unitsSqFt = AreaService::calcAreaSqFt(
                        $item['length'] ?? 1, 
                        $item['length_unit'] ?? 'ft', 
                        $item['width'] ?? 1, 
                        $item['width_unit'] ?? 'ft', 
                        $item['qty']
                    );
                    
                    // FIXED: Do NOT include dimensions in description
                    $description = $userDescription;
                } else {
                    $unitsSqFt = (float) $item['qty'];
                    $description = $userDescription;
                }

                $lineTotal = ($unitsSqFt * $item['rate']) - ($item['discount'] ?? 0) + ($item['tax'] ?? 0);

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'description' => $description,
                    'quantity' => $item['qty'],
                    'rate' => $item['rate'],
                    'discount' => $item['discount'] ?? 0,
                    'tax' => $item['tax'] ?? 0,
                    'units_sqft' => $unitsSqFt,
                    'line_total' => $lineTotal,
                    'length_input' => $item['length'] ?? null,
                    'length_unit' => $item['length_unit'] ?? null,
                    'width_input' => $item['width'] ?? null,
                    'width_unit' => $item['width_unit'] ?? null,
                ]);

                // Consume inventory
                if ($item['type'] === 'panaflex_roll') {
                    // Calculate required meters for panaflex roll
                    $rollWidthInch = $product->panaflexSpec->roll_width_inch ?? 126;
                    
                    $qty = (int) $item['qty'];
                    $length = (float) ($item['length'] ?? 1);
                    $lengthUnit = $item['length_unit'] ?? 'ft';
                    $width = (float) ($item['width'] ?? 1);
                    $widthUnit = $item['width_unit'] ?? 'ft'; // Default to ft to match AreaService

                    // NEW LOGIC: Deduct based on Area Sold (SqFt) converted to linear meters
                    // This ensures stock deduction matches the exact SqFt sold, ignoring waste/strips
                    $rollWidthFt = $rollWidthInch / 12;
                    $requiredMeters = ($unitsSqFt / $rollWidthFt) / 3.28;
                    
                    \Log::info("Panaflex Stock Deduction (Area Based)", [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'length' => $length,
                        'width' => $width,
                        'roll_width' => $rollWidthInch,
                        'units_sqft' => $unitsSqFt,
                        'meters_deducted' => $requiredMeters
                    ]);

                    $product->updateStock($requiredMeters, 'sale');
                } else {
                    $product->updateStock($item['qty'], 'sale');
                }
            }

            // Create pending payment for credit sales (only for current transaction amount)
            // Note: Previous balance (including OPB) already has its own PendingPayment
            if ($request->payment_type === 'credit') {
                // Calculate amount due for THIS transaction only (not including previous balance)
                $currentTransactionDue = $billTotal - $advanceUsed;
                
                if ($currentTransactionDue > 0) {
                    PendingPayment::create([
                        'sale_id' => $sale->id,
                        'customer_id' => $request->customer_id,
                        'due_date' => $request->due_date ?? now()->addDays(30),
                        'amount_due' => $currentTransactionDue, // Only current bill, not previous balance
                        'amount' => $currentTransactionDue,
                        'settled' => false,
                    ]);
                }
                
                // Recalculate credit_used from actual pending payments
                // Use full balance logic to account for payments
                $customer->refresh();
                $customer->credit_used = $customer->balance;
                $customer->save();
            }

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'grand_total' => $sale->grand_total,
                'advance_used' => $advanceUsed,
                'cash_required' => $billTotal - $advanceUsed,
                'payment_breakdown' => [
                    'bill_total' => $billTotal,
                    'advance_used' => $advanceUsed,
                    'cash_required' => $billTotal - $advanceUsed,
                    'customer_advance_remaining' => $request->customer_id ? 
                        Customer::find($request->customer_id)->advance_balance : 0
                ],
                'printable_urls' => [
                    'a4' => route('pos.print.a4', $sale->id),
                    '80mm' => route('pos.print.80mm', $sale->id),
                ],
                'preview_urls' => [
                    'a4' => route('pos.preview.a4', $sale->id),
                    '80mm' => route('pos.preview.80mm', $sale->id),
                ],
            ]);
        });
    }

    /**
     * Print A4 receipt
     */
    public function printA4(Sale $sale)
    {
        $sale->load(['customer', 'user', 'saleItems.product.unit', 'saleItems.product.panaflexSpec']);
        $settings = CompanySetting::first();

        return view('prints.invoice_a4', compact('sale', 'settings'));
    }

    /**
     * Print 80mm receipt
     */
    public function print80mm(Sale $sale)
    {
        $sale->load(['customer', 'user', 'saleItems.product.unit', 'saleItems.product.panaflexSpec']);
        $settings = CompanySetting::first();

        return view('prints.invoice_80mm', compact('sale', 'settings'));
    }

    /**
     * Preview A4 invoice
     */
    public function previewA4(Sale $sale)
    {
        $sale->load(['customer', 'user', 'saleItems.product.unit', 'saleItems.product.panaflexSpec']);
        $settings = CompanySetting::first();

        return view('prints.invoice_a4', compact('sale', 'settings'));
    }

    /**
     * Preview 80mm receipt
     */
    public function preview80mm(Sale $sale)
    {
        $sale->load(['customer', 'user', 'saleItems.product.unit', 'saleItems.product.panaflexSpec']);
        $settings = CompanySetting::first();

        return view('prints.invoice_80mm', compact('sale', 'settings'));
    }
}
