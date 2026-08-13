<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegisterSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'cash_difference',
        'opening_notes',
        'closing_notes',
        'opened_at',
        'closed_at',
        'status'
    ];

    protected $casts = [
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the register session
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sales for this register session
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'register_session_id');
    }

    /**
     * Get the expenses for this register session
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Check if the register is currently open
     */
    public function isOpen()
    {
        return $this->status === 'open' && is_null($this->closed_at);
    }

    /**
     * Get the active register session for a user
     */
    public static function getActiveSession($userId)
    {
        return static::where('user_id', $userId)
            ->where('status', 'open')
            ->whereNull('closed_at')
            ->latest()
            ->first();
    }
}
