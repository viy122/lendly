<?php

namespace App\Models;

use App\Enums\ReviewType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The model's default attribute values.
     *
     * Set explicitly (in addition to the DB column defaults) because Eloquent
     * does not re-hydrate a freshly created model from the database, so any
     * attribute omitted from create() would otherwise be missing in-memory.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'member',
        'status' => 'active',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'avatar_path',
        'status',
        'suspended_at',
        'suspension_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Owner and Renter are no longer separate account types — every
     * non-admin member can both list items and rent items on the same
     * account, so both methods simply mean "is a regular member" (as
     * opposed to Admin). Kept as two named methods (rather than collapsing
     * every call site to `! isAdmin()`) since `isRenter()`/`isOwner()` still
     * read clearly at each call site about which capability is being used.
     */
    public function isRenter(): bool
    {
        return ! $this->isAdmin();
    }

    public function isOwner(): bool
    {
        return ! $this->isAdmin();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            UserRole::Member => 'dashboard',
            UserRole::Admin => 'admin.dashboard',
        };
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset('storage/'.$this->avatar_path) : null;
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'owner_id');
    }

    public function rentalRequests(): HasMany
    {
        return $this->hasMany(RentalRequest::class, 'renter_id');
    }

    public function rentalsAsRenter(): HasMany
    {
        return $this->hasMany(Rental::class, 'renter_id');
    }

    public function rentalsAsOwner(): HasMany
    {
        return $this->hasMany(Rental::class, 'owner_id');
    }

    public function averageRatingAsOwner(): ?float
    {
        return Review::whereHas('rental', fn ($query) => $query->where('owner_id', $this->id))
            ->where('type', ReviewType::RenterToOwner)
            ->avg('rating');
    }

    public function averageRatingAsRenter(): ?float
    {
        return Review::whereHas('rental', fn ($query) => $query->where('renter_id', $this->id))
            ->where('type', ReviewType::OwnerToRenter)
            ->avg('rating');
    }

    public function messagesSent(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function messagesReceived(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function unreadMessagesCount(): int
    {
        return $this->messagesReceived()->whereNull('read_at')->count();
    }
}
