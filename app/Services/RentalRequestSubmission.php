<?php

namespace App\Services;

use App\Enums\FulfillmentMethod;
use App\Enums\NotificationType;
use App\Enums\RentalRequestStatus;
use App\Models\CommissionSetting;
use App\Models\Listing;
use App\Models\RentalRequest;
use App\Models\User;
use App\Notifications\TalaNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RentalRequestSubmission
{
    public static function termsFor(Listing $listing): array
    {
        return $listing->only(['price_per_day', 'security_deposit', 'rental_rules'])
            // A current read sees rates committed while this transaction waited for participant locks.
            + ['commission_rate' => CommissionSetting::query()->sharedLock()->firstOrCreate([], ['commission_rate' => 10.00])->commission_rate];
    }

    public static function termsMatch(array $expected, array $actual): bool
    {
        ksort($expected);
        ksort($actual);

        return $expected === $actual;
    }

    public static function preview(User $renter, int $listingId, array $details): array
    {
        return DB::transaction(function () use ($renter, $listingId, $details) {
            $snapshot = Listing::withTrashed()->find($listingId);
            if (! $snapshot) {
                throw ValidationException::withMessages(['listing' => 'This item is no longer available for rent. Please choose another item.']);
            }
            $participants = User::whereIn('id', [$renter->id, $snapshot->owner_id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $member = $participants->get($renter->id);
            $owner = $participants->get($snapshot->owner_id);
            abort_unless($participants->count() === 2 && $member && $member->isRenter() && $member->hasVerifiedEmail()
                && $owner && ! $owner->isAdmin() && $participants->every(fn (User $user) => ! $user->isSuspended()), 403);
            $listing = Listing::withTrashed()->whereKey($listingId)->lockForUpdate()->first();
            abort_unless($listing && $listing->owner_id === $owner->id, 403);
            if ($listing->trashed() || ! $listing->isPublished() || ! $listing->is_available || ! $listing->hasRequestableDates()) {
                throw ValidationException::withMessages(['listing' => 'This item is no longer available for rent. Please choose another item.']);
            }
            $data = Validator::make($details, [
                'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:tomorrow'],
                'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
                'fulfillment_method' => ['required', Rule::enum(FulfillmentMethod::class)],
            ])->validate();
            if (! $listing->includesAvailableDates($data['start_date'], $data['end_date'])) {
                throw ValidationException::withMessages([
                    'start_date' => 'Choose rental dates within the owner’s availability window.',
                    'end_date' => 'The entire rental period must fit within the available dates.',
                ]);
            }
            if (($data['fulfillment_method'] === 'pickup' && ! $listing->pickup_available)
                || ($data['fulfillment_method'] === 'delivery' && ! $listing->delivery_available)) {
                throw ValidationException::withMessages(['fulfillment_method' => ucfirst($data['fulfillment_method']).' is not available for this item.']);
            }
            $days = (int) Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
            if ($days > $listing->max_rental_duration_days) {
                throw ValidationException::withMessages(['end_date' => "This item can be rented for at most {$listing->max_rental_duration_days} days."]);
            }
            if ($listing->hasApprovedOverlap($data['start_date'], $data['end_date'])) {
                throw ValidationException::withMessages(['start_date' => 'These dates are no longer available for this item. Please choose different dates.']);
            }
            $terms = self::termsFor($listing);
            $fee = round((float) $terms['price_per_day'] * $days, 2);
            $commission = round($fee * (float) $terms['commission_rate'] / 100, 2);

            return ['listing' => $listing, 'terms' => $terms, 'attributes' => $data + [
                'listing_id' => $listingId, 'renter_id' => $member->id, 'rental_days' => $days,
                'rental_fee' => $fee, 'commission_rate' => (float) $terms['commission_rate'],
                'commission_amount' => $commission, 'security_deposit' => $terms['security_deposit'],
                'total_amount' => round($fee + $commission + (float) $terms['security_deposit'], 2),
                'status' => RentalRequestStatus::Requested,
            ]];
        }, 3);
    }

    public static function submit(User $renter, int $listingId, array $details, array $presentedTerms, bool $accepted): RentalRequest
    {
        return DB::transaction(function () use ($renter, $listingId, $details, $presentedTerms, $accepted) {
            $preview = self::preview($renter, $listingId, $details);
            if (! self::termsMatch($preview['terms'], $presentedTerms)) {
                throw ValidationException::withMessages(['accept_terms' => 'The listing terms or price changed. Review the updated details and accept them again.']);
            }
            Validator::make(['accept_terms' => $accepted], ['accept_terms' => ['accepted']])->validate();
            $request = RentalRequest::create($preview['attributes']);
            $listing = $preview['listing'];
            $listing->owner->notify(new TalaNotification(
                NotificationType::RentalRequestSubmitted->value, 'New rental request',
                $renter->name." wants to rent \"{$listing->name}\". The request is pending your review.",
                route('owner.rental-requests.index'),
            ));
            $request->renter->notify(new TalaNotification(
                NotificationType::RentalRequestSubmitted->value, 'Request pending',
                "Your request for \"{$listing->name}\" is pending the owner's review.", route('renter.rental-requests.index'),
            ));

            return $request;
        }, 3);
    }
}
