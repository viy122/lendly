<?php

namespace Tests\Feature;

use App\Enums\RentalStatus;
use App\Enums\ReviewType;
use App\Livewire\Owner\Rentals\Show as OwnerRentalShow;
use App\Livewire\Renter\Rentals\Show as RenterRentalShow;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\Review;
use App\Models\SecurityDeposit;
use App\Models\User;
use App\Services\RentalReviews;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RentalReviewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function rental(User $owner, User $renter, RentalStatus $status = RentalStatus::Completed): Rental
    {
        $category = Category::firstOrCreate(['slug' => 'review-tools'], ['name' => 'Review Tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id, 'name' => 'Reviewed item',
            'description' => 'Item description', 'condition' => 'good', 'price_per_day' => 100,
            'security_deposit' => 500, 'location' => 'Manila', 'max_rental_duration_days' => 10,
            'status' => 'published', 'is_available' => true, 'pickup_available' => true,
        ]);
        $terms = [
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->addDays(3), 'end_date' => now()->addDays(5), 'rental_days' => 3,
            'fulfillment_method' => 'pickup', 'rental_fee' => 300, 'commission_rate' => 10,
            'commission_amount' => 30, 'security_deposit' => 500, 'total_amount' => 830,
        ];
        $request = RentalRequest::create($terms + ['status' => 'approved']);
        $rental = Rental::create($terms + [
            'rental_request_id' => $request->id, 'owner_id' => $owner->id, 'status' => $status,
            'paid_at' => now(), 'completed_at' => $status === RentalStatus::Completed ? now() : null,
            'archived_at' => $status === RentalStatus::Completed ? now() : null,
            'cancelled_at' => $status === RentalStatus::Cancelled ? now() : null,
            'cancellation_reason' => $status === RentalStatus::Cancelled ? 'Booking cancelled' : null,
        ]);
        Payment::create(['rental_id' => $rental->id, 'transaction_reference' => Payment::generateReference(), 'amount' => 830, 'paid_at' => now()]);
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 500, 'status' => 'return_eligible']);

        return $rental->fresh();
    }

    public static function reviewActions(): array
    {
        return [
            'renter reviews owner' => [RenterRentalShow::class, 'submitOwnerReview', 'owner', ReviewType::RenterToOwner],
            'renter reviews item' => [RenterRentalShow::class, 'submitListingReview', 'listing', ReviewType::RenterToListing],
            'owner reviews renter' => [OwnerRentalShow::class, 'submitRenterReview', 'renter', ReviewType::OwnerToRenter],
        ];
    }

    private function reviewer(User $owner, User $renter, ReviewType $type): User
    {
        return $type === ReviewType::OwnerToRenter ? $owner : $renter;
    }

    public function test_both_parties_can_review_each_other_independently_on_the_same_completed_transaction(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->rental($owner, $renter);
        $saved = $rental->getAttributes();
        foreach (self::reviewActions() as [$component, $action, $field, $type]) {
            $rating = match ($type) {
                ReviewType::RenterToOwner => 5,
                ReviewType::RenterToListing => 4,
                ReviewType::OwnerToRenter => 2,
            };
            Livewire::actingAs($this->reviewer($owner, $renter, $type))->test($component, ['rental' => $rental])
                ->set($field.'_rating', $rating)->set($field.'_comment', $type->label())
                ->call($action)->assertHasNoErrors();
        }

        $this->assertDatabaseCount('reviews', 3);
        $this->assertSame(5.0, $owner->averageRatingAsOwner());
        $this->assertSame(2.0, $renter->averageRatingAsRenter());
        $this->assertSame(4.0, $rental->listing->averageRating());
        $this->assertSame($saved, $rental->fresh()->getAttributes());
    }

    #[DataProvider('reviewActions')]
    public function test_actual_participant_can_submit_rating_and_comment_after_completion(string $component, string $action, string $field, ReviewType $type): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->rental($owner, $renter);
        $reviewer = $this->reviewer($owner, $renter, $type);

        Livewire::actingAs($reviewer)->test($component, ['rental' => $rental])
            ->assertSeeHtml('wire:submit="'.$action.'"')
            ->set($field.'_rating', 4)->set($field.'_comment', 'Clear communication and a smooth rental.')
            ->call($action)->assertHasNoErrors()
            ->assertSee('You rated 4/5 stars')->assertSee('Clear communication and a smooth rental.')
            ->assertDontSeeHtml('wire:submit="'.$action.'"');

        $review = Review::sole();
        $this->assertSame($rental->id, $review->rental_id);
        $this->assertSame($type, $review->type);
        $this->assertSame(4, $review->rating);
        $this->assertSame('Clear communication and a smooth rental.', $review->comment);
    }

    #[DataProvider('reviewActions')]
    public function test_opposite_party_cannot_submit_a_review_through_the_other_partys_page(string $component, string $action, string $field, ReviewType $type): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->rental($owner, $renter);
        $wrongParty = $type === ReviewType::OwnerToRenter ? $renter : $owner;

        Livewire::actingAs($wrongParty)->test($component, ['rental' => $rental])
            ->assertDontSeeHtml('wire:submit="'.$action.'"')
            ->set($field.'_rating', 5)->set($field.'_comment', 'Unauthorized review')
            ->call($action)->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
        $this->assertNull($owner->averageRatingAsOwner());
        $this->assertNull($renter->averageRatingAsRenter());
        $this->assertNull($rental->listing->averageRating());
    }

    #[DataProvider('reviewActions')]
    public function test_admin_view_access_does_not_allow_review_submission(string $component, string $action, string $field, ReviewType $type): void
    {
        $rental = $this->rental(User::factory()->create(), User::factory()->create());
        Livewire::actingAs(User::factory()->admin()->create())->test($component, ['rental' => $rental])
            ->assertDontSeeHtml('wire:submit="'.$action.'"')
            ->call($action)->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
    }

    #[DataProvider('reviewActions')]
    public function test_non_participant_cannot_view_or_review_the_transaction(string $component, string $action, string $field, ReviewType $type): void
    {
        $rental = $this->rental(User::factory()->create(), User::factory()->create());
        $stranger = User::factory()->create();
        Livewire::actingAs($stranger)->test($component, ['rental' => $rental])->assertForbidden();
        $this->assertReviewDenied(fn () => RentalReviews::submit($rental, $stranger, $type, 5, 'Forged review'));
        $this->assertDatabaseCount('reviews', 0);
    }

    #[DataProvider('reviewActions')]
    public function test_duplicate_submission_from_another_open_tab_preserves_the_first_review(string $component, string $action, string $field, ReviewType $type): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->rental($owner, $renter);
        $reviewer = $this->reviewer($owner, $renter, $type);
        $firstTab = Livewire::actingAs($reviewer)->test($component, ['rental' => $rental]);
        $otherTab = Livewire::test($component, ['rental' => $rental]);

        $firstTab->set($field.'_rating', 1)->set($field.'_comment', 'Original review')->call($action)->assertHasNoErrors();
        $otherTab->set($field.'_rating', 5)->set($field.'_comment', 'Replacement')->call($action)->assertForbidden();
        $this->assertReviewDenied(fn () => RentalReviews::submit($rental, $reviewer, $type, 5, null));

        $this->assertDatabaseCount('reviews', 1);
        $this->assertSame(1, Review::sole()->rating);
        $this->assertSame('Original review', Review::sole()->comment);
    }

    #[DataProvider('reviewActions')]
    public function test_rating_range_and_comment_length_are_validated(string $component, string $action, string $field, ReviewType $type): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->rental($owner, $renter);
        $form = Livewire::actingAs($this->reviewer($owner, $renter, $type))->test($component, ['rental' => $rental]);

        foreach ([0, 6] as $rating) {
            $form->set($field.'_rating', $rating)->call($action)->assertHasErrors([$field.'_rating']);
            $this->assertDatabaseCount('reviews', 0);
        }
        $form->set($field.'_rating', 5)->set($field.'_comment', str_repeat('x', 1001))
            ->call($action)->assertHasErrors([$field.'_comment']);
        $this->assertDatabaseCount('reviews', 0);
        $form->set($field.'_comment', '')->call($action)->assertHasNoErrors();
        $this->assertSame(5, Review::sole()->rating);
    }

    public static function incompleteStatuses(): array
    {
        return array_map(fn (RentalStatus $status) => [$status], array_filter(RentalStatus::cases(), fn ($status) => $status !== RentalStatus::Completed));
    }

    #[DataProvider('incompleteStatuses')]
    public function test_reviews_require_a_completed_transaction_for_every_direction(RentalStatus $status): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->rental($owner, $renter, $status);
        foreach (self::reviewActions() as [$component, $action, $field, $type]) {
            Livewire::actingAs($this->reviewer($owner, $renter, $type))->test($component, ['rental' => $rental])
                ->assertDontSeeHtml('wire:submit="'.$action.'"')->call($action)->assertForbidden();
        }
        $this->assertDatabaseCount('reviews', 0);
    }

    #[DataProvider('reviewActions')]
    public function test_review_service_rechecks_saved_status_instead_of_a_stale_completed_model(string $component, string $action, string $field, ReviewType $type): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->rental($owner, $renter);
        Rental::whereKey($rental->id)->update(['status' => RentalStatus::Cancelled]);
        $this->assertTrue($rental->isCompleted());

        $this->assertReviewDenied(fn () => RentalReviews::submit($rental, $this->reviewer($owner, $renter, $type), $type, 5, null));
        $this->assertDatabaseCount('reviews', 0);
    }

    #[DataProvider('reviewActions')]
    public function test_legacy_self_rental_cannot_generate_a_self_review(string $component, string $action, string $field, ReviewType $type): void
    {
        $user = User::factory()->create();
        $rental = $this->rental($user, $user);
        Livewire::actingAs($user)->test($component, ['rental' => $rental])
            ->assertDontSeeHtml('wire:submit="'.$action.'"')->call($action)->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
    }

    private function assertReviewDenied(callable $submit): void
    {
        try {
            $submit();
            $this->fail('Review submission must be forbidden.');
        } catch (AuthorizationException $exception) {
            $this->assertSame(403, $exception->status() ?? 403);
        }
    }
}
