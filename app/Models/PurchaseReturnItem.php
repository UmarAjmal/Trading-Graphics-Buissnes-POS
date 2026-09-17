<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id',
        'purchase_item_id',
        'product_id',
        'quantity',
        'units_sqft',
        'roll_width_inch',
        'roll_length_meter',
        'rolls_count',
        'length_input',
        'length_unit',
        'width_input',
        'width_unit',
        'rate',
        'line_total',
        'note',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'units_sqft' => 'decimal:2',
        'roll_width_inch' => 'decimal:2',
        'roll_length_meter' => 'decimal:2',
        'rolls_count' => 'decimal:2',
        'length_input' => 'decimal:4',
        'width_input' => 'decimal:4',
        'rate' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
