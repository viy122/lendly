<?php

namespace Tests\Feature;

use App\Livewire\Rentals\ExchangeSchedule;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\ExchangeArrangements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExchangeArrangementsTest extends TestCase
{
    use RefreshDatabase;

    private function booking(string $method = 'pickup'): array
    {
        $this->travelTo(now()->startOfDay());
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id,
            'name' => 'Pressure Washer', 'description' => 'An item for rent.',
            'condition' => 'good', 'price_per_day' => 100, 'security_deposit' => 500,
            'location' => 'Manila', 'max_rental_duration_days' => 10,
            'status' => 'published', 'is_available' => true,
            'pickup_available' => true, 'delivery_available' => true,
        ]);
        $request = RentalRequest::create([
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->addDays(3)->toDateString(), 'end_date' => now()->addDays(5)->toDateString(),
            'rental_days' => 3, 'fulfillment_method' => $method,
            'rental_fee' => 300, 'commission_rate' => 10, 'commission_amount' => 30,
            'security_deposit' => 500, 'total_amount' => 830, 'status' => 'approved',
            'owner_terms_accepted_at' => now(), 'renter_terms_accepted_at' => now(),
        ]);
        $rental = Rental::create([
            'rental_request_id' => $request->id, 'listing_id' => $listing->id,
            'owner_id' => $owner->id, 'renter_id' => $renter->id,
            ...$request->only(['start_date', 'end_date', 'rental_days', 'fulfillment_method', 'rental_fee', 'commission_rate', 'commission_amount', 'security_deposit', 'total_amount']),
            'status' => 'paid', 'paid_at' => now(),
        ]);

        return [$owner, $renter, $rental];
    }

    private function proposal(Rental $rental, User $user, string $address = '123 Test Street, Manila'): void
    {
        Livewire::actingAs($user)->test(ExchangeSchedule::class, ['rental' => $rental])
            ->call('startProposal')
            ->set('exchange_time', $rental->start_date->format('Y-m-d').'T10:30')
            ->set('address', $address)->set('notes', 'Meet at the front gate.')
            ->call('propose')->assertHasNoErrors();
    }

    public static function methodsAndOrders(): array
    {
        return ['pickup owner first' => ['pickup', true], 'pickup renter first' => ['pickup', false],
            'delivery owner first' => ['delivery', true], 'delivery renter first' => ['delivery', false]];
    }

    #[DataProvider('methodsAndOrders')]
    public function test_both_parties_confirm_a_saved_schedule_and_are_notified(string $method, bool $ownerFirst): void
    {
        [$owner, $renter, $rental] = $this->booking($method);
        $first = $ownerFirst ? $owner : $renter;
        $second = $ownerFirst ? $renter : $owner;
        $this->proposal($rental, $first);
        $schedule = $rental->fresh()->exchangeSchedule;
        $this->assertSame($first->id, $schedule->proposed_by);
        $this->assertSame($method, $schedule->fulfillment_method->value);
        $this->assertSame($rental->start_date->format('Y-m-d').' 02:30:00', $schedule->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertNull($schedule->confirmed_at);
        $this->assertNull($schedule->owner_confirmed_at);
        $this->assertNull($schedule->renter_confirmed_at);
        $this->assertSame(0, $first->notifications()->count());
        $this->assertSame('exchange_schedule_proposed', $second->notifications()->first()->data['type']);

        foreach ([$owner, $renter] as $party) {
            Livewire::actingAs($party)->test(ExchangeSchedule::class, ['rental' => $rental])
                ->assertSee(ucfirst($method).' arrangements')->assertSee('123 Test Street, Manila')
                ->assertSee('10:30 AM')->assertSee('Asia/Manila')->assertSee('Meet at the front gate.');
        }
        Livewire::actingAs($first)->test(ExchangeSchedule::class, ['rental' => $rental])
            ->call('confirm', $schedule->id)->assertHasNoErrors();
        $this->assertNull($schedule->fresh()->confirmed_at);
        $this->assertSame(0, $first->notifications()->where('data->type', 'exchange_schedule_confirmed')->count());
        $firstField = $ownerFirst ? 'owner_confirmed_at' : 'renter_confirmed_at';
        $firstConfirmation = $schedule->fresh()->$firstField->toDateTimeString();

        Livewire::actingAs($second)->test(ExchangeSchedule::class, ['rental' => $rental])
            ->call('confirm', $schedule->id)->assertHasNoErrors()->assertSee('Schedule confirmed by both parties');
        $this->assertNotNull($schedule->fresh()->confirmed_at);
        foreach ([$owner, $renter] as $party) {
            $notices = $party->notifications()->where('data->type', 'exchange_schedule_confirmed')->get();
            $this->assertCount(1, $notices);
            $data = $notices->first()->data;
            $this->assertSame(ucfirst($method).' schedule confirmed', $data['title']);
            $this->assertStringContainsString('123 Test Street, Manila', $data['message']);
            $this->assertStringContainsString('10:30 AM (Asia/Manila)', $data['message']);
            $interface = $party->id === $owner->id ? 'owner' : 'renter';
            $this->assertSame(route($interface.'.rentals.show', $rental).'#exchange-arrangements', $data['url']);
        }
        $this->assertSame('paid', $rental->fresh()->status->value);
        $this->assertNull($rental->fresh()->pickup_confirmed_by_owner_at);
        $this->assertNull($rental->fresh()->pickup_confirmed_by_renter_at);

        $this->travel(1)->hours();
        ExchangeArrangements::confirm($rental, $first, $schedule->id);
        ExchangeArrangements::confirm($rental, $second, $schedule->id);
        $this->assertSame($firstConfirmation, $schedule->fresh()->$firstField->toDateTimeString());
        $this->assertSame(3, $owner->notifications()->count() + $renter->notifications()->count());
    }

    public function test_revised_schedules_require_fresh_confirmations_and_reject_stale_forms(): void
    {
        [$owner, $renter, $rental] = $this->booking();
        $this->proposal($rental, $owner);
        $original = $rental->fresh()->exchangeSchedule;
        ExchangeArrangements::confirm($rental, $owner, $original->id);
        ExchangeArrangements::confirm($rental, $renter, $original->id);
        $staleForm = Livewire::actingAs($owner)->test(ExchangeSchedule::class, ['rental' => $rental])->call('startProposal');

        $this->proposal($rental, $renter, '456 New Street, Manila');
        $latest = $rental->fresh()->exchangeSchedule;
        $this->assertNotSame($original->id, $latest->id);
        $this->assertNull($latest->owner_confirmed_at);
        $this->assertNull($latest->renter_confirmed_at);
        $this->assertNull($latest->confirmed_at);
        $this->assertNotNull($original->fresh()->confirmed_at);
        $this->assertSame(2, $rental->exchangeSchedules()->count());

        Livewire::actingAs($owner)->test(ExchangeSchedule::class, ['rental' => $rental])
            ->call('confirm', $original->id)->assertHasErrors(['schedule'])->assertSee('The schedule has changed');
        $staleForm->set('exchange_time', $rental->start_date->format('Y-m-d').'T12:00')->set('address', 'Old form address')
            ->call('propose')->assertHasErrors(['schedule']);
        $this->assertSame(2, $rental->exchangeSchedules()->count());
        $this->assertNull($latest->fresh()->confirmed_at);
    }

    public static function invalidFields(): array
    {
        return ['empty time' => ['exchange_time', ''], 'bad time' => ['exchange_time', 'bad date'],
            'empty address' => ['address', '  '], 'long address' => ['address', str_repeat('x', 501)],
            'long notes' => ['notes', str_repeat('x', 1001)], 'past time' => ['exchange_time', '2000-01-01T10:00']];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_arrangements_are_rejected(string $field, string $value): void
    {
        [$owner, , $rental] = $this->booking();
        Livewire::actingAs($owner)->test(ExchangeSchedule::class, ['rental' => $rental])->call('startProposal')
            ->set('exchange_time', $rental->start_date->format('Y-m-d').'T10:00')->set('address', 'Valid address')
            ->set($field, $value)->call('propose')->assertHasErrors([$field]);
        $this->assertSame(0, $rental->exchangeSchedules()->count());
        $this->assertSame(0, $rental->owner->notifications()->count() + $rental->renter->notifications()->count());
    }

    public function test_time_must_fall_within_the_rental_period(): void
    {
        [$owner, , $rental] = $this->booking();
        foreach ([$rental->start_date->copy()->subDay(), $rental->end_date->copy()->addDay()] as $date) {
            Livewire::actingAs($owner)->test(ExchangeSchedule::class, ['rental' => $rental])->call('startProposal')
                ->set('exchange_time', $date->format('Y-m-d').'T10:00')->set('address', 'Valid address')
                ->call('propose')->assertHasErrors(['exchange_time']);
        }
        $this->assertSame(0, $rental->exchangeSchedules()->count());
    }

    public function test_unrelated_users_cannot_propose_or_confirm_private_arrangements(): void
    {
        [$owner, , $rental] = $this->booking();
        $this->proposal($rental, $owner);
        $schedule = $rental->fresh()->exchangeSchedule;
        $stranger = User::factory()->create();
        Livewire::actingAs($stranger)->test(ExchangeSchedule::class, ['rental' => $rental])->assertForbidden();
        $form = Livewire::actingAs($owner)->test(ExchangeSchedule::class, ['rental' => $rental])->call('startProposal');
        $this->actingAs($stranger);
        $form->call('confirm', $schedule->id)->assertForbidden();
        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(ExchangeSchedule::class, ['rental' => $rental])->call('startProposal')->assertForbidden();
        $this->assertNull($schedule->fresh()->owner_confirmed_at);
        $this->assertNull($schedule->fresh()->renter_confirmed_at);
    }

    public static function lockedBookings(): array
    {
        return ['cancelled' => [['status' => 'cancelled']], 'active' => [['status' => 'active']],
            'returned' => [['status' => 'returned']], 'completed' => [['status' => 'completed']],
            'owner hand-over' => [['pickup_confirmed_by_owner_at' => '2026-01-01 00:00:00']],
            'renter received' => [['pickup_confirmed_by_renter_at' => '2026-01-01 00:00:00']]];
    }

    #[DataProvider('lockedBookings')]
    public function test_arrangements_cannot_change_after_cancellation_or_hand_over(array $changes): void
    {
        [$owner, , $rental] = $this->booking();
        $this->proposal($rental, $owner);
        $schedule = $rental->fresh()->exchangeSchedule;
        $form = Livewire::actingAs($owner)->test(ExchangeSchedule::class, ['rental' => $rental])->call('startProposal');
        $rental->update($changes);
        $form->call('propose')->assertForbidden();
        Livewire::actingAs($owner)->test(ExchangeSchedule::class, ['rental' => $rental])
            ->call('confirm', $schedule->id)->assertForbidden();
        $this->assertSame(1, $rental->exchangeSchedules()->count());
        $this->assertNull($schedule->fresh()->confirmed_at);
    }

    public function test_expired_proposal_cannot_be_confirmed(): void
    {
        [$owner, , $rental] = $this->booking();
        $this->proposal($rental, $owner);
        $schedule = $rental->fresh()->exchangeSchedule;
        $this->travelTo($schedule->scheduled_at->addMinute());
        Livewire::actingAs($owner)->test(ExchangeSchedule::class, ['rental' => $rental])
            ->call('confirm', $schedule->id)->assertHasErrors(['schedule'])->assertSee('This proposed time has passed');
        $this->assertNull($schedule->fresh()->owner_confirmed_at);
    }

    public function test_schedule_panel_is_present_on_both_booking_pages(): void
    {
        [$owner, $renter, $rental] = $this->booking('delivery');
        foreach ([$owner, $renter] as $party) {
            $interface = $party->id === $owner->id ? 'owner' : 'renter';
            $this->actingAs($party)->get(route($interface.'.rentals.show', $rental))->assertOk()
                ->assertSee('Delivery arrangements')->assertSee('Propose pickup or delivery schedule');
        }
    }
}
