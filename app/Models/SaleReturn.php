<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    protected $fillable = [
        'sale_id',
        'user_id',
        'return_no',
        'returned_at',
        'subtotal',
        'discount_total',
        'tax_total',
        'other_adjustments',
        'grand_total',
        'reason',
    ];

    protected $casts = [
        'returned_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'other_adjustments' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    /**
     * Generate next return number
     */
    public static function generateReturnNo(): string
    {
        $prefix = config('app.return_prefix', 'RTN-');
        $lastReturn = static::orderBy('id', 'desc')->first();
        
        if (!$lastReturn) {
            return $prefix . '0000001';
        }

        // Extract number from last return_no
        $lastNumber = (int) str_replace($prefix, '', $lastReturn->return_no);
        $nextNumber = $lastNumber + 1;

        return $prefix . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate and update totals
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('line_total');
        
        $this->update([
            'subtotal' => $subtotal,
            'grand_total' => $subtotal - $this->discount_total + $this->tax_total + $this->other_adjustments,
        ]);
    }
}
