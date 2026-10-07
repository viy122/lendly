<?php

namespace App\Models;

use App\Enums\FulfillmentMethod;
use App\Enums\RentalRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RentalRequest extends Model
{
    protected $attributes = [
        'status' => 'requested',
    ];

    protected $fillable = [
        'listing_id',
        'renter_id',
        'start_date',
        'end_date',
        'rental_days',
        'fulfillment_method',
        'rental_fee',
        'commission_rate',
        'commission_amount',
        'security_deposit',
        'total_amount',
        'status',
        'rejection_reason',
        'renter_terms_accepted_at',
        'owner_terms_accepted_at',
        'agreement_terms',
        'cancelled_at',
        'cancellation_reason',
        'cancellation_fee',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'fulfillment_method' => FulfillmentMethod::class,
            'status' => RentalRequestStatus::class,
            'rental_fee' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'security_deposit' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'renter_terms_accepted_at' => 'datetime',
            'owner_terms_accepted_at' => 'datetime',
            'agreement_terms' => 'array',
            'cancelled_at' => 'datetime',
            'cancellation_fee' => 'decimal:2',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class)->withTrashed();
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id')->withTrashed();
    }

    public function rental(): HasOne
    {
        return $this->hasOne(Rental::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function otherPartyFor(User $user): User
    {
        return $user->id === $this->renter_id ? $this->listing->owner : $this->renter;
    }

    public function isPending(): bool
    {
        return $this->status === RentalRequestStatus::Requested;
    }

    public function isApproved(): bool
    {
        return $this->status === RentalRequestStatus::Approved;
    }

    /**
     * A renter may cancel their own request while it's still pending or
     * approved but hasn't started yet. Once the rental period has begun
     * there is nothing left in this phase to "cancel" (Phase 6 handles the
     * in-progress lifecycle).
     */
    public function isCancellableByRenter(): bool
    {
        if (! in_array($this->status, [RentalRequestStatus::Requested, RentalRequestStatus::Approved], true)) {
            return false;
        }

        return $this->start_date->isFuture();
    }

    public function scopeOverlapping(Builder $query, int $listingId, $startDate, $endDate): Builder
    {
        return $query
            ->where('listing_id', $listingId)
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate);
    }
}
