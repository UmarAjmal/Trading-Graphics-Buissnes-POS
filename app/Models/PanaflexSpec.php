<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PanaflexSpec extends Model
{
    protected $fillable = [
        'product_id',
        'roll_width_inch',
        'roll_length_meter',
        'rate_per_sqft',
    ];

    protected $casts = [
        'roll_width_inch' => 'decimal:2',
        'roll_length_meter' => 'decimal:2',
        'rate_per_sqft' => 'decimal:2',
    ];

    protected $appends = [
        'roll_width_ft',
        'roll_length_ft',
    ];

    /**
     * Get the product that owns this specification.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the roll width in feet.
     */
    protected function rollWidthFt(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->roll_width_inch / 12, 2),
        );
    }

    /**
     * Get the roll length in feet.
     */
    protected function rollLengthFt(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->roll_length_meter * 3.28, 2),
        );
    }

    /**
     * Get the roll width in feet.
     */
    protected function rollWidthFeet(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->roll_width_inch / 12, 2),
        );
    }

    /**
     * Calculate total square feet per roll.
     */
    public function getTotalSquareFeet(): float
    {
        // Width in feet × Length in meters × 3.28 (meters to feet conversion)
        return round(($this->roll_width_inch / 12) * ($this->roll_length_meter * 3.28), 2);
    }

    /**
     * Calculate the total value of the roll.
     */
    public function getRollValue(): float
    {
        return round($this->getTotalSquareFeet() * $this->rate_per_sqft, 2);
    }
}
