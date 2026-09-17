<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'whatsapp',
        'address',
        'city',
        'postal_code',
        'customer_type',
        'credit_limit',
        'credit_used',
        'notes',
        'opening_balance',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'credit_used' => 'decimal:2',
    ];

    /**
     * Get all sales for this customer
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get all pending payments for this customer
     */
    public function pendingPayments(): HasMany
    {
        return $this->hasMany(PendingPayment::class);
    }

    /**
     * Get all payments for this customer
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all sale returns for this customer
     */
    public function returns(): HasManyThrough
    {
        return $this->hasManyThrough(SaleReturn::class, Sale::class);
    }

    /**
     * Get all credit payments for this customer
     */
    public function creditPayments(): HasMany
    {
        return $this->hasMany(CustomerCreditPayment::class);
    }

    /**
     * Calculate current balance
     * Positive = Customer owes us (Debit)
     * Negative = We owe customer (Credit/Advance)
     */
    public function getBalanceAttribute()
    {
        // 1. Debt from Pending Payments (Credit Sales)
        $debtFromPendingPayments = (float) $this->pendingPayments()->sum('amount_due');
        
        // 2. Advances (Customer Advance deposits)
        $totalAdvances = (float) $this->advances()->where('amount', '>', 0)->sum('amount');
        
        // 3. New Ledger Payments (Received payments on-account, without sale_id)
        $totalNewReceived = (float) $this->payments()
            ->where('type', 'received')
            ->whereNull('sale_id')
            ->sum('amount');
            
        // 4. Paid Payments (Disbursements paid to customer on-account, without sale_id)
        $totalNewPaid = (float) $this->payments()
             ->where('type', 'paid')
             ->whereNull('sale_id')
             ->sum('amount');
        
        // 5. Store Credit Returns on Paid Sales (Sales where no pending payment exists)
        // When goods from cash/bank sales are returned on credit, they represent store credit for the customer
        $storeCreditsFromReturns = (float) \App\Models\SaleReturn::whereIn('sale_id', $this->sales()->pluck('id'))
            ->where('refund_type', 'credit')
            ->whereDoesntHave('sale.pendingPayment')
            ->sum('grand_total');
        
        // Formula:
        // Balance = (Pending Payments Due) - (Advances) - (New Received) + (New Paid) - (Store Credits From Returns)
        // Positive = Customer Owes Us. Negative = We owe customer.
        return $debtFromPendingPayments - $totalAdvances - $totalNewReceived + $totalNewPaid - $storeCreditsFromReturns;
    }
    /**
     * Get all advance payments for this customer
     */
    public function advances(): HasMany
    {
        return $this->hasMany(CustomerAdvance::class);
    }

    /**
     * Get total pending amount for this customer
     */
    public function getTotalPendingAttribute(): float
    {
        return (float) $this->pendingPayments()
            ->where('settled', false)
            ->sum('amount_due');
    }

    /**
     * Get current credit used (pending payments)
     */
    public function getCreditUsedAttribute(): float
    {
        return (float) $this->pendingPayments()
            ->where('settled', false)
            ->sum('amount_due');
    }

    /**
     * Get available credit remaining
     */
    public function getAvailableCreditAttribute(): float
    {
        return max(0, (float) $this->credit_limit - $this->credit_used);
    }

    /**
     * Check if credit limit is exceeded
     */
    public function isCreditExceeded(): bool
    {
        return $this->credit_used > $this->credit_limit && $this->credit_limit > 0;
    }

    /**
     * Check if credit limit is close to being exceeded (within 10%)
     */
    public function isCreditNearLimit(): bool
    {
        if ($this->credit_limit <= 0) return false;
        
        $threshold = $this->credit_limit * 0.9; // 90% of credit limit
        return $this->credit_used >= $threshold && !$this->isCreditExceeded();
    }

    /**
     * Get credit status: 'safe', 'warning', 'exceeded'
     */
    public function getCreditStatusAttribute(): string
    {
        if ($this->isCreditExceeded()) return 'exceeded';
        if ($this->isCreditNearLimit()) return 'warning';
        return 'safe';
    }

    /**
     * Get total advance balance available for this customer
     */
    public function getAdvanceBalanceAttribute(): float
    {
        return (float) $this->advances()->sum('amount');
    }

    /**
     * Get current advance balance after considering used advances
     * This calculates remaining advance balance after subtracting used amounts
     */
    public function getCurrentAdvanceBalanceAttribute(): float
    {
        // Sum all advances (positive and negative amounts)
        // Positive = advance payments, Negative = advance usage
        $netAdvanceBalance = $this->advances()->sum('amount');
        return (float) max(0, $netAdvanceBalance);
    }

    /**
     * Get display name with contact info
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->name;
        if ($this->phone) {
            $name .= " ({$this->phone})";
        }
        return $name;
    }

    /**
     * Search customers by name or phone
     */
    public static function search($query)
    {
        return static::where('name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('whatsapp', 'like', "%{$query}%")
            ->orderBy('name');
    }
}
