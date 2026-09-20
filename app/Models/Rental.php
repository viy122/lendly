<?php

namespace App\Models;

use App\Enums\ConditionRecordType;
use App\Enums\FulfillmentMethod;
use App\Enums\RentalStatus;
use App\Enums\ReviewType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rental extends Model
{
    protected $attributes = [
        'status' => 'payment_pending',
    ];

    protected $fillable = [
        'rental_request_id',
        'listing_id',
        'owner_id',
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
        'paid_at',
        'pickup_confirmed_by_owner_at',
        'pickup_confirmed_by_renter_at',
        'return_confirmed_by_owner_at',
        'return_confirmed_by_renter_at',
        'completed_at',
        'days_overdue',
        'late_fee',
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
            'status' => RentalStatus::class,
            'rental_fee' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'security_deposit' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'late_fee' => 'decimal:2',
            'cancellation_fee' => 'decimal:2',
            'paid_at' => 'datetime',
            'pickup_confirmed_by_owner_at' => 'datetime',
            'pickup_confirmed_by_renter_at' => 'datetime',
            'return_confirmed_by_owner_at' => 'datetime',
            'return_confirmed_by_renter_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function rentalRequest(): BelongsTo
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function securityDeposit(): HasOne
    {
        return $this->hasOne(SecurityDeposit::class);
    }

    public function conditionRecords(): HasMany
    {
        return $this->hasMany(ConditionRecord::class);
    }

    public function damageReport(): HasOne
    {
        return $this->hasOne(DamageReport::class);
    }

    public function beforeConditionRecord(): HasOne
    {
        return $this->hasOne(ConditionRecord::class)->where('type', ConditionRecordType::Before);
    }

    public function afterConditionRecord(): HasOne
    {
        return $this->hasOne(ConditionRecord::class)->where('type', ConditionRecordType::After);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function reviewFromRenterToOwner(): HasOne
    {
        return $this->hasOne(Review::class)->where('type', ReviewType::RenterToOwner);
    }

    public function reviewFromRenterToListing(): HasOne
    {
        return $this->hasOne(Review::class)->where('type', ReviewType::RenterToListing);
    }

    public function reviewFromOwnerToRenter(): HasOne
    {
        return $this->hasOne(Review::class)->where('type', ReviewType::OwnerToRenter);
    }

    public function isPaymentPending(): bool
    {
        return $this->status === RentalStatus::PaymentPending;
    }

    public function isPaid(): bool
    {
        return $this->status === RentalStatus::Paid;
    }

    public function isActive(): bool
    {
        return $this->status === RentalStatus::Active;
    }

    public function isOverdue(): bool
    {
        return $this->status === RentalStatus::Overdue;
    }

    public function isReturned(): bool
    {
        return $this->status === RentalStatus::Returned;
    }

    public function isCompleted(): bool
    {
        return $this->status === RentalStatus::Completed;
    }

    public function isCancelled(): bool
    {
        return $this->status === RentalStatus::Cancelled;
    }

    /**
     * "Due Soon" is a derived display state, not a stored status — an
     * active rental due back within the next 2 days that hasn't tipped
     * into Overdue yet (see markOverdueRentals(), which is what actually
     * flips a rental to the Overdue status once end_date has passed).
     */
    public function isDueSoon(): bool
    {
        if ($this->status !== RentalStatus::Active) {
            return false;
        }

        $daysUntilDue = now()->startOfDay()->diffInDays($this->end_date->copy()->startOfDay(), false);

        return $daysUntilDue >= 0 && $daysUntilDue <= 2;
    }

    public function displayStatusLabel(): string
    {
        return $this->isDueSoon() ? 'Due Soon' : $this->status->label();
    }

    public function displayStatusColor(): string
    {
        return $this->isDueSoon() ? 'amber' : $this->status->badgeColor();
    }

    /**
     * Cancellable only before hand-over — i.e. before pickup has been
     * confirmed by both parties (the point RentalLifecycle::confirmPickup
     * flips the status to Active). PaymentPending and Paid are the only two
     * statuses that precede that.
     */
    public function isCancellableByRenter(): bool
    {
        return in_array($this->status, [RentalStatus::PaymentPending, RentalStatus::Paid], true);
    }

    public function awaitingPickupConfirmation(): bool
    {
        return in_array($this->status, [RentalStatus::Paid], true);
    }

    public function awaitingReturnConfirmation(): bool
    {
        return in_array($this->status, [RentalStatus::Active, RentalStatus::Overdue], true);
    }

    public function bothConfirmedPickup(): bool
    {
        return $this->pickup_confirmed_by_owner_at !== null && $this->pickup_confirmed_by_renter_at !== null;
    }

    public function bothConfirmedReturn(): bool
    {
        return $this->return_confirmed_by_owner_at !== null && $this->return_confirmed_by_renter_at !== null;
    }

    /**
     * The originally agreed daily rate, derived from the snapshotted total
     * rather than the listing's current price (which may have changed
     * since this rental was booked).
     */
    public function agreedDailyRate(): float
    {
        return $this->rental_days > 0
            ? round((float) $this->rental_fee / $this->rental_days, 2)
            : 0.0;
    }
}
