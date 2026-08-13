<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    protected $fillable = [
        'invoice_no',
        'customer_id',
        'user_id',
        'register_session_id',
        'sold_at',
        'invoice_date',
        'payment_type',
        'subtotal',
        'discount_total',
        'tax_total',
        'utilities_charges',
        'other_charges',
        'bill_total',
        'previous_balance',
        'grand_total',
        'paid_amount',
        'current_balance',
        'advance_used',
        'notes',
        'system_description',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'invoice_date' => 'date:Y-m-d',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'utilities_charges' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'bill_total' => 'decimal:2',
        'previous_balance' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'advance_used' => 'decimal:2',
    ];

    protected $appends = [
        'invoice_number',
    ];

    /**
     * Get the customer that owns the sale
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user (cashier) that made the sale
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the register session for this sale
     */
    public function registerSession(): BelongsTo
    {
        return $this->belongsTo(RegisterSession::class);
    }

    /**
     * Get all sale items for this sale
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get pending payment if this is a credit sale
     */
    public function pendingPayment(): HasOne
    {
        return $this->hasOne(PendingPayment::class);
    }

    /**
     * Get all credit payments for this sale
     */
    public function creditPayments(): HasMany
    {
        return $this->hasMany(CustomerCreditPayment::class);
    }

    /**
     * Get all payments for this sale
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all returns for this sale
     */
    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    /**
     * Generate next invoice number based on settings
     */
    public static function generateInvoiceNumber(): string
    {
        $settings = CompanySetting::first();
        $prefix = $settings->invoice_prefix ?? 'INV-';
        
        // Use database transaction to prevent race conditions
        return \DB::transaction(function () use ($prefix) {
            // Get the last invoice number for this prefix with proper ordering
            $lastSale = static::where('invoice_no', 'like', $prefix . '%')
                ->orderByRaw('CAST(SUBSTR(invoice_no, ?) AS INTEGER) DESC', [strlen($prefix) + 1])
                ->lockForUpdate()
                ->first();

            if ($lastSale) {
                // Extract numeric part more reliably
                $invoiceWithoutPrefix = substr($lastSale->invoice_no, strlen($prefix));
                // Remove leading zeros and convert to int
                $lastNumber = (int) ltrim($invoiceWithoutPrefix, '0') ?: 0;
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            // Generate the invoice number
            $invoiceNo = $prefix . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
            
            // Double-check uniqueness (extra safety)
            $attempts = 0;
            while (static::where('invoice_no', $invoiceNo)->exists() && $attempts < 10) {
                $nextNumber++;
                $invoiceNo = $prefix . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
                $attempts++;
            }
            
            if ($attempts >= 10) {
                throw new \Exception('Unable to generate unique invoice number after 10 attempts');
            }

            return $invoiceNo;
        });
    }

    /**
     * Calculate and update totals based on sale items
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->saleItems->sum('line_total');
        
        $this->update([
            'subtotal' => $subtotal,
            'grand_total' => $subtotal - $this->discount_total + $this->tax_total + $this->other_charges,
        ]);
    }

    /**
     * Get invoice number (alias for invoice_no)
     */
    public function getInvoiceNumberAttribute(): string
    {
        return $this->invoice_no ?? '';
    }

    /**
     * Get formatted grand total for display
     */
    public function getFormattedGrandTotalAttribute(): string
    {
        return 'PKR ' . number_format($this->grand_total, 2);
    }

    /**
     * Check if this is a credit sale
     */
    public function getIsCreditAttribute(): bool
    {
        return $this->payment_type === 'credit';
    }

    /**
     * Get cashier name
     */
    public function getCashierNameAttribute(): string
    {
        return $this->user->name ?? 'Unknown';
    }

    /**
     * Get total returned amount for this sale
     */
    public function getTotalReturnedAttribute(): float
    {
        return abs($this->returns->sum('grand_total'));
    }

    /**
     * Get remaining returnable amount
     */
    public function getRemainingReturnableAttribute(): float
    {
        return $this->grand_total - $this->total_returned;
    }

    /**
     * Check if this sale has any returns
     */
    public function getHasReturnsAttribute(): bool
    {
        return $this->returns->count() > 0;
    }
}
