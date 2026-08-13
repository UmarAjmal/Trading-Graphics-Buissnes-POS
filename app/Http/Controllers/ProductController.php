<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\PanaflexSpec;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Picqer\Barcode\BarcodeGeneratorHTML;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('Products/Index', [
            'categories' => Category::all(['id', 'name']),
            'units' => Unit::all(['id', 'code', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Products/Form', [
            'categories' => Category::all(['id', 'name']),
            'units' => Unit::all(['id', 'code', 'name']),
            'product' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);

        try {
            // Map form data to database fields
            $productData = $this->mapFormDataToDatabase($validated);

            // Handle image upload
            if ($request->hasFile('image')) {
                $productData['image_path'] = $request->file('image')->store('products', 'public');
            }

            // Handle barcode
            if (empty($validated['barcode'])) {
                $productData['barcode'] = Product::generateSku($validated['name']);
            }

            // Create product
            $product = Product::create($productData);

            // Create panaflex spec if needed
            if ($validated['type'] === 'panaflex_roll') {
                // Convert Feet to Inches/Meters for storage
                $widthInches = $request->roll_width_inch;
                $lengthMeters = $request->roll_length_meter;

                PanaflexSpec::create([
                    'product_id' => $product->id,
                    'roll_width_inch' => $widthInches,
                    'roll_length_meter' => $lengthMeters,
                    'rate_per_sqft' => $validated['rate_per_sqft'],
                ]);
            }

            return redirect()->route('products.index')
                ->with('success', 'Product created successfully.');

        } catch (\Exception $e) {
            \Log::error('Product creation error: ' . $e->getMessage());
            return back()
                ->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): Response
    {
        $product->load(['category', 'unit', 'panaflexSpec']);
        
        // Convert stock meters to sq ft for display if panaflex
        if ($product->type === 'panaflex_roll') {
            $widthFt = 1;
            if ($product->panaflexSpec && $product->panaflexSpec->roll_width_inch) {
                $widthFt = $product->panaflexSpec->roll_width_inch / 12;
            }
            
            // Linear Meters to Sq Ft: LinearM * 3.28 * WidthFt
            $product->current_stock_ft = $product->stock_meters ? round(($product->stock_meters * 3.28) * $widthFt, 2) : 0;
            
            // Do not convert roll_width_inch and roll_length_meter to feet. Show page uses roll_width_ft and roll_length_ft.
        }
        
        return Inertia::render('Products/Show', [
            'product' => $product,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): Response
    {
        $product->load(['category', 'unit', 'panaflexSpec']);
        
        // Convert stock meters to sq ft for display if panaflex
        if ($product->type === 'panaflex_roll') {
            $widthFt = 1;
            if ($product->panaflexSpec && $product->panaflexSpec->roll_width_inch) {
                $widthFt = $product->panaflexSpec->roll_width_inch / 12;
            }

            $product->current_stock = $product->stock_meters ? round(($product->stock_meters * 3.28) * $widthFt, 2) : 0;
            $product->min_stock = $product->min_meters ? round(($product->min_meters * 3.28) * $widthFt, 2) : 0;
            
            // Do not convert roll_width_inch and roll_length_meter to feet. Form.vue expects raw database values.
        }

        return Inertia::render('Products/Form', [
            'categories' => Category::all(['id', 'name']),
            'units' => Unit::all(['id', 'code', 'name']),
            'product' => $product,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $this->validateProduct($request, $product->id);

        try {
            // Map form data to database fields
            $productData = $this->mapFormDataToDatabase($validated);

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image
                if ($product->image_path) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $productData['image_path'] = $request->file('image')->store('products', 'public');
            }

            // Handle barcode
            if (empty($validated['barcode'])) {
                $productData['barcode'] = $product->barcode ?: Product::generateSku($validated['name']);
            }

            // Update product
            $product->update($productData);

            // Handle panaflex spec
            if ($validated['type'] === 'panaflex_roll') {
                // Convert Feet to Inches/Meters for storage
                $widthInches = $request->roll_width_inch;
                $lengthMeters = $request->roll_length_meter;

                $product->panaflexSpec()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'roll_width_inch' => $widthInches,
                        'roll_length_meter' => $lengthMeters,
                        'rate_per_sqft' => $validated['rate_per_sqft'],
                    ]
                );
            } else {
                // Delete panaflex spec if type changed to simple
                $product->panaflexSpec()->delete();
            }

            return redirect()->route('products.index')
                ->with('success', 'Product updated successfully.');

        } catch (\Exception $e) {
            \Log::error('Product update error: ' . $e->getMessage());
            return back()
                ->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        try {
            // Delete image if exists
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $product->delete();

            return redirect()->route('products.index')
                ->with('success', 'Product deleted successfully.');

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete product: ' . $e->getMessage()]);
        }
    }

    /**
     * Get products data for DataTable (API).
     */
    public function tableData(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'unit', 'panaflexSpec']);

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by category
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by active status
        if ($request->has('active') && $request->active !== 'all') {
            $query->where('active', $request->active === 'true');
        }

        // Sorting
        $sortField = $request->get('sort_field', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $products = $query->paginate($perPage);

        // Transform data for display (convert meters to feet for panaflex)
        $products->getCollection()->transform(function ($product) {
            if ($product->type === 'panaflex_roll') {
                // Set virtual unit for display
                if (!$product->unit) {
                    $product->setRelation('unit', new Unit(['symbol' => 'sq.ft', 'name' => 'Square Feet']));
                }
            }
            return $product;
        });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Generate SKU for a product name.
     */
    public function generateSku(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'type' => 'nullable|string|in:simple,panaflex_roll',
            'exclude_id' => 'nullable|integer',
        ]);

        $type = $request->input('type', 'simple');
        $sku = Product::generateSku($request->name, $type);

        return response()->json([
            'success' => true,
            'sku' => $sku
        ]);
    }

    /**
     * Validate product data.
     */
    private function validateProduct(Request $request, ?int $excludeId = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'sku' => [
                'required', 
                'string', 
                'max:64', 
                Rule::unique('products', 'sku')->ignore($excludeId)
            ],
            'type' => 'required|in:simple,panaflex_roll',
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'description' => 'nullable|string|max:1000',
            'sale_rate' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'purchase_rate' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'taxable' => 'nullable|boolean',
            'barcode' => 'nullable|string|max:128',
            'image' => 'nullable|image|max:1024',
            'active' => 'nullable|boolean',
        ];

        $messages = [
            'name.required' => 'Product name is required.',
            'sku.required' => 'Product SKU is required.',
            'sku.unique' => 'This SKU already exists.',
            'type.required' => 'Product type is required.',
            'type.in' => 'Product type must be either simple or panaflex_roll.',
            'unit_id.required' => 'Unit is required for simple products.',
            'roll_width_inch.required' => 'Roll width is required for panaflex products.',
            'roll_length_meter.required' => 'Roll length (meters) is required for panaflex products.',
            'rate_per_sqft.required' => 'Rate per square foot is required for panaflex products.',
        ];

        // Add panaflex-specific rules
        if ($request->type === 'panaflex_roll') {
            // Accept feet input, but we'll convert it later. 
            // Validation should be on the input fields (roll_width_inch, roll_length_meter)
            $rules['roll_width_inch'] = 'required|numeric|min:1'; // Max 20ft seems reasonable
            $rules['roll_length_meter'] = 'required|numeric|min:0.1'; // Max 500ft
            $rules['rate_per_sqft'] = 'required|numeric|min:0';
            
            // Unit is not needed for panaflex rolls
            $rules['unit_id'] = 'nullable';
        } else {
            // Unit is required for simple products
            $rules['unit_id'] = 'required|exists:units,id';
        }

        return $request->validate($rules, $messages);
    }

    /**
     * Map form data to database fields
     */
    private function mapFormDataToDatabase(array $validated): array
    {
        $mapped = [
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'type' => $validated['type'],
            'category_id' => $validated['category_id'] ?? null,
            'unit_id' => $validated['unit_id'] ?? null,
            'description' => $validated['description'] ?? '',
            'sale_rate' => $validated['selling_price'] ?? $validated['sale_rate'] ?? 0,
            'purchase_rate' => $validated['cost_price'] ?? $validated['purchase_rate'] ?? 0,
            'taxable' => $validated['taxable'] ?? false,
            'barcode' => $validated['barcode'] ?? '',
            'active' => $validated['active'] ?? true,
        ];

        // Handle stock fields based on product type
        if ($validated['type'] === 'panaflex_roll') {
            // Convert Sq Ft to Linear Meters for storage
            // roll_width_inch is in inches, convert it to feet
            $widthFt = isset($validated['roll_width_inch']) ? (floatval($validated['roll_width_inch']) / 12) : 1;
            $widthFt = $widthFt > 0 ? $widthFt : 1; // Prevent division by zero
            
            // Convert Sq Ft to Linear Feet first: SqFt / WidthFt
            // Then Linear Feet to Meters: LinearFt / 3.28
            $linearFeet = isset($validated['current_stock']) ? ($validated['current_stock'] / $widthFt) : 0;
            $minLinearFeet = isset($validated['min_stock']) ? ($validated['min_stock'] / $widthFt) : 0;

            $mapped['stock_meters'] = $linearFeet / 3.28;
            $mapped['min_meters'] = $minLinearFeet / 3.28;
            $mapped['stock_quantity'] = 0;
            $mapped['min_qty'] = 0;
        } else {
            $mapped['stock_quantity'] = $validated['current_stock'] ?? 0;
            $mapped['min_qty'] = $validated['min_stock'] ?? 0;
            $mapped['stock_meters'] = 0;
            $mapped['min_meters'] = 0;
        }

        return $mapped;
    }

    /**
     * Export products to Excel.
     */
    public function export(Request $request)
    {
        try {
            \Log::info('Products export started');
            $export = new ProductsExport;
            \Log::info('ProductsExport created');
            return Excel::download($export, 'products.xlsx');
        } catch (\Exception $e) {
            \Log::error('Products export failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import products from CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
            'create_categories' => 'boolean',
            'create_units' => 'boolean',
        ]);

        try {
            $import = new ProductsImport(
                $request->boolean('create_categories', false),
                $request->boolean('create_units', false)
            );
            
            Excel::import($import, $request->file('file'));

            $summary = $import->getSummary();

            return back()->with('success', 
                "Import completed! Created: {$summary['created']}, Updated: {$summary['updated']}, Errors: {$summary['errors']}"
            );

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate barcode for printing.
     */
    public function barcode(Product $product, Request $request)
    {
        $request->validate([
            'quantity' => 'nullable|integer|min:1|max:500',
            'layout' => 'nullable|string|in:2x6,2x12,3x8,auto',
        ]);

        $quantity = $request->get('quantity', 1);
        $layout = $request->get('layout', 'auto');
        
        // Auto-determine layout based on quantity
        if ($layout === 'auto') {
            if ($quantity <= 12) {
                $layout = '2x6';
            } else {
                $layout = '3x8';
            }
        }
        
        $generator = new BarcodeGeneratorHTML();
        $barcodeHtml = $generator->getBarcode($product->barcode ?: $product->sku, $generator::TYPE_CODE_128);
        
        return view('products.barcode', [
            'product' => $product,
            'barcodeHtml' => $barcodeHtml,
            'quantity' => $quantity,
            'layout' => $layout,
        ]);
    }

    /**
     * API: Store new product
     */
    public function apiStore(Request $request): JsonResponse
    {
        try {
            // Debug: Log the request data
            \Log::info('Product Store Request Data:', $request->all());
            
            $validated = $this->validateProduct($request);

            // Map form data to database fields
            $createData = $this->mapFormDataToDatabase($validated);

            // Handle image upload
            if ($request->hasFile('image')) {
                $createData['image_path'] = $request->file('image')->store('products', 'public');
            }

            // Handle barcode
            if (empty($validated['barcode'])) {
                $createData['barcode'] = Product::generateSku($validated['name']);
            }

            // Create product
            $product = Product::create($createData);

            // Create panaflex spec if needed
            if ($validated['type'] === 'panaflex_roll') {
                // Convert Feet to Inches/Meters for storage
                $widthInches = $request->roll_width_inch;
                $lengthMeters = $request->roll_length_meter;

                PanaflexSpec::create([
                    'product_id' => $product->id,
                    'roll_width_inch' => $widthInches,
                    'roll_length_meter' => $lengthMeters,
                    'rate_per_sqft' => $validated['rate_per_sqft'],
                ]);
            }

            $product->load(['category', 'unit', 'panaflexSpec']);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product
            ]);

        } catch (\Exception $e) {
            \Log::error('Product API creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * API: Show product details
     */
    public function apiShow(Product $product): JsonResponse
    {
        $product->load(['category', 'unit', 'panaflexSpec']);
        
        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * API: Update product
     */
    public function apiUpdate(Request $request, Product $product): JsonResponse
    {
        try {
            // Debug: Log the request data
            \Log::info('Product Update Request Data:', [
                'all_data' => $request->all(),
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'has_file' => $request->hasFile('image'),
                'form_data' => [
                    'name' => $request->input('name'),
                    'sku' => $request->input('sku'),
                    'type' => $request->input('type'),
                    'selling_price' => $request->input('selling_price'),
                    'cost_price' => $request->input('cost_price'),
                ]
            ]);
            
            $validated = $this->validateProduct($request, $product->id);

            // Map form data to database fields
            $updateData = $this->mapFormDataToDatabase($validated);

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image
                if ($product->image_path) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $updateData['image_path'] = $request->file('image')->store('products', 'public');
            }

            // Handle barcode
            if (empty($validated['barcode'])) {
                $updateData['barcode'] = $product->barcode ?: Product::generateSku($validated['name']);
            }

            // Update product
            $product->update($updateData);

            // Handle panaflex spec
            if ($validated['type'] === 'panaflex_roll') {
                // Convert Feet to Inches/Meters for storage
                $widthInches = $request->roll_width_inch;
                $lengthMeters = $request->roll_length_meter;

                $product->panaflexSpec()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'roll_width_inch' => $widthInches,
                        'roll_length_meter' => $lengthMeters,
                        'rate_per_sqft' => $validated['rate_per_sqft'],
                    ]
                );
            } else {
                // Delete panaflex spec if type changed to simple
                $product->panaflexSpec()->delete();
            }

            $product->load(['category', 'unit', 'panaflexSpec']);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product
            ]);

        } catch (\Exception $e) {
            \Log::error('Product API update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * API: Delete product
     */
    public function apiDestroy(Product $product): JsonResponse
    {
        try {
            // Delete image if exists
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product: ' . $e->getMessage()
            ], 422);
        }
    }

}



