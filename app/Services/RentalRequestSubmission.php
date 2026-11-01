<?php

namespace App\Services;

use App\Enums\FulfillmentMethod;
use App\Enums\NotificationType;
use App\Enums\RentalRequestStatus;
use App\Models\CommissionSetting;
use App\Models\Listing;
use App\Models\RentalRequest;
use App\Models\RentalWaitlistEntry;
use App\Models\User;
use App\Notifications\TalaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RentalRequestSubmission
{
    public static function termsFor(Listing $listing): array
    {
        return $listing->only(['price_per_day', 'price_per_hour', 'allows_daily_rentals', 'max_rental_duration_days', 'available_time_from', 'available_time_until', 'security_deposit', 'rental_rules'])
            // A current read sees rates committed while this transaction waited for participant locks.
            + ['commission_rate' => CommissionSetting::query()->sharedLock()->firstOrCreate([], ['commission_rate' => 10.00])->commission_rate];
    }

    public static function termsMatch(array $expected, array $actual): bool
    {
        ksort($expected);
        ksort($actual);

        return $expected === $actual;
    }

    public static function preview(User $renter, int $listingId, array $details, bool $forRequest = true, bool $allowOverlap = false): array
    {
        return DB::transaction(function () use ($renter, $listingId, $details, $forRequest, $allowOverlap) {
            $snapshot = Listing::withTrashed()->find($listingId);
            if (! $snapshot) {
                throw ValidationException::withMessages(['listing' => 'This item is no longer available for rent. Please choose another item.']);
            }
            $participants = User::whereIn('id', [$renter->id, $snapshot->owner_id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $member = $participants->get($renter->id);
            $owner = $participants->get($snapshot->owner_id);
            abort_unless($participants->count() === 2 && $member && $member->isRenter() && $member->hasVerifiedEmail()
                && $owner && ! $owner->isAdmin() && $participants->every(fn (User $user) => ! $user->isSuspended()), 403);
            if ($forRequest && ! $member->hasVerifiedIdentity()) {
                throw ValidationException::withMessages(['identity' => 'Complete your ID review before sending a rental request.']);
            }
            if ($forRequest && ! $owner->hasVerifiedIdentity()) {
                throw ValidationException::withMessages(['listing' => 'This owner must complete their ID review before accepting new rentals.']);
            }
            $listing = Listing::withTrashed()->whereKey($listingId)->lockForUpdate()->first();
            abort_unless($listing && $listing->owner_id === $owner->id, 403);
            if (! $listing->acceptsNewRequests()) {
                throw ValidationException::withMessages(['listing' => 'This item is no longer available for rent. Please choose another item.']);
            }
            $period = RentalPeriod::attributes($listing, $details);
            $data = Validator::make($details, ['fulfillment_method' => ['required', Rule::enum(FulfillmentMethod::class)]])->validate();
            if (($data['fulfillment_method'] === 'pickup' && ! $listing->pickup_available)
                || ($data['fulfillment_method'] === 'delivery' && ! $listing->delivery_available)) {
                throw ValidationException::withMessages(['fulfillment_method' => ucfirst($data['fulfillment_method']).' is not available for this item.']);
            }
            if (! $allowOverlap && $listing->hasApprovedOverlap($period['start_date'], $period['end_date'], startsAt: $period['starts_at'], endsAt: $period['ends_at'])) {
                throw ValidationException::withMessages(['start_date' => 'These dates are no longer available for this item. Join the waitlist or choose a different period.']);
            }
            $terms = self::termsFor($listing);
            $fee = $period['rental_unit'] === 'hour'
                ? round((float) $terms['price_per_hour'] * $period['rental_hours'], 2)
                : round((float) $terms['price_per_day'] * $period['rental_days'], 2);
            $commission = round($fee * (float) $terms['commission_rate'] / 100, 2);
            if ($fee + $commission + (float) $terms['security_deposit'] > 99999999.99) {
                throw ValidationException::withMessages(['end_date' => 'The booking total exceeds the supported amount. Choose a shorter period or contact the owner.']);
            }

            return ['listing' => $listing, 'terms' => $terms, 'attributes' => $data + $period + [
                'listing_id' => $listingId, 'renter_id' => $member->id,
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
            $entry = RentalWaitlistEntry::where('listing_id', $listingId)->where('user_id', $renter->id)->lockForUpdate()->first();
            if ($entry && self::termsMatch($entry->details, RentalPeriod::details($request) + ['rental_unit' => 'day'])) {
                $entry->delete();
            }
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
