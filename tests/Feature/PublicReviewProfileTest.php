<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalStatus;
use App\Enums\ReviewType;
use App\Livewire\Profiles\Show;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicReviewProfileTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->category = Category::create(['name' => 'Tools', 'slug' => 'tools']);
    }

    private function rental(User $owner, User $renter, RentalStatus $status = RentalStatus::Completed): Rental
    {
        $listing = Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $this->category->id,
            'name' => 'Private rental item',
            'description' => 'Rental history fixture.',
            'condition' => 'good',
            'price_per_day' => 100,
            'location' => 'Private pickup address',
            'max_rental_duration_days' => 7,
            'status' => ListingStatus::Published,
        ]);
        $terms = [
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDays(2),
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 1000,
            'total_amount' => 1330,
        ];
        $request = RentalRequest::create($terms + ['status' => 'approved']);

        return Rental::create($terms + [
            'rental_request_id' => $request->id,
            'owner_id' => $owner->id,
            'status' => $status,
            'completed_at' => $status === RentalStatus::Completed ? now() : null,
        ]);
    }

    private function review(Rental $rental, ReviewType $type, int $rating, ?string $comment): Review
    {
        return Review::create(['rental_id' => $rental->id, 'type' => $type, 'rating' => $rating, 'comment' => $comment]);
    }

    public function test_verified_member_has_a_guest_accessible_profile_without_private_account_or_rental_data(): void
    {
        $member = User::factory()->create([
            'name' => 'Public Member', 'email' => 'private-profile@example.test', 'phone' => '09998887776',
            'address' => 'PRIVATE-HOME-ADDRESS', 'avatar_path' => 'avatars/public-member.jpg',
        ]);
        $author = User::factory()->create(['name' => 'Review Author', 'email' => 'private-author@example.test', 'phone' => '09997776665']);
        $rental = $this->rental($member, $author);
        $this->review($rental, ReviewType::RenterToOwner, 5, 'Helpful owner');
        Payment::create(['rental_id' => $rental->id, 'transaction_reference' => 'PRIVATE-PAYMENT-REFERENCE', 'amount' => 1330, 'paid_at' => now()]);

        $this->get('/users/'.$member->id)->assertOk()
            ->assertSee('Public Member')->assertSee('Review Author')->assertSee('Helpful owner')
            ->assertSee('/storage/avatars/public-member.jpg', false)
            ->assertDontSee('private-profile@example.test')->assertDontSee('private-author@example.test')
            ->assertDontSee('09998887776')->assertDontSee('09997776665')->assertDontSee('PRIVATE-HOME-ADDRESS')
            ->assertDontSee('PRIVATE-PAYMENT-REFERENCE')->assertDontSee('Private pickup address')->assertDontSee('Private rental item')
            ->assertDontSee('1,330')->assertDontSee('rental_fee')->assertDontSee('transaction_reference');
    }

    public function test_profile_ratings_include_received_completed_peer_reviews_and_exclude_authored_and_item_reviews(): void
    {
        $member = User::factory()->create(['name' => 'Reviewed member']);
        $peer = User::factory()->create(['name' => 'Reviewing peer']);
        $ownerRental = $this->rental($member, $peer);
        $this->review($ownerRental, ReviewType::RenterToOwner, 5, 'Received as owner one');
        $this->review($ownerRental, ReviewType::OwnerToRenter, 1, 'Authored by member');
        $this->review($ownerRental, ReviewType::RenterToListing, 1, 'Item review excluded');
        $this->review($this->rental($member, $peer), ReviewType::RenterToOwner, 3, 'Received as owner two');
        $this->review($this->rental($peer, $member), ReviewType::OwnerToRenter, 2, 'Received as renter one');
        $this->review($this->rental($peer, $member), ReviewType::OwnerToRenter, 3, 'Received as renter two');
        $this->review($this->rental($member, $peer, RentalStatus::Active), ReviewType::RenterToOwner, 1, 'Unfinished rental excluded');
        $this->review($this->rental($peer, User::factory()->create()), ReviewType::OwnerToRenter, 1, 'Other recipient excluded');

        Livewire::test(Show::class, ['user' => $member])
            ->assertViewHas('ownerAverageRating', 4.0)->assertViewHas('renterAverageRating', 2.5)
            ->assertViewHas('reviews', fn ($reviews) => $reviews->total() === 4)
            ->assertSee('Received as owner one')->assertSee('Received as renter one')
            ->assertDontSee('Authored by member')->assertDontSee('Item review excluded')
            ->assertDontSee('Unfinished rental excluded')->assertDontSee('Other recipient excluded');
    }

    public function test_empty_profile_has_no_rating_and_a_readable_empty_state(): void
    {
        $member = User::factory()->create();

        Livewire::test(Show::class, ['user' => $member])
            ->assertViewHas('ownerAverageRating', null)->assertViewHas('renterAverageRating', null)
            ->assertSee('No reviews yet');
    }

    public function test_public_profile_rechecks_member_visibility_on_livewire_refresh(): void
    {
        $member = User::factory()->create();
        $component = Livewire::test(Show::class, ['user' => $member]);
        $member->update(['status' => 'suspended']);

        $component->call('$refresh')->assertNotFound();
    }

    public function test_review_comments_are_escaped_and_show_author_rating_and_date(): void
    {
        $member = User::factory()->create();
        $author = User::factory()->create(['name' => 'Helpful reviewer']);
        $comment = '<script>alert("review-xss")</script>';
        $review = $this->review($this->rental($member, $author), ReviewType::RenterToOwner, 4, $comment);
        $review->forceFill(['created_at' => '2026-10-01 12:00:00'])->saveQuietly();

        $this->get('/users/'.$member->id)->assertOk()->assertSee('Helpful reviewer')
            ->assertSee('4 / 5')->assertSee('Oct 01, 2026')
            ->assertSee(e($comment), false)->assertDontSee($comment, false);
    }

    public static function prohibitedProfiles(): array
    {
        return [
            'admin' => [['role' => 'admin']],
            'suspended' => [['status' => 'suspended']],
            'unverified' => [['email_verified_at' => null]],
            'deleted' => [['deleted_at' => '2026-10-01 12:00:00']],
        ];
    }

    #[DataProvider('prohibitedProfiles')]
    public function test_prohibited_profiles_return_404_without_identity(array $attributes): void
    {
        $member = User::factory()->create($attributes + ['name' => 'PRIVATE-UNAVAILABLE-IDENTITY']);

        $this->get('/users/'.$member->id)->assertNotFound()->assertDontSee('PRIVATE-UNAVAILABLE-IDENTITY');
    }

    #[DataProvider('prohibitedProfiles')]
    public function test_prohibited_review_author_identity_is_hidden(array $attributes): void
    {
        $member = User::factory()->create(['name' => 'Visible profile']);
        $author = User::factory()->create($attributes + ['name' => 'PRIVATE-REVIEW-AUTHOR']);
        $this->review($this->rental($member, $author), ReviewType::RenterToOwner, 5, 'Received public review');

        $this->get('/users/'.$member->id)->assertOk()->assertSee('Received public review')
            ->assertDontSee('PRIVATE-REVIEW-AUTHOR');
    }

    public static function peerDirections(): array
    {
        return ['received as owner' => [ReviewType::RenterToOwner], 'received as renter' => [ReviewType::OwnerToRenter]];
    }

    #[DataProvider('peerDirections')]
    public function test_closed_review_author_history_survives_without_their_identity(ReviewType $type): void
    {
        $owner = User::factory()->create(['name' => 'Original owner name']);
        $renter = User::factory()->create(['name' => 'Original renter name']);
        $rental = $this->rental($owner, $renter);
        $this->review($rental, $type, 5, 'Preserved completed review');
        $recipient = $type === ReviewType::RenterToOwner ? $owner : $renter;
        $author = $type === ReviewType::RenterToOwner ? $renter : $owner;
        $originalName = $author->name;
        $originalEmail = $author->email;
        $author->closeAccount();

        $this->get('/users/'.$recipient->id)->assertOk()->assertSee('Preserved completed review')->assertSee('Deleted user')
            ->assertDontSee($originalName)->assertDontSee($originalEmail);
    }

    public function test_profile_paginates_twelve_reviews_and_averages_all_received_reviews(): void
    {
        $member = User::factory()->create();
        $author = User::factory()->create();
        foreach (range(1, 13) as $number) {
            $review = $this->review($this->rental($member, $author), ReviewType::RenterToOwner, $number === 1 ? 1 : 5, sprintf('Public review #%02d.', $number));
            $review->forceFill(['created_at' => now()->subMinutes(14 - $number)])->saveQuietly();
        }

        Livewire::test(Show::class, ['user' => $member])
            ->assertViewHas('ownerAverageRating', fn ($average) => abs($average - 4.6923076923) < .00001)
            ->assertViewHas('reviews', fn ($reviews) => $reviews->count() === 12 && $reviews->total() === 13)
            ->assertSee('Public review #13.')->assertDontSee('Public review #01.')
            ->call('setPage', 2)->assertViewHas('reviews', fn ($reviews) => $reviews->count() === 1)
            ->assertSee('Public review #01.')->assertDontSee('Public review #13.');
    }
}
