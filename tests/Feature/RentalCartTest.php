<?php

namespace Tests\Feature;

use App\Enums\RentalRequestStatus;
use App\Livewire\Renter\Cart\Index;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\CommissionSetting;
use App\Models\Listing;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\RentalCart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RentalCartTest extends TestCase
{
    use RefreshDatabase;

    private function listing(?User $owner = null, array $overrides = []): Listing
    {
        $owner ??= User::factory()->create();
        $category = Category::firstOrCreate(['slug' => 'cart-tools'], ['name' => 'Tools']);

        return Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id, 'name' => 'Cart item '.Str::random(6),
            'description' => 'Synthetic rental item', 'condition' => 'good', 'price_per_day' => 100,
            'security_deposit' => 500, 'rental_rules' => 'Return clean', 'location' => 'Manila',
            'max_rental_duration_days' => 10, 'status' => 'published', 'is_available' => true,
            'pickup_available' => true, 'delivery_available' => true,
            'available_from' => now()->addDay()->toDateString(), 'available_until' => now()->addDays(30)->toDateString(),
            ...$overrides,
        ]);
    }

    private function details(int $start = 3, int $end = 5): array
    {
        return ['start_date' => now()->addDays($start)->toDateString(), 'end_date' => now()->addDays($end)->toDateString(), 'fulfillment_method' => 'pickup'];
    }

    private function validation(callable $action, string $key): ValidationException
    {
        try {
            $action();
            $this->fail('Expected validation to stop the cart action.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($key, $exception->errors());

            return $exception;
        }
    }

    public function test_save_is_one_draft_per_listing_with_server_quotes_and_no_requests_holds_or_notifications(): void
    {
        $renter = User::factory()->create();
        $listing = $this->listing();
        $item = RentalCart::save($renter, $listing->id, $this->details() + ['rental_fee' => 1, 'total_amount' => 1, 'owner_id' => $renter->id]);
        $same = RentalCart::save($renter, $listing->id, $this->details(7, 8));

        $this->assertSame($item->id, $same->id);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertSame($renter->id, $same->user_id);
        $this->assertSame(now()->addDays(7)->toDateString(), $same->start_date->toDateString());
        $this->assertSame(720.0, $same->totalAmount());
        $this->assertDatabaseCount('rental_requests', 0);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertTrue($listing->fresh()->is_available);
    }

    public function test_update_requotes_dates_and_remove_only_operates_on_the_passed_renters_draft(): void
    {
        $renter = User::factory()->create();
        $item = RentalCart::save($renter, $this->listing()->id, $this->details());
        $updated = RentalCart::update($renter, $item->id, $this->details(6, 6) + ['fulfillment_method' => 'delivery']);
        $this->assertSame(610.0, $updated->totalAmount());
        $other = User::factory()->create();
        foreach ([fn () => RentalCart::update($other, $item->id, $this->details()), fn () => RentalCart::remove($other, $item->id)] as $action) {
            try {
                $action();
                $this->fail('Another member cannot mutate this draft.');
            } catch (HttpException $exception) {
                $this->assertSame(404, $exception->getStatusCode());
            }
        }
        RentalCart::remove($renter, $item->id);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_a_full_cart_can_update_an_existing_draft_but_cannot_add_another_listing(): void
    {
        $renter = User::factory()->create();
        $listing = $this->listing();
        $first = RentalCart::save($renter, $listing->id, $this->details());
        for ($index = 1; $index < 50; $index++) {
            $another = $listing->replicate();
            $another->save();
            CartItem::create(['user_id' => $renter->id, 'listing_id' => $another->id, ...$this->details(), 'terms' => $first->terms]);
        }
        $this->assertSame($first->id, RentalCart::save($renter, $listing->id, $this->details(7, 8))->id);
        $this->validation(fn () => RentalCart::save($renter, $this->listing()->id, $this->details()), 'cart');
        $this->assertDatabaseCount('cart_items', 50);
    }

    public function test_checkout_consumes_only_selected_rows_and_retries_cannot_submit_them_again(): void
    {
        $renter = User::factory()->create();
        $first = RentalCart::save($renter, $this->listing()->id, $this->details());
        $second = RentalCart::save($renter, $this->listing()->id, $this->details(8, 9));
        $ids = RentalCart::checkout($renter, [$first->id], true);
        $this->assertCount(1, $ids);
        $request = RentalRequest::findOrFail($ids[0]);
        $this->assertSame($first->listing_id, $request->listing_id);
        $this->assertSame(RentalRequestStatus::Requested, $request->status);
        $this->assertSame('830.00', $request->total_amount);
        $this->assertDatabaseMissing('cart_items', ['id' => $first->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $second->id]);
        $this->validation(fn () => RentalCart::checkout($renter, [$first->id], true), 'selected');
        $this->assertDatabaseCount('rental_requests', 1);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_malformed_or_foreign_selection_and_missing_acceptance_cannot_submit(): void
    {
        $renter = User::factory()->create();
        $item = RentalCart::save($renter, $this->listing()->id, $this->details());
        $foreign = RentalCart::save(User::factory()->create(), $this->listing()->id, $this->details());
        foreach ([[], [$item->id, $item->id], [$item->id, $foreign->id], ['bad'], [true], [[$item->id]], range(1, 51)] as $selection) {
            $this->validation(fn () => RentalCart::checkout($renter, $selection, true), 'selected');
        }
        $this->validation(fn () => RentalCart::checkout($renter, [$item->id], false), 'accept_terms');
        $this->assertDatabaseCount('rental_requests', 0);
        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_changed_prices_rules_and_commission_refresh_quotes_without_sending_any_requests(): void
    {
        $renter = User::factory()->create();
        $first = RentalCart::save($renter, $this->listing()->id, $this->details());
        $second = RentalCart::save($renter, $this->listing()->id, $this->details());
        $first->listing->update(['price_per_day' => 200, 'rental_rules' => 'Updated rule']);
        CommissionSetting::current()->update(['commission_rate' => 15]);
        $this->validation(fn () => RentalCart::checkout($renter, [$first->id, $second->id], true), 'accept_terms');
        $this->assertSame('200.00', $first->fresh()->terms['price_per_day']);
        $this->assertSame('Updated rule', $first->fresh()->terms['rental_rules']);
        $this->assertSame('15.00', $second->fresh()->terms['commission_rate']);
        $this->assertDatabaseCount('rental_requests', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertCount(2, RentalCart::checkout($renter, [$first->id, $second->id], true));
    }

    public function test_one_unavailable_selected_listing_aborts_the_whole_checkout(): void
    {
        $renter = User::factory()->create();
        $first = RentalCart::save($renter, $this->listing()->id, $this->details());
        $second = RentalCart::save($renter, $this->listing()->id, $this->details());
        $second->listing->delete();
        $this->validation(fn () => RentalCart::checkout($renter, [$first->id, $second->id], true), 'items.'.$second->id.'.listing');
        $this->assertDatabaseCount('rental_requests', 0);
        $this->assertDatabaseCount('cart_items', 2);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_outdated_dates_and_newly_approved_overlaps_preserve_all_cart_drafts(): void
    {
        $renter = User::factory()->create();
        $first = RentalCart::save($renter, $this->listing()->id, $this->details());
        $second = RentalCart::save($renter, $this->listing()->id, $this->details());
        RentalRequest::create([
            'listing_id' => $second->listing_id, 'renter_id' => User::factory()->create()->id,
            ...$this->details(), 'rental_days' => 3, 'rental_fee' => 300, 'commission_rate' => 10,
            'commission_amount' => 30, 'security_deposit' => 500, 'total_amount' => 830, 'status' => 'approved',
        ]);
        $this->validation(fn () => RentalCart::checkout($renter, [$first->id, $second->id], true), 'items.'.$second->id.'.start_date');
        $this->assertDatabaseCount('rental_requests', 1);
        $this->assertDatabaseCount('cart_items', 2);
        $this->travel(7)->days();
        $this->validation(fn () => RentalCart::checkout($renter, [$first->id], true), 'items.'.$first->id.'.start_date');
        $this->assertDatabaseCount('rental_requests', 1);
        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_component_rechecks_member_eligibility_for_every_selection_and_mutation(): void
    {
        foreach (['selectAll', 'selectOwner', 'remove', 'updateItem', 'checkout'] as $action) {
            $renter = User::factory()->create();
            $item = RentalCart::save($renter, $this->listing()->id, $this->details());
            $component = Livewire::actingAs($renter)->test(Index::class);
            $renter->update(['status' => 'suspended']);
            $component->call($action, $action === 'selectOwner' ? $item->listing->owner_id : $item->id)->assertForbidden();
            $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
        }
        session(['active_interface' => 'owner']);
        Livewire::actingAs(User::factory()->create())->test(Index::class)->assertForbidden();
    }

    public function test_late_submission_failure_rolls_back_every_request_and_cart_consumption(): void
    {
        $renter = User::factory()->create();
        $first = RentalCart::save($renter, $this->listing()->id, $this->details());
        $second = RentalCart::save($renter, $this->listing()->id, $this->details());
        $created = 0;
        Event::listen('eloquent.creating: '.RentalRequest::class, function () use (&$created) {
            if (++$created === 2) {
                throw new RuntimeException('Synthetic write failure');
            }
        });
        try {
            RentalCart::checkout($renter, [$first->id, $second->id], true);
            $this->fail('The write failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic write failure', $exception->getMessage());
        } finally {
            Event::forget('eloquent.creating: '.RentalRequest::class);
        }
        $this->assertDatabaseCount('rental_requests', 0);
        $this->assertDatabaseCount('cart_items', 2);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_stale_ineligible_members_and_owner_interface_cannot_use_cart_actions(): void
    {
        foreach (['suspended', 'deleted', 'unverified', 'admin', 'owner_interface'] as $change) {
            $renter = User::factory()->create();
            $item = RentalCart::save($renter, $this->listing()->id, $this->details());
            $stale = $renter->fresh();
            match ($change) {
                'suspended' => $renter->update(['status' => 'suspended']),
                'deleted' => $renter->delete(),
                'unverified' => $renter->forceFill(['email_verified_at' => null])->save(),
                'admin' => $renter->update(['role' => 'admin']),
                'owner_interface' => session(['active_interface' => 'owner']),
            };
            foreach ([fn () => RentalCart::save($stale, $item->listing_id, $this->details()), fn () => RentalCart::update($stale, $item->id, $this->details()), fn () => RentalCart::remove($stale, $item->id), fn () => RentalCart::checkout($stale, [$item->id], true)] as $action) {
                try {
                    $action();
                    $this->fail('The inactive renter must be refused.');
                } catch (HttpException $exception) {
                    $this->assertSame(403, $exception->getStatusCode());
                }
            }
            session()->forget('active_interface');
        }
    }

    public function test_component_groups_selects_and_edits_only_own_items_and_resets_acceptance(): void
    {
        $renter = User::factory()->create();
        $owner = User::factory()->create();
        $first = RentalCart::save($renter, $this->listing($owner)->id, $this->details());
        $second = RentalCart::save($renter, $this->listing($owner)->id, $this->details());
        $third = RentalCart::save($renter, $this->listing()->id, $this->details());
        $foreign = RentalCart::save(User::factory()->create(), $this->listing()->id, $this->details());
        $component = Livewire::actingAs($renter)->test(Index::class)
            ->assertSet('selected', [$first->id, $second->id, $third->id])
            ->assertViewHas('groups', fn ($groups) => $groups->count() === 2)
            ->assertViewHas('selectedTotal', 2490.0)
            ->set('accept_terms', true)->call('selectOwner', $owner->id)->assertSet('accept_terms', false)
            ->assertSet('selected', [$third->id])->call('selectAll')->assertSet('selected', [$first->id, $second->id, $third->id]);
        $component->set('edits.'.$first->id.'.end_date', now()->addDays(7)->toDateString())->set('accept_terms', true)
            ->call('checkout')->assertHasErrors('items.'.$first->id.'.dates')->assertSet('accept_terms', false);
        $component->call('updateItem', $first->id)->assertHasNoErrors()->assertSet('accept_terms', false);
        $this->assertSame(now()->addDays(7)->toDateString(), $first->fresh()->end_date->toDateString());
        $component->call('remove', $foreign->id)->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $foreign->id]);
    }

    public function test_component_checkout_keeps_changed_quotes_for_review_then_redirects_after_explicit_acceptance(): void
    {
        $renter = User::factory()->create();
        $item = RentalCart::save($renter, $this->listing()->id, $this->details());
        $component = Livewire::actingAs($renter)->test(Index::class)->set('accept_terms', true);
        $item->listing->update(['price_per_day' => 150]);
        $component->call('checkout')->assertHasErrors('accept_terms')->assertSet('accept_terms', false);
        $this->assertSame('150.00', $item->fresh()->terms['price_per_day']);
        $component->set('accept_terms', true)->call('checkout')->assertHasNoErrors()->assertRedirect(route('renter.rental-requests.index'));
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('rental_requests', 1);
    }

    public function test_checkout_refuses_draft_changes_after_the_reviewed_snapshot_was_displayed(): void
    {
        $renter = User::factory()->create();
        $item = RentalCart::save($renter, $this->listing()->id, $this->details());
        $reviewed = [$item->id => $this->details() + ['terms' => $item->terms]];
        RentalCart::update($renter, $item->id, $this->details(3, 8));
        $this->validation(fn () => RentalCart::checkout($renter, [$item->id], true, $reviewed), 'accept_terms');
        $this->assertDatabaseCount('rental_requests', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'end_date' => now()->addDays(8)->toDateString().' 00:00:00']);
    }

    public function test_checkout_accepts_unchanged_strict_term_values_when_json_object_key_order_changes(): void
    {
        $renter = User::factory()->create();
        $item = RentalCart::save($renter, $this->listing()->id, $this->details());
        $reviewed = [$item->id => array_reverse($this->details() + ['terms' => $item->terms], preserve_keys: true)];
        $item->update(['terms' => array_reverse($item->terms, preserve_keys: true)]);
        $ids = RentalCart::checkout($renter, [$item->id], true, $reviewed);
        $this->assertCount(1, $ids);
        $this->assertSame('830.00', RentalRequest::findOrFail($ids[0])->total_amount);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_storage_key_order_changes_do_not_clear_a_components_acceptance(): void
    {
        $renter = User::factory()->create();
        $item = RentalCart::save($renter, $this->listing()->id, $this->details());
        $component = Livewire::actingAs($renter)->test(Index::class)->set('accept_terms', true);
        $item->update(['terms' => array_reverse($item->terms, preserve_keys: true)]);
        $component->call('$refresh')->assertSet('accept_terms', true)->call('checkout')->assertHasNoErrors()
            ->assertRedirect(route('renter.rental-requests.index'));
        $this->assertSame('830.00', RentalRequest::sole()->total_amount);
    }

    public function test_component_blocks_a_draft_change_between_its_precheck_and_the_locked_checkout_read(): void
    {
        $renter = User::factory()->create();
        $item = RentalCart::save($renter, $this->listing()->id, $this->details());
        $component = Livewire::actingAs($renter)->test(Index::class)->set('accept_terms', true);
        $changed = false;
        DB::listen(function ($query) use (&$changed, $item) {
            if (! $changed && str_starts_with($query->sql, 'select * from "cart_items"') && str_contains($query->sql, 'order by "id" asc')) {
                $changed = true;
                DB::table('cart_items')->where('id', $item->id)->update(['end_date' => now()->addDays(8)->toDateString().' 00:00:00']);
            }
        });
        $component->call('checkout')->assertHasErrors('accept_terms')->assertSet('accept_terms', false)
            ->assertSet('edits.'.$item->id.'.end_date', now()->addDays(8)->toDateString());
        $this->assertTrue($changed);
        $this->assertDatabaseCount('rental_requests', 0);
        $this->assertDatabaseCount('notifications', 0);
        $component->set('accept_terms', true)->call('checkout')->assertHasNoErrors()->assertRedirect(route('renter.rental-requests.index'));
        $this->assertSame(6, RentalRequest::sole()->rental_days);
    }

    public function test_reviewed_cart_snapshots_cannot_be_tampered_with_by_the_browser(): void
    {
        $renter = User::factory()->create();
        $item = RentalCart::save($renter, $this->listing()->id, $this->details());
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::actingAs($renter)->test(Index::class)->set('reviewed.'.$item->id.'.terms.price_per_day', 1);
    }

    public function test_a_render_with_updated_cart_details_clears_acceptance_before_the_next_checkout(): void
    {
        $renter = User::factory()->create();
        $item = RentalCart::save($renter, $this->listing()->id, $this->details());
        $component = Livewire::actingAs($renter)->test(Index::class);
        RentalCart::update($renter, $item->id, $this->details(3, 8));
        $component->set('accept_terms', true)->assertSet('accept_terms', false)
            ->assertSet('reviewed.'.$item->id.'.end_date', now()->addDays(8)->toDateString());
        $this->assertDatabaseCount('rental_requests', 0);
    }
}
