<?php

namespace Tests\Feature;

use App\Enums\RentalAdminActionType;
use App\Enums\RentalStatus;
use App\Enums\ReviewType;
use App\Livewire\Owner\Rentals\Show as OwnerRentalShow;
use App\Livewire\Renter\Rentals\Show as RenterRentalShow;
use App\Livewire\Users\Show as PublicUserShow;
use App\Models\Category;
use App\Models\ConditionRecord;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\SecurityDeposit;
use App\Models\User;
use App\Services\RentalAdministration;
use App\Services\RentalLifecycle;
use App\Services\RentalReviews;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicUserReviewsTest extends TestCase
{
    use RefreshDatabase;

    private function rental(User $owner, User $renter, RentalStatus $status = RentalStatus::Completed): Rental
    {
        $category = Category::firstOrCreate(['slug' => 'profile-tools'], ['name' => 'Profile Tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id, 'name' => 'Private transaction item',
            'description' => 'Item description', 'condition' => 'good', 'price_per_day' => 100,
            'security_deposit' => 500, 'location' => 'Manila', 'max_rental_duration_days' => 10,
            'status' => 'published', 'is_available' => true, 'pickup_available' => true,
        ]);
        $terms = [
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->subDays(4), 'end_date' => now()->subDays(2), 'rental_days' => 3,
            'fulfillment_method' => 'pickup', 'rental_fee' => 300, 'commission_rate' => 10,
            'commission_amount' => 30, 'security_deposit' => 500, 'total_amount' => 830,
        ];
        $request = RentalRequest::create($terms + ['status' => 'approved']);
        $rental = Rental::create($terms + [
            'rental_request_id' => $request->id, 'owner_id' => $owner->id, 'status' => $status,
            'paid_at' => now()->subDays(5),
            'pickup_confirmed_by_owner_at' => now()->subDays(4), 'pickup_confirmed_by_renter_at' => now()->subDays(4),
            'return_confirmed_by_owner_at' => now()->subDays(2), 'return_confirmed_by_renter_at' => now()->subDays(2),
            'completed_at' => $status === RentalStatus::Completed ? now() : null,
            'archived_at' => $status === RentalStatus::Completed ? now() : null,
        ]);
        Payment::create(['rental_id' => $rental->id, 'transaction_reference' => Payment::generateReference(), 'amount' => 830, 'paid_at' => now()]);
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 500, 'status' => 'held']);
        ConditionRecord::create(['rental_id' => $rental->id, 'recorded_by' => $owner->id, 'type' => 'after', 'condition' => 'good']);

        return $rental;
    }

    public static function completionActors(): array
    {
        return ['owner closes' => [false], 'admin closes' => [true]];
    }

    #[DataProvider('completionActors')]
    public function test_closing_a_rental_prompts_both_parties_once_and_leaves_reviews_optional(bool $byAdmin): void
    {
        $rental = $this->rental(User::factory()->create(), User::factory()->create(), RentalStatus::Returned);
        $actor = $byAdmin ? User::factory()->admin()->create() : $rental->owner;
        $this->actingAs($actor);
        $complete = fn () => $byAdmin
            ? RentalAdministration::perform($rental, $actor, RentalAdminActionType::InspectionCompleted, 'Return inspected')
            : RentalLifecycle::completeInspection($rental);
        $complete();

        $this->assertSame(RentalStatus::Completed, $rental->fresh()->status);
        $this->assertNotNull($rental->fresh()->archived_at);
        $this->assertDatabaseCount('reviews', 0);
        foreach (['owner' => $rental->owner, 'renter' => $rental->renter] as $role => $party) {
            $notice = $party->notifications()->where('data->type', 'review_request')->sole();
            $this->assertSame(route($role.'.rentals.show', $rental).'#reviews', $notice->data['url']);
            $this->assertStringContainsString('You may rate and review', $notice->data['message']);
            $this->assertStringContainsString('public profile', $notice->data['message']);
        }
        try {
            $complete();
            $this->fail('A completed rental must not close again.');
        } catch (AuthorizationException) {
            $this->assertSame(2, $rental->owner->notifications()->where('data->type', 'review_request')->count()
                + $rental->renter->notifications()->where('data->type', 'review_request')->count());
        }
    }

    public function test_submitted_person_reviews_are_immediately_public_on_the_correct_profile(): void
    {
        $owner = User::factory()->create(['name' => 'Reviewed Owner']);
        $renter = User::factory()->create(['name' => 'Reviewed Renter']);
        $rental = $this->rental($owner, $renter);
        Livewire::actingAs($renter)->test(RenterRentalShow::class, ['rental' => $rental])
            ->assertSee('Optional.')->assertSee('public profile')->assertSeeHtml('id="reviews"')
            ->set('owner_rating', 5)->set('owner_comment', 'Helpful owner review')->call('submitOwnerReview')->assertHasNoErrors();
        Livewire::actingAs($owner)->test(OwnerRentalShow::class, ['rental' => $rental])
            ->assertSee('Optional.')->assertSee('public profile')->assertSeeHtml('id="reviews"')
            ->set('renter_rating', 4)->set('renter_comment', 'Careful renter review')->call('submitRenterReview')->assertHasNoErrors();
        RentalReviews::submit($rental, $renter, ReviewType::RenterToListing, 1, 'Only an item review');
        auth()->logout();

        $this->get(route('users.show', $owner))->assertOk()->assertSee('Helpful owner review')
            ->assertSee('5.0 out of 5')->assertSee('As an owner')->assertDontSee('Careful renter review')->assertDontSee('Only an item review');
        $this->get(route('users.show', $renter))->assertOk()->assertSee('Careful renter review')
            ->assertSee('4.0 out of 5')->assertSee('As a renter')->assertDontSee('Helpful owner review')->assertDontSee('Only an item review');
    }

    public function test_one_member_can_receive_reviews_in_both_roles_with_separate_averages(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        foreach ([5, 3] as $rating) {
            RentalReviews::submit($this->rental($member, $other), $other, ReviewType::RenterToOwner, $rating, 'Owner role review');
        }
        RentalReviews::submit($this->rental($other, $member), $other, ReviewType::OwnerToRenter, 2, 'Renter role review');
        $this->get(route('users.show', $member))->assertOk()->assertSee('Received reviews (3)')
            ->assertSee('4.0 out of 5 · 2 reviews')->assertSee('2.0 out of 5 · 1 review')
            ->assertSee('Owner role review')->assertSee('Renter role review');
        $this->assertSame(4.0, $member->averageRatingAsOwner());
        $this->assertSame(2.0, $member->averageRatingAsRenter());
    }

    public function test_public_profile_does_not_expose_contact_details_transaction_data_or_unescaped_comments(): void
    {
        $owner = User::factory()->create(['email' => 'private-owner@example.test', 'phone' => '09171111111', 'address' => 'Private owner address']);
        $renter = User::factory()->create(['email' => 'private-renter@example.test', 'phone' => '09172222222', 'address' => 'Private renter address']);
        $rental = $this->rental($owner, $renter);
        $comment = '<script>alert("review")</script>';
        RentalReviews::submit($rental, $renter, ReviewType::RenterToOwner, 4, $comment);
        $response = $this->get(route('users.show', $owner))->assertOk()->assertSee($comment)
            ->assertDontSee($comment, false)->assertSeeHtml(route('users.show', $renter));
        foreach ([$owner->email, $owner->phone, $owner->address, $renter->email, $renter->phone, $renter->address,
            $rental->listing->name, $rental->payment->transaction_reference, route('owner.rentals.show', $rental)] as $private) {
            $response->assertDontSee($private, false);
        }
    }

    public function test_rating_only_review_and_empty_profile_are_public(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $this->get(route('users.show', $owner))->assertOk()->assertSee('No reviews yet')->assertSee('No ratings yet');
        RentalReviews::submit($this->rental($owner, $renter), $renter, ReviewType::RenterToOwner, 5, null);
        $this->get(route('users.show', $owner))->assertOk()->assertSee('Rating only')
            ->assertSee('5 out of 5 stars')->assertSee('Received reviews (1)');
    }

    public function test_public_reviews_survive_listing_and_reviewer_deletion_without_linking_deleted_accounts(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->rental($owner, $renter);
        RentalReviews::submit($rental, $renter, ReviewType::RenterToOwner, 5, 'Archived member review');
        $rental->listing->delete();
        $renter->delete();
        $this->get(route('users.show', $owner))->assertOk()->assertSee('Archived member review')
            ->assertSee('Deleted account')->assertDontSee(route('users.show', $renter), false);
        $this->get(route('users.show', $renter))->assertNotFound();
    }

    public function test_profiles_exclude_unclosed_and_self_reviews_and_other_members_received_reviews(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        foreach ([RentalStatus::Returned, RentalStatus::Cancelled] as $status) {
            $this->rental($member, $other, $status)->reviews()->create(['type' => ReviewType::RenterToOwner, 'rating' => 1, 'comment' => 'Unclosed review']);
        }
        $this->rental($member, $member)->reviews()->create(['type' => ReviewType::RenterToOwner, 'rating' => 5, 'comment' => 'Self review']);
        RentalReviews::submit($this->rental($other, $member), $member, ReviewType::RenterToOwner, 3, 'Review received by someone else');
        $this->get(route('users.show', $member))->assertOk()->assertSee('Received reviews (0)')
            ->assertDontSee('Unclosed review')->assertDontSee('Self review')->assertDontSee('Review received by someone else');
        $this->assertNull($member->averageRatingAsOwner());
    }

    public function test_profiles_paginate_reviews_newest_first_without_changing_totals(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        foreach (range(1, 11) as $number) {
            RentalReviews::submit($this->rental($owner, $renter), $renter, ReviewType::RenterToOwner, 5, 'Public review '.$number.' end');
        }
        $this->get(route('users.show', $owner))->assertOk()->assertSee('Received reviews (11)')
            ->assertSee('5.0 out of 5 · 11 reviews')->assertSee('Public review 11 end')->assertDontSee('Public review 1 end');
        $this->get(route('users.show', $owner).'?page=2')->assertOk()->assertSee('Public review 1 end')->assertDontSee('Public review 11 end');
    }

    public function test_profile_links_are_discoverable_and_admin_accounts_have_no_member_profile(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->rental($owner, $renter);
        $this->get(route('listings.show', $rental->listing))->assertOk()->assertSeeHtml(route('users.show', $owner));
        $this->actingAs($renter)->get(route('renter.rentals.show', $rental))->assertOk()->assertSeeHtml(route('users.show', $owner));
        $this->actingAs($owner)->get(route('owner.rentals.show', $rental))->assertOk()->assertSeeHtml(route('users.show', $renter));
        $this->get(route('profile'))->assertOk()->assertSeeHtml(route('users.show', $owner));
        auth()->logout();
        $this->get(route('users.show', User::factory()->admin()->create()))->assertNotFound();
        $this->get('/users/999999')->assertNotFound();
    }

    public function test_a_removed_profile_cannot_be_refreshed(): void
    {
        $user = User::factory()->create();
        $profile = Livewire::test(PublicUserShow::class, ['user' => $user]);
        $user->delete();
        $this->expectException(ModelNotFoundException::class);
        $profile->call('$refresh');
    }

    public function test_public_profile_identity_is_locked(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(PublicUserShow::class, ['user' => User::factory()->create()])->set('userId', 123);
    }
}
