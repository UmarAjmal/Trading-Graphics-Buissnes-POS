<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'whatsapp',
        'email',
        'address',
        'contact_person',
        'is_active',
        'opening_balance',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
    ];

    /**
     * Get all purchases for this supplier
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Get all payments for this supplier
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all pending payments (used for old payment tracking)
     */
    public function pendingPayments(): HasMany
    {
        return $this->hasMany(PendingPayment::class);
    }

    /**
     * Calculate current balance
     * Positive = We owe supplier (Credit)
     * Negative = Supplier owes us (Debit/Advance)
     */
    public function getBalanceAttribute()
    {
        $totalPurchases = $this->purchases()->where('status', '!=', 'cancelled')->sum('grand_total');
        
        // Include old payments from PendingPayments table (if used for historic tracking)
        // Note: For suppliers, PendingPayment usually tracks "opening balance due" via purchase, not payments made.
        // Assuming typical usage: Debt - Payments
        
        // New payments (Ledger)
        // Paid = We paid supplier (Reduces Debt)
        // Exclude payments linked to purchases (Cash Purchases) if any existed?
        // Usually purchase creation shouldn't double count.
        // Assuming PaymentController creates payments with purchase_id=null
        $totalNewPaid = $this->payments()
            ->where('type', 'paid')
            ->whereNull('purchase_id')
            ->sum('amount');
            
        // Received = Supplier refunded us (Increases Debt)
        $totalNewReceived = $this->payments()
            ->where('type', 'received')
            ->whereNull('purchase_id')
            ->sum('amount');
        
        // Balance = Purchases - (Paid - Received)
        // Note: We do NOT add opening_balance etc if it's already in Purchases
        
        // If PendingPayments creates a duplicate tracking issue, verify its usage. 
        // Suppliers usually don't use PendingPayment for tracking *paid* amounts.
        // If PendingPayment has 'amount' field populated with historic payments, use it.
        $totalOldPayments = $this->pendingPayments()->sum('amount');
        
        return $totalPurchases - ($totalOldPayments + $totalNewPaid - $totalNewReceived);
    }
}