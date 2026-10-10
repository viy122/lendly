<?php

namespace App\Models;

use App\Enums\DamageReportStatus;
use App\Enums\DisputeStatus;
use App\Enums\ListingStatus;
use App\Enums\RentalRequestStatus;
use App\Enums\RentalStatus;
use App\Enums\ReviewType;
use App\Enums\SecurityDepositStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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

    public function closeAccount(): void
    {
        $avatarPath = DB::transaction(function () {
            $this->newQuery()->whereKey($this->id)->lockForUpdate()->firstOrFail();
            $this->refresh();

            $hasOutstandingRental = Rental::query()
                ->where(fn ($query) => $query->where('owner_id', $this->id)->orWhere('renter_id', $this->id))
                ->where(fn ($query) => $query
                    ->whereNotIn('status', [RentalStatus::Completed, RentalStatus::Cancelled])
                    ->orWhereHas('disputes', fn ($dispute) => $dispute->where('status', '!=', DisputeStatus::Resolved))
                    ->orWhereHas('securityDeposit', fn ($deposit) => $deposit->whereNotIn('status', [SecurityDepositStatus::Released, SecurityDepositStatus::Refunded, SecurityDepositStatus::Deducted]))
                    ->orWhereHas('damageReport', fn ($damage) => $damage->whereNotIn('status', [DamageReportStatus::Accepted, DamageReportStatus::Rejected])))
                ->exists();
            $hasOutstandingRequest = RentalRequest::query()
                ->where(fn ($query) => $query->where('renter_id', $this->id)
                    ->orWhereHas('listing', fn ($listing) => $listing->withTrashed()->where('owner_id', $this->id)))
                ->where(fn ($query) => $query->where('status', RentalRequestStatus::Requested)
                    ->orWhere(fn ($approved) => $approved->where('status', RentalRequestStatus::Approved)->whereDoesntHave('rental')))
                ->exists();

            if ($hasOutstandingRental || $hasOutstandingRequest) {
                throw ValidationException::withMessages([
                    'account' => 'Settle your rental requests, rentals, deposits, damage claims, and disputes before deleting your account.',
                ]);
            }

            $avatarPath = $this->avatar_path;
            DB::table(config('auth.passwords.users.table'))->where('email', $this->email)->delete();
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table'))
                    ->where('user_id', $this->id)->delete();
            }
            $this->listings()->update(['status' => ListingStatus::Inactive, 'is_available' => false]);
            $this->listings()->delete();
            $this->forceFill([
                'name' => 'Deleted user',
                'email' => 'deleted-'.$this->id.'-'.Str::uuid().'@deleted.invalid',
                'email_verified_at' => null,
                'phone' => null,
                'address' => null,
                'avatar_path' => null,
                'password' => Str::random(64),
                'remember_token' => null,
                'suspension_reason' => null,
            ])->save();
            $this->delete();

            return $avatarPath;
        });

        if ($avatarPath) {
            Storage::disk('public')->delete($avatarPath);
        }
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
