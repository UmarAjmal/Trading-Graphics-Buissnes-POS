<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'type',
        'unit_id',
        'description',
        'sale_rate',
        'purchase_rate',
        'taxable',
        'barcode',
        'image_path',
        'active',
        'min_qty',
        'min_meters',
        'stock_quantity',
        'stock_meters',
    ];

    protected $casts = [
        'taxable' => 'boolean',
        'active' => 'boolean',
        'sale_rate' => 'decimal:2',
        'purchase_rate' => 'decimal:2',
        'min_qty' => 'integer',
        'min_meters' => 'decimal:2',
        'stock_quantity' => 'decimal:2',
        'stock_meters' => 'decimal:2',
        'description' => 'string',
    ];

    protected $appends = [
        'selling_price',
        'cost_price',
        'price',
        'is_active',
        'current_stock',
        'min_stock',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the unit that owns the product.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the panaflex specification for this product.
     */
    public function panaflexSpec(): HasOne
    {
        return $this->hasOne(PanaflexSpec::class);
    }

    /**
     * Get the stock batches for this product.
     */
    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    /**
     * Get the stock moves for this product.
     */
    public function stockMoves(): HasMany
    {
        return $this->hasMany(StockMove::class);
    }

    /**
     * Get the stock adjustments for this product.
     */
    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    /**
     * Get the purchase items for this product.
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Get the sale items for this product.
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get the image URL attribute.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $this->image_path 
                ? asset('storage/' . $this->image_path) 
                : null,
        );
    }

    /**
     * Check if this is a panaflex roll product.
     */
    public function isPanaflexRoll(): bool
    {
        return $this->type === 'panaflex_roll';
    }

    /**
     * Check if this is a simple product.
     */
    public function isSimple(): bool
    {
        return $this->type === 'simple';
    }


    /**
     * Get the price attribute (alias for sale_rate for compatibility)
     */
    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->sale_rate,
        );
    }

    /**
     * Get the selling_price attribute (alias for sale_rate for frontend compatibility)
     */
    protected function sellingPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->sale_rate,
        );
    }

    /**
     * Get the cost_price attribute (alias for purchase_rate for frontend compatibility)
     */
    protected function costPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->purchase_rate,
        );
    }

    /**
     * Get the is_active attribute (alias for active for compatibility)
     */
    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->active,
        );
    }

    /**
     * Get the current_stock attribute (returns appropriate stock based on product type)
     */
    protected function currentStock(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->type === 'panaflex_roll') {
                    $widthFt = 1;
                    if ($this->panaflexSpec && $this->panaflexSpec->roll_width_inch) {
                        $widthFt = $this->panaflexSpec->roll_width_inch / 12;
                    }
                    return $this->stock_meters ? round($this->stock_meters * 3.28 * $widthFt, 2) : 0;
                }
                return $this->stock_quantity;
            },
        );
    }

    /**
     * Get the min_stock attribute (returns appropriate minimum stock based on product type)
     */
    protected function minStock(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->type === 'panaflex_roll') {
                    $widthFt = 1;
                    if ($this->panaflexSpec && $this->panaflexSpec->roll_width_inch) {
                        $widthFt = $this->panaflexSpec->roll_width_inch / 12;
                    }
                    return $this->min_meters ? round($this->min_meters * 3.28 * $widthFt, 2) : 0;
                }
                return $this->min_qty;
            },
        );
    }

    /**
     * Check if product has sufficient stock
     */
    public function hasStock(float $required): bool
    {
        if ($this->type === 'panaflex_roll') {
            return $this->stock_meters >= $required;
        }
        return $this->stock_quantity >= $required;
    }

    /**
     * Get current stock level
     */
    public function getCurrentStock(): float
    {
        if ($this->type === 'panaflex_roll') {
            return $this->stock_meters;
        }
        return $this->stock_quantity;
    }

    /**
     * Update stock levels
     */
    public function updateStock(float $quantity, string $type = 'sale'): void
    {
        if ($this->type === 'panaflex_roll') {
            $this->stock_meters = max(0, $this->stock_meters + ($type === 'sale' ? -$quantity : $quantity));
        } else {
            $this->stock_quantity = max(0, $this->stock_quantity + ($type === 'sale' ? -$quantity : $quantity));
        }
        $this->save();
    }

    /**
     * Generate SKU for product
     */
    public static function generateSku(string $name, string $type = 'simple'): string
    {
        // Clean the name and convert to uppercase
        $cleanName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
        
        // Take first 3 characters of the name
        $namePrefix = substr($cleanName, 0, 3);
        
        // Add type prefix
        $typePrefix = $type === 'panaflex_roll' ? 'PF' : 'SP';
        
        // Generate random number suffix
        $randomSuffix = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Combine to create SKU
        $sku = $typePrefix . $namePrefix . $randomSuffix;
        
        // Check if SKU already exists, if so, generate a new one
        while (static::where('sku', $sku)->exists()) {
            $randomSuffix = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $sku = $typePrefix . $namePrefix . $randomSuffix;
        }
        
        return $sku;
    }
}
