<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'quantity',
        'units_sqft',
        'rate',
        'line_total',
        'note',
        'length_input',
        'length_unit',
        'width_input',
        'width_unit',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'units_sqft' => 'decimal:2',
        'rate' => 'decimal:2',
        'line_total' => 'decimal:2',
        'length_input' => 'decimal:4',
        'width_input' => 'decimal:4',
    ];

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    /**
     * Calculate line total for simple items
     */
    public function calculateSimpleLineTotal(): void
    {
        $this->line_total = (-1) * $this->quantity * $this->rate;
        $this->save();
    }

    /**
     * Calculate line total for panaflex items
     */
    public function calculatePanaflexLineTotal(): void
    {
        $this->line_total = (-1) * $this->units_sqft * $this->rate;
        $this->save();
    }

    /**
     * Check if this is a panaflex item
     */
    public function isPanaflexItem(): bool
    {
        return $this->saleItem->product->category === 'panaflex';
    }
}
