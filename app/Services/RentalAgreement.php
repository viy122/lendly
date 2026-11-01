<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\RentalRequestStatus;
use App\Models\CommissionSetting;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Notifications\TalaNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RentalAgreement
{
    public static function termsFor(RentalRequest $request): array
    {
        $settings = CommissionSetting::current()->refresh();

        return [
            'payment_review' => 'owner',
            'rental_unit' => $request->rental_unit ?? 'day',
            'rental_hours' => $request->rental_hours,
            'starts_at' => $request->starts_at?->toIso8601String(),
            'ends_at' => $request->ends_at?->toIso8601String(),
            'cancellation_requires_review' => true,
            'cancellation_reference_at' => ($request->starts_at ?? Carbon::parse($request->start_date->toDateString().' '.$request->listing->available_time_from, 'Asia/Manila')->utc())->toIso8601String(),
            'payment_window_hours' => $settings->payment_window_hours,
            'processing_fee_refundable' => true,
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
            'cancellation_window_hours' => $settings->cancellation_window_hours,
            'cancellation_fee_percentage' => (float) $settings->cancellation_fee_percentage,
            // Save the wording so later edits cannot change an accepted agreement.
            'clauses' => [
                [
                    'title' => 'Scope and duration of the rental',
                    'paragraphs' => [
                        'This agreement concerns the temporary rental of the item identified above between the owner and the renter associated with this request. The owner retains ownership of the item. Neither payment of the rental fee nor payment of the security deposit transfers ownership, creates a purchase option, or permits the renter to sell, pledge, or dispose of the item.',
                        'Daily rentals include both dates shown in the particulars. Hourly rentals use the exact start and return times shown in Philippine time, billed in whole hours. The recorded item, dates, fulfillment method, charges, and item-specific rules form part of this agreement. Each party must review these details and raise any error before accepting. Sending a request for agreement holds the dates. The request becomes Approved and the booking is finalized only when both parties explicitly accept this saved agreement.',
                    ],
                ],
                [
                    'title' => 'Owner responsibilities',
                    'paragraphs' => [
                        'The owner confirms that they are entitled to offer the item for rental and must describe its condition, included accessories, and intended use accurately. The owner must disclose known defects, operating limitations, and safety concerns that could affect the rental, and provide reasonable instructions needed for the renter to use and care for the item.',
                        'The owner must make the agreed item and accessories available according to the arrangements confirmed with the renter. If an issue prevents the exchange, the owner must inform the renter promptly and cooperate in resolving it. On return, the owner must inspect the item fairly, record its returned condition, promptly confirm actual receipt, and support any damage claim with a clear explanation and available evidence.',
                    ],
                ],
                [
                    'title' => 'Renter responsibilities',
                    'paragraphs' => [
                        'The renter must review the item description, rental period, charges, and owner rules before accepting this agreement. While the item is in their possession, the renter must exercise reasonable care, keep it appropriately stored and secured, follow the supplied operating instructions, and return the item together with the accessories received by the agreed end date.',
                        'The renter must promptly tell the owner about loss, damage, a fault, an accident, or a problem that prevents safe use or timely return. The renter must not conceal damage, misrepresent the condition of the item, or provide false information about payment or return. The renter must not lend, subrent, transfer, or give another person possession of the item without the owner’s prior written agreement.',
                    ],
                ],
                [
                    'title' => 'Permitted use and safety',
                    'paragraphs' => [
                        'The item may be used only for its intended, lawful purpose and within the operating limits communicated by the owner or manufacturer. The renter must not overload the item, remove safety features, use it recklessly, or use it for an activity prohibited by the item rules. Any person permitted to operate it must understand the instructions and take the precautions appropriate to that activity.',
                        'If the item appears unsafe or develops a fault, the renter must stop using it, take reasonable steps to prevent further harm, and contact the owner to agree the next steps. Repairs, alterations, and replacement of parts require the owner’s agreement. Neither party should continue an unsafe exchange or use merely because a booking has been paid; unresolved safety or description issues may be raised through the dispute process.',
                    ],
                ],
                [
                    'title' => 'Pickup, delivery and hand-over',
                    'paragraphs' => [
                        'The exchange must use the pickup or delivery method recorded for the booking. The parties must coordinate a suitable time, address, and any access instructions. Exchange schedule times are displayed in Philippine time. A proposed schedule is confirmed only when both parties confirm the same proposal; a revised time or address requires fresh confirmations and must remain within the booked rental period.',
                        'Each party must confirm hand-over only after the physical exchange has actually occurred. The booking becomes active when both parties have recorded their pickup or delivery confirmation. Once either party records hand-over, the booking is no longer eligible for the ordinary renter cancellation process. A schedule confirmation records the arrangement and is separate from confirmation that the item was received.',
                    ],
                ],
                [
                    'title' => 'Condition records and supporting evidence',
                    'paragraphs' => [
                        'Before hand-over, the parties should check the item and included accessories together and identify existing defects or missing parts. The owner may create a before-condition record with notes and photographs. Both parties should promptly record any disagreement in the rental conversation and retain relevant evidence rather than rely on memory when a later condition issue arises.',
                        'On return, the owner records the after-condition of the item and may attach photographs and damage details. Condition notes and images must describe the actual item and must not be altered to mislead the other party. A condition report or allegation alone does not constitute the renter’s acceptance of a damage deduction; the renter retains the opportunity to review and respond to a claim.',
                    ],
                ],
                [
                    'title' => 'Payment verification and financial records',
                    'paragraphs' => [
                        'Payment follows both parties accepting this agreement and must cover the rental fee, processing fee charged once, and refundable security deposit shown above. Submit payment proof within the saved payment window or before the rental start reference, whichever is earlier. Unpaid reservations expire without a fee; pending proof pauses expiry during review. The renter must follow the payment instructions published for the booking, provide the correct transaction reference, and submit genuine payment proof. A screenshot, uploaded receipt, or pending submission does not by itself mark the booking as paid.',
                        'The owner must confirm that the full amount reached the saved owner payment account before the booking is marked paid and available for hand-over. Admin approval is not required for this owner-wallet payment. The owner may reject incorrect proof with a reason so the renter can correct it within the payment window. If proof is rejected, the renter should review the stated reason and submit corrected proof where permitted. Payment, refund, deposit, and late-fee records show the recorded handling of the transaction; changing a status does not itself send money, initiate a refund, or confirm that an additional charge has been collected.',
                    ],
                ],
                [
                    'title' => 'Cancellation procedure and settlement',
                    'paragraphs' => [
                        'The cancellation window and percentage stated above are saved for this booking. The reference time is the hourly start time, or the owner’s available-from time on the first daily rental date, in Philippine time. A pickup or delivery schedule confirmed by both parties replaces that reference. An unpacked booking may be cancelled freely before the chargeable window. Within the window or after packing, a reason and owner or administrator approval are required. A pending request keeps the dates reserved and pauses packing, courier updates and handover. The percentage applies once to the rental fee only. Pending rental requests and expired unpaid reservations have no cancellation fee.',
                        'After a booking is created, the renter must use its cancellation action while available. Ordinary cancellation closes at Waiting for courier, Shipped, or either party’s handover confirmation. A message saying that the renter will not attend does not itself cancel the recorded booking. An approved cancellation releases booked dates and records any applicable rental-only cancellation charge. For a paid booking, the manual refund equals the total paid, including processing fee and deposit, less that cancellation charge. Any actual return of funds must be arranged through the payment collection process; this agreement does not promise an automatic transfer or a fixed refund processing time.',
                    ],
                ],
                [
                    'title' => 'Return, overdue charges and changes to dates',
                    'paragraphs' => [
                        'The renter must coordinate return before the agreed rental period ends and must not assume that silence, a chat message, or a changed listing extends the booking. If a delay becomes likely, the renter must inform the owner promptly and arrange the return. Any proposed change to the rental dates or charges must be resolved with the other party; the recorded booking remains the reference until an appropriate updated booking is recorded.',
                        $request->rental_unit === 'hour'
                            ? 'For an active hourly rental returned after its agreed return time, the late fee uses the agreed hourly rental rate for each started overdue hour. The agreed hourly rate is the saved rental fee divided by the saved rental hours. Philippine time is displayed above; timestamps are stored in UTC. Listing price changes do not change this rate. Overdue charges stop accruing when the owner records the item as returned. Inspection and settlement remain separate from that return confirmation.'
                            : 'For an active rental that continues beyond its end date, the late fee is the agreed daily rental rate multiplied by the number of calendar days overdue. Overdue days are counted using UTC calendar dates; each new UTC date begins at 8:00 AM Philippine time. The daily rate is the saved rental fee divided by the saved number of rental days, rounded to two decimal places; a later change to the listing price does not change that rate. Overdue charges stop accruing when the owner records the item as returned. The owner’s return confirmation and after-condition record allow inspection completion, separately from settlement of any outstanding claim or charge.',
                    ],
                ],
                [
                    'title' => 'Damage claims and security deposit settlement',
                    'paragraphs' => [
                        'The security deposit is recorded separately from the rental and processing fees and remains subject to return inspection and any unresolved damage claim. A proposed deduction must identify the reported damage and estimated repair cost. The platform’s damage deduction process limits a proposed deposit deduction to the deposit amount; that workflow limit does not determine the full extent of either party’s rights or liability under applicable law.',
                        'The renter may accept the claim or dispute it with an explanation. An accepted claim or administrative decision may record a deduction, while rejection of a damage claim makes the deposit eligible for return under the applicable workflow. A completed rental or a return-eligible deposit status does not itself confirm that funds have been received by the renter. The parties must coordinate the actual deposit settlement and retain payment evidence; no fixed inspection or settlement deadline is represented by this agreement.',
                    ],
                ],
                [
                    'title' => 'Disputes and administrative review',
                    'paragraphs' => [
                        'Either party may raise a rental dispute about damage, an item not matching its description, failure to provide or return the item, an incorrect charge, or a deposit disagreement. The party raising the issue must give an accurate explanation and any relevant records. Both parties should communicate respectfully, respond to reasonable requests for information, and avoid unsupported allegations or misleading evidence.',
                        'An administrator may review the recorded agreement, conversation, condition reports, and other relevant transaction evidence and record a resolution with notes. Administrative decisions and financial statuses do not automatically execute a payment or refund. This review concerns the platform’s rental and deposit records and does not prevent either party from seeking remedies available under applicable law. Nothing in these terms excludes rights or responsibilities that the law does not permit the parties to exclude.',
                    ],
                ],
                [
                    'title' => 'Communication, personal information and saved terms',
                    'paragraphs' => [
                        'The parties should use the rental conversation to record material arrangements, problems, and proposed changes. Contact details and exchange addresses received for the rental must be used only as needed to coordinate it or address a related issue, and must not be published or used for unrelated contact. Upload only relevant evidence and remove unrelated personal or financial details. Never share account passwords, payment passwords, or one-time security codes with the other party.',
                        'After completion, reviews are optional and are displayed publicly on the relevant profile or listing. Reviews should describe the rental honestly and must not disclose private contact or payment information. The detailed terms saved with this request are the terms presented for acceptance; later edits to a listing or the platform’s agreement template do not rewrite that saved wording. Each party must review the full document and explicitly accept it from their own account. The recorded acceptance dates below show the acceptance state of this request.',
                    ],
                ],
            ],
        ];
    }

    public static function accept(RentalRequest $request, User $user): ?Rental
    {
        return DB::transaction(function () use ($request, $user) {
            $snapshot = RentalRequest::with('listing')->findOrFail($request->id);
            $participants = User::withTrashed()->whereIn('id', [$snapshot->listing->owner_id, $snapshot->renter_id])
                ->orderBy('id')->lockForUpdate()->get();
            abort_unless($participants->count() === 2 && $participants->every(fn (User $participant) => ! $participant->trashed() && ! $participant->isSuspended() && ! $participant->isAdmin()), 403, 'Both participants must have active member accounts.');
            // Removed listings still belong to already approved agreements/history.
            Listing::withTrashed()->whereKey($snapshot->listing_id)->lockForUpdate()->firstOrFail();
            $request = RentalRequest::with(['listing.owner', 'renter', 'rental'])
                ->lockForUpdate()->findOrFail($request->id);

            Gate::forUser($user)->authorize('acceptAgreement', $request);
            abort_unless($request->canReviewAgreement(), 403, 'Only requests awaiting agreement or already approved can accept the rental agreement.');

            // Retrying acceptance must never create another booking or notice.
            if ($request->rental) {
                return $request->rental;
            }
            if (! $participants->every(fn (User $participant) => $participant->hasVerifiedIdentity())) {
                throw ValidationException::withMessages(['identity' => 'Both members must complete their ID review before starting a new booking.']);
            }

            if ($request->agreement_terms === null) {
                throw ValidationException::withMessages([
                    'agreement' => 'This request has no saved rental agreement. The renter must cancel this request and submit a new one for review.',
                ]);
            }

            if ($request->listing->reopen_required || $request->listing->hasPhysicalBlocker() || $request->start_date->toDateString() < now('Asia/Manila')->toDateString() || $request->starts_at?->lte(now()) || $request->listing->hasApprovedOverlap($request->start_date, $request->end_date, $request->id, $request->starts_at, $request->ends_at)) {
                throw ValidationException::withMessages(['agreement' => 'These dates are no longer available. Cancel this request and submit new dates.']);
            }

            $field = $user->id === $request->renter_id
                ? 'renter_terms_accepted_at'
                : 'owner_terms_accepted_at';

            // Normalize legacy unfinalized requests without changing their saved terms.
            if ($request->isApproved()) {
                $request->update(['status' => RentalRequestStatus::AwaitingAgreement]);
            }

            if ($request->$field === null) {
                $request->update([$field => now()]);
            }

            // Check both persisted acceptances inside the same transaction as
            // booking creation; a checkbox alone cannot finalize a booking.
            $request->refresh();
            if ($request->renter_terms_accepted_at === null || $request->owner_terms_accepted_at === null) {
                return null;
            }

            $request->update(['status' => RentalRequestStatus::Approved]);

            $rental = Rental::create([
                'rental_request_id' => $request->id,
                'payment_due_at' => isset($request->agreement_terms['payment_window_hours'])
                    ? now()->addHours($request->agreement_terms['payment_window_hours'])->min(Carbon::parse($request->agreement_terms['cancellation_reference_at'])) : null,
                'listing_id' => $request->listing_id,
                'owner_id' => $request->listing->owner_id,
                'renter_id' => $request->renter_id,
                ...$request->only([
                    'start_date', 'end_date', 'rental_days', 'rental_unit', 'rental_hours', 'starts_at', 'ends_at', 'fulfillment_method',
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
        }, 3);
    }
}
