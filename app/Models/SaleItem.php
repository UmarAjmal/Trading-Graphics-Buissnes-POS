<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\AreaService;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'description',
        'quantity',
        'rate',
        'discount',
        'tax',
        'units_sqft',
        'line_total',
        'length_input',
        'length_unit',
        'width_input',
        'width_unit',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'units_sqft' => 'decimal:2',
        'line_total' => 'decimal:2',
        'length_input' => 'decimal:4',
        'width_input' => 'decimal:4',
    ];

    /**
     * Get the sale that owns this item
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get all return items for this sale item
     */
    public function returnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    /**
     * Calculate units_sqft based on product type and inputs
     */
    public function calculateUnits(): float
    {
        if ($this->product->type === 'panaflex_roll') {
            if ($this->length_input && $this->width_input && $this->length_unit && $this->width_unit) {
                return AreaService::calcAreaSqFt(
                    $this->length_input,
                    $this->length_unit,
                    $this->width_input,
                    $this->width_unit,
                    $this->quantity
                );
            }
            return 0;
        }
        
        // Simple product: units = quantity
        return (float) $this->quantity;
    }

    /**
     * Calculate line total based on units and rate
     */
    public function calculateLineTotal(): float
    {
        $baseAmount = $this->units_sqft * $this->rate;
        return $baseAmount - $this->discount + $this->tax;
    }

    /**
     * Get the total accessor (alias for line_total)
     */
    public function getTotalAttribute(): float
    {
        return (float) $this->line_total;
    }

    /**
     * Update calculated fields
     */
    public function updateCalculations(): void
    {
        $this->units_sqft = $this->calculateUnits();
        $this->line_total = $this->calculateLineTotal();
        $this->save();
    }

    /**
     * Get formatted description for display
     */
    public function getFormattedDescriptionAttribute(): string
    {
        if ($this->product->type === 'panaflex_roll' && $this->length_input && $this->width_input) {
            $length = number_format($this->length_input, 2) . $this->length_unit;
            $width = number_format($this->width_input, 2) . $this->width_unit;
            return "{$this->product->name} - {$length} × {$width} × {$this->quantity}";
        }
        
        return $this->product->name . ($this->quantity > 1 ? " × {$this->quantity}" : '');
    }

    /**
     * Get formatted units for display
     */
    public function getFormattedUnitsAttribute(): string
    {
        if ($this->product->type === 'panaflex_roll') {
            return number_format($this->units_sqft, 2) . ' sq.ft';
        }
        
        return $this->quantity . ' ' . ($this->product->unit->symbol ?? 'pcs');
    }

    /**
     * Get formatted line total
     */
    public function getFormattedLineTotalAttribute(): string
    {
        return 'PKR ' . number_format($this->line_total, 2);
    }

    /**
     * Get total returned quantity for simple items
     */
    public function getReturnedQuantityAttribute(): int
    {
        return $this->returnItems->sum('quantity');
    }

    /**
     * Get total returned units for panaflex items
     */
    public function getReturnedUnitsAttribute(): float
    {
        return (float) $this->returnItems->sum('units_sqft');
    }

    /**
     * Get remaining returnable quantity for simple items
     */
    public function getRemainingQuantityAttribute(): int
    {
        return $this->quantity - $this->returned_quantity;
    }

    /**
     * Get remaining returnable units for panaflex items
     */
    public function getRemainingUnitsAttribute(): float
    {
        return $this->units_sqft - $this->returned_units;
    }

    /**
     * Check if this item has any returns
     */
    public function getHasReturnsAttribute(): bool
    {
        return $this->returnItems->count() > 0;
    }

    /**
     * Check if item is fully returned
     */
    public function getIsFullyReturnedAttribute(): bool
    {
        if ($this->product->type === 'panaflex_roll') {
            return $this->remaining_units <= 0;
        }
        return $this->remaining_quantity <= 0;
    }
}
