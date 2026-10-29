<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentalCart
{
    public static function save(User $renter, int $listingId, array $details): CartItem
    {
        $renter = self::member($renter);

        return DB::transaction(function () use ($renter, $listingId, $details) {
            $quote = RentalRequestSubmission::preview($renter, $listingId, $details, forRequest: false, allowOverlap: true);
            $item = CartItem::where('user_id', $renter->id)->where('listing_id', $listingId)->lockForUpdate()->first();
            if (! $item && CartItem::where('user_id', $renter->id)->lockForUpdate()->get()->count() >= 50) {
                throw ValidationException::withMessages(['cart' => 'Your cart can hold up to 50 items. Remove an item before adding another.']);
            }
            $item ??= new CartItem(['user_id' => $renter->id, 'listing_id' => $listingId]);
            $item->fill(self::draft($quote))->save();

            return $item->fresh();
        }, 3);
    }

    public static function update(User $renter, int $cartItemId, array $details): CartItem
    {
        $renter = self::member($renter);
        $item = CartItem::where('user_id', $renter->id)->find($cartItemId);
        abort_unless($item, 404);

        return DB::transaction(function () use ($renter, $item, $details) {
            $quote = RentalRequestSubmission::preview($renter, $item->listing_id, $details, forRequest: false, allowOverlap: true);
            $current = CartItem::where('user_id', $renter->id)->lockForUpdate()->find($item->id);
            abort_unless($current, 404);
            $current->fill(self::draft($quote))->save();

            return $current->fresh();
        }, 3);
    }

    public static function remove(User $renter, int $cartItemId): void
    {
        $renter = self::member($renter);
        DB::transaction(function () use ($renter, $cartItemId) {
            $renter = self::member($renter, lock: true);
            $item = CartItem::where('user_id', $renter->id)->lockForUpdate()->find($cartItemId);
            abort_unless($item, 404);
            $item->delete();
        }, 3);
    }

    public static function checkout(User $renter, array $selectedIds, bool $accepted, ?array $reviewed = null): array
    {
        $renter = self::member($renter);
        if (! $renter->hasVerifiedIdentity()) {
            throw ValidationException::withMessages(['identity' => 'Complete your ID review before sending rental requests.']);
        }
        $selectedIds = self::selection($selectedIds);
        $result = DB::transaction(function () use ($renter, $selectedIds, $accepted, $reviewed) {
            $drafts = CartItem::where('user_id', $renter->id)->whereIn('id', $selectedIds)->get();
            if ($drafts->count() !== count($selectedIds)) {
                throw ValidationException::withMessages(['selected' => 'Some selected items are no longer in your cart. Refresh the cart and try again.']);
            }
            $listingIds = $drafts->pluck('listing_id')->sort()->values()->all();
            $owners = Listing::withTrashed()->whereIn('id', $listingIds)->pluck('owner_id', 'id');
            $participantIds = $owners->values()->push($renter->id)->unique()->sort()->values()->all();
            User::withTrashed()->whereIn('id', $participantIds)->orderBy('id')->lockForUpdate()->get();
            $renter = self::member($renter, lock: true);
            if (! $renter->hasVerifiedIdentity()) {
                throw ValidationException::withMessages(['identity' => 'Complete your ID review before sending rental requests.']);
            }
            $listings = Listing::withTrashed()->whereIn('id', $listingIds)->orderBy('id')->lockForUpdate()->get();
            $items = CartItem::where('user_id', $renter->id)->whereIn('id', $selectedIds)->orderBy('id')->lockForUpdate()->get();
            if ($items->count() !== count($selectedIds) || $items->pluck('listing_id')->sort()->values()->all() !== $listingIds
                || $listings->contains(fn (Listing $listing) => $owners->get($listing->id) !== $listing->owner_id)) {
                throw ValidationException::withMessages(['selected' => 'Your cart changed. Review the cart and try again.']);
            }

            $errors = [];
            $changed = false;
            foreach ($items as $item) {
                $expected = $reviewed[$item->id] ?? [];
                if ($reviewed !== null && (! RentalRequestSubmission::termsMatch(array_diff_key($expected, ['terms' => true]), self::details($item))
                    || ! RentalRequestSubmission::termsMatch($expected['terms'] ?? [], $item->terms))) {
                    $changed = true;
                    $errors['items.'.$item->id.'.terms'] = 'This draft changed in another tab. Review its updated dates, handover and quote before sending requests.';
                }
                try {
                    $quote = RentalRequestSubmission::preview($renter, $item->listing_id, self::details($item));
                    if (! RentalRequestSubmission::termsMatch($quote['terms'], $item->terms)) {
                        $item->update(['terms' => $quote['terms']]);
                        $changed = true;
                        $errors['items.'.$item->id.'.terms'] = 'The price or rental terms changed. Review the updated quote before sending requests.';
                    }
                } catch (ValidationException $exception) {
                    foreach ($exception->errors() as $field => $messages) {
                        $errors['items.'.$item->id.'.'.$field] = $messages;
                    }
                }
            }
            if ($changed || ! $accepted) {
                $errors['accept_terms'] = $changed
                    ? 'The price or rental terms changed. Review the updated quotes and accept them again.'
                    : 'Review and accept the selected rental terms before sending requests.';
            }
            if ($errors) {
                return ['ids' => [], 'errors' => $errors];
            }

            $ids = [];
            foreach ($items as $item) {
                try {
                    $ids[] = RentalRequestSubmission::submit($renter, $item->listing_id, self::details($item), $item->terms, true)->id;
                } catch (ValidationException $exception) {
                    $messages = [];
                    foreach ($exception->errors() as $field => $errors) {
                        $messages['items.'.$item->id.'.'.$field] = $errors;
                    }
                    throw ValidationException::withMessages($messages);
                }
                $item->delete();
            }

            return ['ids' => $ids, 'errors' => []];
        }, 3);

        if ($result['errors']) {
            // Commit refreshed quotes even when checkout stops for review.
            throw ValidationException::withMessages($result['errors']);
        }

        return $result['ids'];
    }

    private static function member(User $renter, bool $lock = false): User
    {
        $query = User::query();
        if ($lock) {
            $query->lockForUpdate();
        }
        $member = $query->find($renter->id);
        abort_unless($member && ! $member->isAdmin() && ! $member->isSuspended() && $member->hasVerifiedEmail()
            && $member->activeInterface() === 'renter', 403);

        return $member;
    }

    private static function selection(array $ids): array
    {
        if (! array_is_list($ids) || count($ids) < 1 || count($ids) > 50) {
            throw ValidationException::withMessages(['selected' => 'Select between 1 and 50 items from your cart.']);
        }
        $valid = [];
        foreach ($ids as $id) {
            if ((! is_int($id) && ! is_string($id)) || ! preg_match('/^[1-9][0-9]*$/', (string) $id)
                || filter_var($id, FILTER_VALIDATE_INT) === false) {
                throw ValidationException::withMessages(['selected' => 'Select valid items from your cart.']);
            }
            $valid[] = (int) $id;
        }
        if (count(array_unique($valid)) !== count($valid)) {
            throw ValidationException::withMessages(['selected' => 'Each cart item can be selected only once.']);
        }
        sort($valid);

        return $valid;
    }

    private static function draft(array $quote): array
    {
        return array_intersect_key($quote['attributes'], array_flip(['start_date', 'end_date', 'fulfillment_method', 'rental_unit', 'rental_hours', 'starts_at', 'ends_at']))
            + ['terms' => $quote['terms']];
    }

    private static function details(CartItem $item): array
    {
        return RentalPeriod::details($item);
    }
}
