<?php

namespace Tests\Feature;

use App\Enums\RentalRequestStatus;
use App\Livewire\RentalRequests\Create;
use App\Models\Category;
use App\Models\CommissionSetting;
use App\Models\Listing;
use App\Models\User;
use App\Services\RentalRequestSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RentalRequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_uses_current_server_charges_without_creating_request_or_notices(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();
        CommissionSetting::current()->update(['commission_rate' => 12.5]);
        $details = $this->details() + ['renter_id' => $listing->owner_id, 'rental_fee' => 1, 'total_amount' => 1, 'commission_rate' => 0, 'status' => 'approved'];

        $preview = RentalRequestSubmission::preview($renter, $listing->id, $details);

        $this->assertTrue($preview['listing']->is($listing));
        $this->assertSame(['price_per_day' => '125.25', 'price_per_hour' => null, 'allows_daily_rentals' => true,
            'min_rental_duration_hours' => 1, 'max_rental_duration_hours' => 24, 'available_time_from' => '00:00', 'available_time_until' => '23:59', 'security_deposit' => '250.50', 'rental_rules' => 'Keep it clean', 'commission_rate' => '12.50'], $preview['terms']);
        $this->assertSame($renter->id, $preview['attributes']['renter_id']);
        $this->assertSame($listing->id, $preview['attributes']['listing_id']);
        $this->assertSame(3, $preview['attributes']['rental_days']);
        $this->assertSame(375.75, $preview['attributes']['rental_fee']);
        $this->assertSame(46.97, $preview['attributes']['commission_amount']);
        $this->assertSame(673.22, $preview['attributes']['total_amount']);
        $this->assertDatabaseCount('rental_requests', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_submit_creates_pending_request_and_both_notices_without_accepting_booking_agreement(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();

        $request = RentalRequestSubmission::submit($renter, $listing->id, $this->details(), RentalRequestSubmission::termsFor($listing), true);

        $this->assertSame(RentalRequestStatus::Requested, $request->status);
        $this->assertSame('663.83', $request->total_amount);
        $this->assertSame('10.00', $request->commission_rate);
        $this->assertNull($request->renter_terms_accepted_at);
        $this->assertNull($request->owner_terms_accepted_at);
        $this->assertNull($request->agreement_terms);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertSame(1, $listing->owner->notifications()->where('data->type', 'rental_request_submitted')->count());
        $this->assertSame(1, $renter->notifications()->where('data->type', 'rental_request_submitted')->count());
    }

    public function test_request_form_can_save_cart_draft_without_review_acceptance_or_notifications(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();

        Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])
            ->set('start_date', $this->details()['start_date'])->set('end_date', $this->details()['end_date'])
            ->call('addToCart')->assertHasNoErrors()->assertRedirect(route('renter.cart.index'));

        $this->assertDatabaseHas('cart_items', ['user_id' => $renter->id, 'listing_id' => $listing->id]);
        $this->assertDatabaseCount('rental_requests', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    #[DataProvider('changedTerms')]
    public function test_submit_rejects_changed_quotes_without_creating_requests(string $field, mixed $value): void
    {
        $listing = $this->listing();
        $terms = RentalRequestSubmission::termsFor($listing);
        if ($field === 'commission_rate') {
            CommissionSetting::current()->update([$field => $value]);
        } else {
            $listing->update([$field => $value]);
        }

        $this->rejects(fn () => RentalRequestSubmission::submit(User::factory()->identityVerified()->create(), $listing->id, $this->details(), $terms, true), 'accept_terms');

        $this->assertDatabaseCount('rental_requests', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public static function changedTerms(): array
    {
        return [['price_per_day', 200], ['security_deposit', 500], ['rental_rules', 'New rule'], ['commission_rate', 15]];
    }

    public function test_submit_requires_explicit_acceptance_but_preview_does_not(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();
        $preview = RentalRequestSubmission::preview($renter, $listing->id, $this->details());

        $this->rejects(fn () => RentalRequestSubmission::submit($renter, $listing->id, $this->details(), $preview['terms'], false), 'accept_terms');

        $this->assertDatabaseCount('rental_requests', 0);
    }

    public function test_submit_accepts_reviewed_terms_in_database_key_order_without_coercing_values(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();
        $terms = RentalRequestSubmission::termsFor($listing);
        ksort($terms);

        $request = RentalRequestSubmission::submit($renter, $listing->id, $this->details(), $terms, true);

        $this->assertSame('663.83', $request->total_amount);
        $terms['price_per_day'] = 125.25;
        $this->rejects(fn () => RentalRequestSubmission::submit($renter, $listing->id, $this->details(), $terms, true), 'accept_terms');
        $this->assertDatabaseCount('rental_requests', 1);
    }

    #[DataProvider('invalidDetails')]
    public function test_preview_and_submit_share_date_duration_and_handover_validation(array $changes, string $field): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();
        $details = array_replace($this->details(), $changes);

        $this->rejects(fn () => RentalRequestSubmission::preview($renter, $listing->id, $details), $field);
        $this->rejects(fn () => RentalRequestSubmission::submit($renter, $listing->id, $details, RentalRequestSubmission::termsFor($listing), true), $field);

        $this->assertDatabaseCount('rental_requests', 0);
    }

    public static function invalidDetails(): array
    {
        return [
            [['start_date' => 'not-a-date'], 'start_date'],
            [['start_date' => '2000-01-01'], 'start_date'],
            [['start_date' => '2099-01-02', 'end_date' => '2099-01-01'], 'end_date'],
            [['start_date' => '2099-01-01', 'end_date' => '2099-02-01'], 'end_date'],
            [['fulfillment_method' => 'delivery'], 'fulfillment_method'],
            [['fulfillment_method' => 'courier'], 'fulfillment_method'],
        ];
    }

    public function test_current_availability_window_and_approved_overlap_are_rechecked(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();
        $listing->update(['available_until' => now()->addDays(4)]);
        $this->rejects(fn () => RentalRequestSubmission::preview($renter, $listing->id, $this->details()), 'end_date');
        $listing->update(['available_until' => null]);
        $request = RentalRequestSubmission::submit($renter, $listing->id, $this->details(), RentalRequestSubmission::termsFor($listing), true);
        $request->update(['status' => RentalRequestStatus::Approved]);

        $this->rejects(fn () => RentalRequestSubmission::preview(User::factory()->identityVerified()->create(), $listing->id, $this->details()), 'start_date');

        $this->assertDatabaseCount('rental_requests', 1);
    }

    public function test_unavailable_listing_is_reported_on_the_listing_field(): void
    {
        $listing = $this->listing();
        $listing->update(['is_available' => false]);

        $this->rejects(fn () => RentalRequestSubmission::preview(User::factory()->identityVerified()->create(), $listing->id, $this->details()), 'listing');
    }

    public function test_fresh_account_and_ownership_checks_reject_unauthorized_requests(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();
        $renter->fresh()->update(['status' => 'suspended']);
        foreach ([$listing->owner, $renter, User::factory()->admin()->create(), User::factory()->identityVerified()->unverified()->create()] as $actor) {
            try {
                RentalRequestSubmission::preview($actor, $listing->id, $this->details());
                $this->fail('Unauthorized account must not obtain an actionable quote.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
        $listing->owner->delete();
        try {
            RentalRequestSubmission::preview(User::factory()->identityVerified()->create(), $listing->id, $this->details());
            $this->fail('Deleted owners must not receive requests.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertDatabaseCount('rental_requests', 0);
    }

    public function test_second_notification_failure_rolls_back_request_and_first_notification(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();
        Event::listen(NotificationSending::class, function (NotificationSending $event) use ($renter) {
            if ($event->notifiable->is($renter)) {
                throw new \RuntimeException('Notice storage failed');
            }
        });

        try {
            RentalRequestSubmission::submit($renter, $listing->id, $this->details(), RentalRequestSubmission::termsFor($listing), true);
            $this->fail('The notification failure must roll back the transaction.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Notice storage failed', $exception->getMessage());
        } finally {
            Event::forget(NotificationSending::class);
        }
        $this->assertDatabaseCount('rental_requests', 0);
        $this->assertSame(0, DB::table('notifications')->count());
    }

    private function listing(): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'submission-tools'], ['name' => 'Tools']);

        return Listing::create([
            'owner_id' => User::factory()->identityVerified()->create()->id, 'category_id' => $category->id,
            'name' => 'Rental tool', 'description' => 'Tool', 'condition' => 'good', 'location' => 'Manila',
            'price_per_day' => 125.25, 'security_deposit' => 250.50, 'rental_rules' => 'Keep it clean',
            'max_rental_duration_days' => 10, 'status' => 'published', 'is_available' => true,
            'pickup_available' => true, 'delivery_available' => false,
        ]);
    }

    private function details(): array
    {
        return ['start_date' => now()->addDays(3)->toDateString(), 'end_date' => now()->addDays(5)->toDateString(), 'fulfillment_method' => 'pickup'];
    }

    private function rejects(callable $operation, string $field): void
    {
        try {
            $operation();
            $this->fail('The request must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}
