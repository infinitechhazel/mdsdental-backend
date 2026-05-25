<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Reservation extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'date',
        'time',
        'guests',
        'package',
        'occasion',
        'dining_preference',
        'special_requests',

        'reservation_fee',
        'service_charge',
        'total_fee',
        'remaining_balance',
        'down_payment',

        'payment_method',
        'payment_reference',
        'payment_receipt',
        'payment_status',

        'reservation_number',
        'reservation_status',
        'is_walkin',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'string',
        'guests' => 'integer',
        'is_walkin' => 'boolean',

        'reservation_fee' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'total_fee' => 'decimal:2',

        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor: full URL for payment receipt
     */
    public function getPaymentReceiptUrlAttribute(): ?string
    {
        return $this->payment_receipt
            ? url($this->payment_receipt)
            : null;
    }

    /**
     * Check if payment proof exists (safe, DB-based only)
     */
    public function hasPaymentProof(): bool
    {
        return !empty($this->payment_receipt);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString())
            ->whereIn('reservation_status', ['pending', 'confirmed'])
            ->orderBy('date')
            ->orderBy('time');
    }

    public function scopePast($query)
    {
        return $query->where(function ($q) {
            $q->where('date', '<', now()->toDateString())
                ->orWhereIn('reservation_status', ['completed', 'cancelled', 'noshow']);
        })->orderByDesc('date')
            ->orderByDesc('time');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'pending')
            ->where('is_walkin', false);
    }

    public function scopeWithPaymentProof($query)
    {
        return $query->whereNotNull('payment_receipt');
    }

    public function scopeWithoutPaymentProof($query)
    {
        return $query->whereNull('payment_receipt')
            ->where('is_walkin', false);
    }

    public function calculateServiceCharge(): float
    {
        return (float) $this->reservation_fee * 0.10;
    }

    public function calculateRemainingBalance(): float
    {
        return max(((float) $this->reservation_fee - (float) $this->down_payment), 0);
    }

    public function calculateTotalFee(): float
    {
        return $this->calculateRemainingBalance() + $this->calculateServiceCharge();
    }
}
