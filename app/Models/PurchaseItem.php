<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'received_quantity',
        'roll_width_inch',
        'roll_length_meter',
        'rolls_count',
        'rate',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'roll_width_inch' => 'decimal:2',
        'roll_length_meter' => 'decimal:2',
        'rolls_count' => 'decimal:2',
        'rate' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    /**
     * Get the purchase that owns the purchase item.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the product that owns the purchase item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the stock batches for the purchase item.
     */
    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    /**
     * Calculate total meters for panaflex rolls
     */
    public function getTotalMetersAttribute(): ?float
    {
        if ($this->product->type === 'panaflex_roll' && $this->rolls_count && $this->roll_length_meter) {
            return round($this->roll_length_meter * $this->rolls_count, 2);
        }
        return null;
    }
}