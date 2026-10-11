<?php

namespace App\Models;

use App\Enums\ListingAvailabilityStatus;
use App\Enums\ListingCondition;
use App\Enums\ListingStatus;
use App\Enums\RentalRequestStatus;
use App\Enums\RentalStatus;
use App\Enums\ReviewType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use SoftDeletes;

    /**
     * Default attribute values, declared explicitly (in addition to the DB
     * column defaults) so a freshly created in-memory model is never left
     * with a missing status/is_available/views_count attribute.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending_approval',
        'is_available' => true,
        'pickup_available' => true,
        'delivery_available' => false,
        'security_deposit' => 0,
        'views_count' => 0,
    ];

    protected $fillable = [
        'owner_id',
        'category_id',
        'subcategory_id',
        'name',
        'brand',
        'model',
        'description',
        'condition',
        'purchase_year',
        'estimated_original_price',
        'price_per_day',
        'price_per_hour',
        'price_per_week',
        'security_deposit',
        'location',
        'latitude',
        'longitude',
        'pickup_available',
        'delivery_available',
        'rental_rules',
        'max_rental_duration_days',
        'is_available',
        'available_from',
        'available_until',
        'status',
        'rejection_reason',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'condition' => ListingCondition::class,
            'status' => ListingStatus::class,
            'is_available' => 'boolean',
            'available_from' => 'date',
            'available_until' => 'date',
            'pickup_available' => 'boolean',
            'delivery_available' => 'boolean',
            'purchase_year' => 'integer',
            'max_rental_duration_days' => 'integer',
            'views_count' => 'integer',
            'estimated_original_price' => 'decimal:2',
            'price_per_day' => 'decimal:2',
            'price_per_hour' => 'decimal:2',
            'price_per_week' => 'decimal:2',
            'security_deposit' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id')->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    public function rentalRequests(): HasMany
    {
        return $this->hasMany(RentalRequest::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function occupyingRentals(): HasMany
    {
        return $this->rentals()->where(fn ($query) => $query
            ->whereIn('status', [RentalStatus::Active, RentalStatus::Overdue])
            ->orWhere(fn ($query) => $query->where('status', RentalStatus::Paid)
                ->where(fn ($query) => $query->whereNotNull('pickup_confirmed_by_owner_at')
                    ->orWhereNotNull('pickup_confirmed_by_renter_at'))));
    }

    public function reservingRequests(): HasMany
    {
        return $this->rentalRequests()
            ->where('status', RentalRequestStatus::Approved)
            ->where('end_date', '>=', now()->toDateString())
            ->whereDoesntHave('rental', fn ($query) => $query->whereIn('status', [
                RentalStatus::Returned, RentalStatus::Completed, RentalStatus::Cancelled,
            ]));
    }

    /** Paid reservations use the agreed booking dates, never the payment date. */
    public function paidReservations(): HasMany
    {
        return $this->rentals()
            ->whereIn('status', [RentalStatus::Paid, RentalStatus::Active, RentalStatus::Overdue])
            ->whereNotNull('paid_at')
            ->whereHas('payment', fn ($query) => $query->where('status', 'paid')->whereNotNull('paid_at'))
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->orderBy('id');
    }

    /**
     * Booking availability comes from the current records, so a cancellation
     * releases only its own reservation. is_available remains the owner's
     * choice to accept requests; publication is a separate moderation state.
     */
    public function availabilityStatus(): ListingAvailabilityStatus
    {
        if ($this->trashed() || ! $this->isPublished()) {
            return ListingAvailabilityStatus::Unavailable;
        }

        // FR-42: retain Reserved throughout the paid booking's agreed dates.
        // An unreturned item remains Rented after that reservation expires.
        $reserved = $this->has_paid_reservation ?? $this->paidReservations()->exists();
        if ($reserved) {
            return ListingAvailabilityStatus::Reserved;
        }

        $occupied = $this->has_ongoing_rental ?? $this->occupyingRentals()->exists();
        if ($occupied) {
            return ListingAvailabilityStatus::Rented;
        }

        if (! $this->is_available || ! $this->hasRequestableDates()) {
            return ListingAvailabilityStatus::Unavailable;
        }

        $held = $this->has_held_dates ?? $this->reservingRequests()->exists();

        return $held ? ListingAvailabilityStatus::OnHold : ListingAvailabilityStatus::Available;
    }

    public function scopeWithAvailability(Builder $query): Builder
    {
        return $query->withExists([
            'occupyingRentals as has_ongoing_rental',
            'paidReservations as has_paid_reservation',
            'reservingRequests as has_held_dates',
        ]);
    }

    public function averageRating(): ?float
    {
        return Review::whereHas('rental', fn ($query) => $query->where('listing_id', $this->id))
            ->where('type', ReviewType::RenterToListing)
            ->avg('rating');
    }

    public function reviewCount(): int
    {
        return Review::whereHas('rental', fn ($query) => $query->where('listing_id', $this->id))
            ->where('type', ReviewType::RenterToListing)
            ->count();
    }

    /**
     * Whether the given date range overlaps an already-approved rental for
     * this listing. Only approved bookings block new requests — multiple
     * renters may have overlapping pending requests, and the owner's
     * approval is what actually confirms the dates (see business-rule
     * discussion in Phase 4 rental request handling).
     */
    public function hasApprovedOverlap($startDate, $endDate, ?int $excludingRequestId = null): bool
    {
        return $this->reservingRequests()
            ->overlapping($this->id, $startDate, $endDate)
            ->when($excludingRequestId, fn ($query) => $query->whereKeyNot($excludingRequestId))
            ->exists()
            || $this->rentals()
                ->whereIn('status', [RentalStatus::PaymentPending, RentalStatus::Paid, RentalStatus::Active, RentalStatus::Overdue])
                ->whereDate('start_date', '<=', $endDate)
                ->whereDate('end_date', '>=', $startDate)
                ->when($excludingRequestId, fn ($query) => $query->where('rental_request_id', '!=', $excludingRequestId))
                ->exists();
    }

    public function availabilityLabel($date = null): string
    {
        if ($this->trashed() || ! $this->isPublished() || ! $this->is_available) {
            return 'Unavailable';
        }

        $date = Carbon::parse($date ?? today())->startOfDay();
        $rentals = $this->relationLoaded('rentals') ? $this->rentals : $this->rentals()->whereIn('status', [RentalStatus::Paid, RentalStatus::Active, RentalStatus::Overdue])->get();
        $covering = $rentals->filter(fn (Rental $rental) => $rental->start_date->lte($date)
            && ($rental->end_date->gte($date) || ($date->lte(today()) && in_array($rental->status, [RentalStatus::Active, RentalStatus::Overdue], true))));

        if ($covering->contains(fn (Rental $rental) => in_array($rental->status, [RentalStatus::Active, RentalStatus::Overdue], true))) {
            return 'Rented';
        }

        return $covering->contains(fn (Rental $rental) => $rental->isPaid()) ? 'Reserved' : 'Available';
    }

    public function availabilityColor($date = null): string
    {
        return match ($this->availabilityLabel($date)) {
            'Reserved' => 'amber',
            'Rented' => 'blue',
            'Available' => 'green',
            default => 'slate',
        };
    }

    public function isPublished(): bool
    {
        return $this->status === ListingStatus::Published;
    }

    public function includesAvailableDates($startDate, $endDate): bool
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        return $end->greaterThanOrEqualTo($start)
            && (! $this->available_from || $start->greaterThanOrEqualTo($this->available_from))
            && (! $this->available_until || $end->lessThanOrEqualTo($this->available_until));
    }

    public function hasRequestableDates(): bool
    {
        $first = now()->addDay()->startOfDay();
        if ($this->available_from && $this->available_from->greaterThan($first)) {
            $first = $this->available_from;
        }

        return ! $this->available_until || $this->available_until->greaterThanOrEqualTo($first);
    }

    public function isPendingApproval(): bool
    {
        return $this->status === ListingStatus::PendingApproval;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::Published);
    }

    public function scopeAcceptingRequests(Builder $query): Builder
    {
        return $query->where('is_available', true)
            ->where(fn ($query) => $query->whereNull('available_until')
                ->orWhereDate('available_until', '>=', now()->addDay()->toDateString()));
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::PendingApproval);
    }
}
