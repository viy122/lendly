<?php

namespace App\Models;

use App\Enums\FulfillmentMethod;
use App\Enums\NotificationType;
use App\Enums\RentalRequestStatus;
use App\Notifications\TalaNotification;
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

    /** A request without a booking has no handover to block cancellation. */
    public function isCancellableByRenter(): bool
    {
        if ($this->rental) {
            return $this->isApproved() && $this->rental->isCancellableByRenter();
        }

        return in_array($this->status, [RentalRequestStatus::Requested, RentalRequestStatus::Approved], true);
    }

    public function notifyStatus(?Rental $rental = null): void
    {
        [$type, $title, $message] = match ($this->status) {
            RentalRequestStatus::Requested => [NotificationType::RentalRequestSubmitted, 'Rental request pending', 'The rental request is pending owner approval.'],
            RentalRequestStatus::Approved => [NotificationType::RequestApproved, 'Booking confirmed', 'Both parties accepted the terms. The booking is confirmed and awaiting payment.'],
            RentalRequestStatus::Rejected => [NotificationType::RequestRejected, 'Request declined', $this->rejection_reason ?: 'The owner declined the request.'],
            RentalRequestStatus::Cancelled => [NotificationType::RentalCancelled, 'Rental request cancelled', 'The renter cancelled the request.'],
        };

        foreach ([$this->listing->owner, $this->renter] as $participant) {
            if ($participant->trashed()) {
                continue;
            }
            $owner = $participant->id === $this->listing->owner_id;
            $url = $rental
                ? route($owner ? 'owner.rentals.show' : 'renter.rentals.show', $rental)
                : route($owner ? 'owner.rental-requests.index' : 'renter.rental-requests.index');
            $participant->notify(new TalaNotification($type->value, $title, "\"{$this->listing->name}\": {$message}", $url));
        }
    }

    public function scopeOverlapping(Builder $query, int $listingId, $startDate, $endDate): Builder
    {
        return $query
            ->where('listing_id', $listingId)
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate);
    }
}
