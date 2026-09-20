<?php

namespace App\Models;

use App\Enums\ListingCondition;
use App\Enums\ListingStatus;
use App\Enums\RentalRequestStatus;
use App\Enums\ReviewType;
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
        return $this->belongsTo(User::class, 'owner_id');
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
        return $this->rentalRequests()
            ->overlapping($this->id, $startDate, $endDate)
            ->where('status', RentalRequestStatus::Approved)
            ->when($excludingRequestId, fn ($query) => $query->whereKeyNot($excludingRequestId))
            ->exists();
    }

    public function isPublished(): bool
    {
        return $this->status === ListingStatus::Published;
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

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::PendingApproval);
    }
}
