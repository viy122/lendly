<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Notifications\TalaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RentalAgreement
{
    public static function termsFor(RentalRequest $request): array
    {
        return [
            'item_name' => $request->listing->name,
            'rental_rules' => $request->listing->rental_rules,
            'start_date' => $request->start_date->toDateString(),
            'end_date' => $request->end_date->toDateString(),
            'rental_days' => $request->rental_days,
            'fulfillment_method' => $request->fulfillment_method->value,
            'rental_fee' => $request->rental_fee,
            'commission_rate' => $request->commission_rate,
            'commission_amount' => $request->commission_amount,
            'security_deposit' => $request->security_deposit,
            'total_amount' => $request->total_amount,
            'cancellation_window_hours' => CancellationPolicy::CHARGEABLE_WINDOW_HOURS,
            'cancellation_fee_percentage' => CancellationPolicy::FEE_PERCENTAGE * 100,
        ];
    }

    public static function accept(RentalRequest $request, User $user): ?Rental
    {
        return DB::transaction(function () use ($request, $user) {
            // Removed listings still belong to already approved agreements/history.
            Listing::withTrashed()->whereKey($request->listing_id)->lockForUpdate()->firstOrFail();
            $request = RentalRequest::with(['listing.owner', 'renter', 'rental'])
                ->lockForUpdate()->findOrFail($request->id);

            Gate::forUser($user)->authorize('acceptAgreement', $request);
            abort_unless($request->isApproved(), 403, 'Only approved requests can accept the rental agreement.');

            // Retrying acceptance must never create another booking or notice.
            if ($request->rental) {
                return $request->rental;
            }

            if ($request->agreement_terms === null) {
                throw ValidationException::withMessages([
                    'agreement' => 'This request has no saved rental agreement. The renter must cancel this request and submit a new one for review.',
                ]);
            }

            $field = $user->id === $request->renter_id
                ? 'renter_terms_accepted_at'
                : 'owner_terms_accepted_at';

            if ($request->$field === null) {
                $request->update([$field => now()]);
            }

            // Check both persisted acceptances inside the same transaction as
            // booking creation; a checkbox alone cannot finalize a booking.
            $request->refresh();
            if ($request->renter_terms_accepted_at === null || $request->owner_terms_accepted_at === null) {
                return null;
            }

            $rental = Rental::create([
                'rental_request_id' => $request->id,
                'listing_id' => $request->listing_id,
                'owner_id' => $request->listing->owner_id,
                'renter_id' => $request->renter_id,
                ...$request->only([
                    'start_date', 'end_date', 'rental_days', 'fulfillment_method',
                    'rental_fee', 'commission_rate', 'commission_amount',
                    'security_deposit', 'total_amount',
                ]),
            ]);

            // Booking confirmation is distinct from request approval. Keep both
            // database notices in this transaction with the finalized booking.
            $request->renter->notify(new TalaNotification(
                NotificationType::BookingConfirmed->value,
                'Booking confirmed',
                "Both parties accepted the rental agreement for \"{$request->agreement_terms['item_name']}\". Your booking is confirmed. Please complete payment.",
                route('renter.rentals.show', $rental),
            ));
            $request->listing->owner->notify(new TalaNotification(
                NotificationType::BookingConfirmed->value,
                'Booking confirmed',
                "Both parties accepted the rental agreement for \"{$request->agreement_terms['item_name']}\". The booking is confirmed and awaiting payment.",
                route('owner.rentals.show', $rental),
            ));

            return $rental;
        });
    }
}
