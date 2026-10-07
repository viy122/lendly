<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Enums\RentalStatus;
use App\Livewire\Notifications\Bell;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Owner\RentalRequests\Index as OwnerRequests;
use App\Livewire\RentalRequests\Agreement;
use App\Livewire\RentalRequests\Create;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\RentalAgreement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class BookingConfirmationNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function requestFixture(): array
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id,
            'name' => 'Confirmation drill', 'description' => 'An item for rent.',
            'condition' => 'good', 'price_per_day' => 100, 'security_deposit' => 500,
            'location' => 'Manila', 'max_rental_duration_days' => 10,
            'status' => 'published', 'is_available' => true, 'pickup_available' => true,
        ]);
        Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString())
            ->call('submit')->assertHasNoErrors();

        return [$owner, $renter, $listing, RentalRequest::sole()];
    }

    private function approve(User $owner, RentalRequest $request): void
    {
        Livewire::actingAs($owner)->test(OwnerRequests::class)
            ->call('approve', $request->id)->assertHasNoErrors();
        $request->refresh();
    }

    private function accept(User $user, RentalRequest $request): void
    {
        Livewire::actingAs($user)->test(Agreement::class, ['rentalRequest' => $request])
            ->set('accept_terms', true)->call('acceptTerms')->assertHasNoErrors();
    }

    private function confirmationCount(): int
    {
        return DB::table('notifications')->where('data->type', NotificationType::BookingConfirmed->value)->count();
    }

    private function assertSavedConfirmations(User $owner, User $renter, Rental $booking): array
    {
        $saved = [];
        foreach (['owner' => $owner, 'renter' => $renter] as $interface => $user) {
            $notices = $user->notifications()->where('data->type', NotificationType::BookingConfirmed->value)->get();
            $this->assertCount(1, $notices);
            $notice = $notices->sole();
            $this->assertNull($notice->read_at);
            $this->assertSame('Booking confirmed', $notice->data['title']);
            $this->assertStringContainsString('Confirmation drill', $notice->data['message']);
            $this->assertStringContainsString('Both parties accepted', $notice->data['message']);
            $this->assertSame(route($interface.'.rentals.show', $booking), $notice->data['url']);
            $saved[$user->id] = $notice->getAttributes();
        }
        $this->assertSame(2, $this->confirmationCount());

        return $saved;
    }

    public static function acceptanceOrders(): array
    {
        return ['owner accepts last' => [false], 'renter accepts last' => [true]];
    }

    #[DataProvider('acceptanceOrders')]
    public function test_confirmation_is_saved_for_both_parties_only_when_booking_is_finalized(bool $ownerFirst): void
    {
        [$owner, $renter, $listing, $request] = $this->requestFixture();
        $this->assertSame(0, $this->confirmationCount());
        $this->approve($owner, $request);
        $this->assertSame(0, Rental::count());
        $this->assertSame(0, $this->confirmationCount());
        foreach ([$owner, $renter] as $user) {
            $this->assertSame(1, $user->notifications()->where('data->type', NotificationType::RequestApproved->value)->count());
        }

        $first = $ownerFirst ? $owner : $renter;
        $second = $ownerFirst ? $renter : $owner;
        $this->accept($first, $request);
        $this->assertSame(0, Rental::count());
        $this->assertSame(0, $this->confirmationCount());
        // The notice should use the agreed item name, even if the listing changes.
        $listing->update(['name' => 'Renamed drill']);
        $this->accept($second, $request);
        $booking = Rental::sole();
        $this->assertSame(RentalStatus::PaymentPending, $booking->status);
        $this->assertSavedConfirmations($owner, $renter, $booking);

        foreach (['owner' => $owner, 'renter' => $renter] as $interface => $user) {
            $this->withSession(['active_interface' => $interface]);
            Livewire::actingAs($user)->test(NotificationsIndex::class)
                ->assertSee('Booking confirmed')->assertSeeHtml(route($interface.'.rentals.show', $booking));
            Livewire::test(Bell::class)->assertSee('Booking confirmed')
                ->assertSeeHtml(route($interface.'.rentals.show', $booking));
            $this->get(route($interface.'.rentals.show', $booking))->assertOk();
        }
        $stranger = User::factory()->create();
        $this->withSession(['active_interface' => 'renter']);
        Livewire::actingAs($stranger)->test(NotificationsIndex::class)->assertDontSee('Booking confirmed');
        $this->assertSame(0, $stranger->notifications()->count());
    }

    public function test_retried_acceptances_do_not_duplicate_or_replace_confirmation_notices(): void
    {
        [$owner, $renter, , $request] = $this->requestFixture();
        $this->approve($owner, $request);
        $this->accept($owner, $request);
        $this->accept($renter, $request);
        $booking = Rental::sole();
        $saved = $this->assertSavedConfirmations($owner, $renter, $booking);
        $this->travel(1)->hours();
        foreach ([$renter, $owner, $renter] as $user) {
            $this->accept($user, $request);
        }
        $this->assertSame($saved, $this->assertSavedConfirmations($owner, $renter, $booking));
        $this->assertSame($booking->id, Rental::sole()->id);
    }

    public function test_failure_to_save_the_second_notice_rolls_back_booking_and_both_notices(): void
    {
        [$owner, $renter, , $request] = $this->requestFixture();
        $this->approve($owner, $request);
        $this->accept($owner, $request);
        $savedCount = DB::table('notifications')->count();
        Event::listen(NotificationSending::class, function (NotificationSending $event) use ($owner) {
            if ($event->notification->type === NotificationType::BookingConfirmed->value && $event->notifiable->is($owner)) {
                throw new RuntimeException('Owner confirmation storage failed.');
            }
        });
        try {
            RentalAgreement::accept($request, $renter);
            $this->fail('The notification storage failure must be raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Owner confirmation storage failed.', $exception->getMessage());
        } finally {
            Event::forget(NotificationSending::class);
        }

        $this->assertSame(0, Rental::count());
        $this->assertSame(0, $this->confirmationCount());
        $this->assertSame($savedCount, DB::table('notifications')->count());
        $this->assertNotNull($request->fresh()->owner_terms_accepted_at);
        $this->assertNull($request->fresh()->renter_terms_accepted_at);

        $this->accept($renter, $request);
        $this->assertSavedConfirmations($owner, $renter, Rental::sole());
    }
}
