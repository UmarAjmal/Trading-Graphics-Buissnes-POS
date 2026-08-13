<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingPayment extends Model
{
    protected $fillable = [
        'sale_id',
        'customer_id',
        'supplier_id',
        'purchase_id',
        'due_date',
        'amount_due',
        'amount',
        'settled',
        'is_prepayment',
        'payment_method',
        'note',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount_due' => 'decimal:2',
        'amount' => 'decimal:2',
        'settled' => 'boolean',
        'is_prepayment' => 'boolean',
    ];

    /**
     * Get the sale this payment belongs to
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the customer this payment belongs to
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the supplier this payment belongs to
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the purchase this payment belongs to
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Mark payment as settled
     */
    public function markAsSettled(): void
    {
        $this->update(['settled' => true]);
    }

    /**
     * Get formatted amount due
     */
    public function getFormattedAmountDueAttribute(): string
    {
        return 'PKR ' . number_format($this->amount_due, 2);
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'PKR ' . number_format($this->amount, 2);
    }

    /**
     * Check if payment is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->settled;
    }

    /**
     * Get days until/since due date
     */
    public function getDaysAttribute(): int
    {
        if (!$this->due_date) {
            return 0;
        }
        
        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Scope for unsettled payments
     */
    public function scopeUnsettled($query)
    {
        return $query->where('settled', false);
    }

    /**
     * Scope for overdue payments
     */
    public function scopeOverdue($query)
    {
        return $query->where('settled', false)
            ->where('due_date', '<', now());
    }

    /**
     * Scope for prepayments
     */
    public function scopePrepayments($query)
    {
        return $query->where('is_prepayment', true);
    }

    /**
     * Scope for customer payments
     */
    public function scopeCustomerPayments($query)
    {
        return $query->whereNotNull('customer_id');
    }

    /**
     * Scope for supplier payments
     */
    public function scopeSupplierPayments($query)
    {
        return $query->whereNotNull('supplier_id');
    }
}
