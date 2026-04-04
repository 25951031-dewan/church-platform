<?php

namespace App\Plugins\Marketplace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    protected $table = 'marketplace_offers';

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'responded_at' => 'datetime',
    ];

    // --- Relationships ---

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // --- Scopes ---

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    // --- Actions ---

    public function accept(): void
    {
        $this->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'status' => 'rejected',
            'responded_at' => now(),
        ]);
    }

    public function counter(float $amount): void
    {
        $this->update([
            'status' => 'countered',
            'counter_amount' => $amount,
            'responded_at' => now(),
        ]);
    }
}
