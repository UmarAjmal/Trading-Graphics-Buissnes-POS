<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'reason',
        'qty_delta',
        'meters_delta',
        'note',
    ];

    protected $casts = [
        'qty_delta' => 'decimal:2',
        'meters_delta' => 'decimal:2',
    ];

    /**
     * Get the product that owns the stock adjustment.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user that created the stock adjustment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted delta amount
     */
    public function getFormattedDeltaAttribute(): string
    {
        if ($this->qty_delta !== null) {
            return ($this->qty_delta >= 0 ? '+' : '') . number_format($this->qty_delta, 0) . ' pcs';
        } elseif ($this->meters_delta !== null) {
            return ($this->meters_delta >= 0 ? '+' : '') . number_format($this->meters_delta, 2) . ' m';
        }
        return '0';
    }

    /**
     * Get reason label
     */
    public function getReasonLabelAttribute(): string
    {
        return match($this->reason) {
            'damage' => 'Damage',
            'shrinkage' => 'Shrinkage',
            'correction' => 'Correction',
            'other' => 'Other',
            default => 'Unknown',
        };
    }
}