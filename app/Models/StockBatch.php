<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class StockBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'batch_no',
        'purchase_item_id',
        'qty_total',
        'qty_remaining',
        'roll_width_inch',
        'meters_total',
        'meters_remaining',
        'received_at',
    ];

    protected $casts = [
        'qty_total' => 'integer',
        'qty_remaining' => 'integer',
        'roll_width_inch' => 'decimal:2',
        'meters_total' => 'decimal:2',
        'meters_remaining' => 'decimal:2',
        'received_at' => 'date',
    ];

    /**
     * Get the product that owns the stock batch.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the purchase item that owns the stock batch.
     */
    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    /**
     * Get the stock moves for the stock batch.
     */
    public function stockMoves(): HasMany
    {
        return $this->hasMany(StockMove::class, 'batch_id');
    }

    /**
     * Check if batch is empty
     */
    public function isEmpty(): bool
    {
        if ($this->product->type === 'simple') {
            return $this->qty_remaining <= 0;
        } else {
            return $this->meters_remaining <= 0;
        }
    }

    /**
     * Generate batch number
     */
    public static function generateBatchNumber(Product $product): string
    {
        $date = Carbon::now()->format('Ymd');
        $count = static::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::today())
            ->count() + 1;
        
        return $product->sku . '-' . $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}