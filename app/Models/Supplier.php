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
     * Get all purchase returns for this supplier
     */
    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /**
     * Calculate current balance
     * Positive = We owe supplier (Credit)
     * Negative = Supplier owes us (Debit/Advance)
     */
    public function getBalanceAttribute()
    {
        $totalPurchases = (float) $this->purchases()->where('status', '!=', 'cancelled')->sum('grand_total');
        $totalReturns = (float) $this->returns()->sum('grand_total');
        
        // New payments (Ledger)
        $totalNewPaid = (float) $this->payments()
            ->where('type', 'paid')
            ->sum('amount');
            
        $totalNewReceived = (float) $this->payments()
            ->where('type', 'received')
            ->sum('amount');
        
        $totalOldPayments = (float) $this->pendingPayments()->where('amount', '>', 0)->sum('amount');
        
        return ($totalPurchases - $totalReturns) - ($totalOldPayments + $totalNewPaid - $totalNewReceived);
    }
}