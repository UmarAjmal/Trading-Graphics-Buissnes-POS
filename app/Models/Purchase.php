<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_no',
        'supplier_id',
        'user_id',
        'purchased_at',
        'expected_date',
        'subtotal',
        'discount_total',
        'tax_total',
        'other_charges',
        'shipping_charges',
        'grand_total',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'expected_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'shipping_charges' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    /**
     * Get the supplier that owns the purchase.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user that created the purchase.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the purchase items for the purchase.
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Alias for purchaseItems to match frontend expectations
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Generate next purchase number
     */
    public static function generatePurchaseNumber(): string
    {
        $lastPurchase = static::orderBy('id', 'desc')->first();
        $nextNumber = $lastPurchase ? (int) substr($lastPurchase->purchase_no, 4) + 1 : 1;
        return 'PUR-' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
    }
}