<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMove extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'ref_id',
        'ref_table',
        'batch_id',
        'qty_change',
        'meters_change',
        'user_id',
        'note',
    ];

    protected $casts = [
        'qty_change' => 'decimal:2',
        'meters_change' => 'decimal:2',
    ];

    /**
     * Get the product that owns the stock move.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the stock batch that owns the stock move.
     */
    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    /**
     * Get the user that created the stock move.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the referenced model (purchase, sale, return, adjustment)
     */
    public function referencedModel()
    {
        if (!$this->ref_table || !$this->ref_id) {
            return null;
        }

        $modelClass = match($this->ref_table) {
            'purchases' => Purchase::class,
            'sales' => Sale::class,
            'sale_returns' => SaleReturn::class,
            'adjustments' => StockAdjustment::class,
            default => null,
        };

        return $modelClass ? $modelClass::find($this->ref_id) : null;
    }

    /**
     * Get formatted change amount
     */
    public function getFormattedChangeAttribute(): string
    {
        if ($this->qty_change !== null) {
            return number_format($this->qty_change, 0) . ' pcs';
        } elseif ($this->meters_change !== null) {
            return number_format($this->meters_change, 2) . ' m';
        }
        return '0';
    }
}