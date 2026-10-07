<?php

namespace Tests\Feature;

use App\Enums\DisputeResolution;
use App\Enums\DisputeStatus;
use App\Enums\ListingStatus;
use App\Enums\RentalStatus;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Member\Dashboard as MemberDashboard;
use App\Livewire\Owner\Rentals\Index as OwnerRentalsIndex;
use App\Livewire\Owner\Rentals\Show as OwnerRentalShow;
use App\Models\Category;
use App\Models\ConditionRecord;
use App\Models\DamageReport;
use App\Models\Dispute;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\RentalLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withSession(['active_interface' => 'owner']);
    }

    private function listingFor(User $owner): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'tools'], ['name' => 'Tools']);

        return Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Pressure Washer',
            'description' => 'x',
            'condition' => 'good',
            'price_per_day' => 100,
            'security_deposit' => 500,
            'location' => 'Manila',
            'max_rental_duration_days' => 10,
            'status' => ListingStatus::Published,
            'is_available' => true,
            'pickup_available' => true,
        ]);
    }

    private function paidRental(User $owner, User $renter, Listing $listing, RentalStatus $status): Rental
    {
        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(7),
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 500,
            'total_amount' => 830,
            'status' => 'approved',
        ]);

        return Rental::create([
            'rental_request_id' => $request->id,
            'listing_id' => $listing->id,
            'owner_id' => $owner->id,
            'renter_id' => $renter->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 500,
            'total_amount' => 830,
            'status' => $status,
            'paid_at' => now()->subDays(9),
        ]);
    }

    public function test_owner_earnings_count_rentals_that_have_progressed_past_paid(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);

        // These are all "paid" in the sense that matters for earnings, even
        // though only one of them has the literal status Paid — this is the
        // exact undercount bug this phase fixed.
        $this->paidRental($owner, $renter, $listing, RentalStatus::Paid);
        $this->paidRental($owner, $renter, $listing, RentalStatus::Active);
        $this->paidRental($owner, $renter, $listing, RentalStatus::Completed);

        Livewire::actingAs($owner)->test(MemberDashboard::class)->assertViewHas('totalEarnings', 900.0);
    }

    public function test_owner_dashboard_identifies_most_rented_and_most_profitable_listing(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $popularListing = $this->listingFor($owner);
        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
        $otherListing = Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Projector',
            'description' => 'x',
            'condition' => 'good',
            'price_per_day' => 50,
            'security_deposit' => 200,
            'location' => 'Manila',
            'max_rental_duration_days' => 10,
            'status' => ListingStatus::Published,
            'is_available' => true,
            'pickup_available' => true,
        ]);

        $this->paidRental($owner, $renter, $popularListing, RentalStatus::Completed);
        $this->paidRental($owner, $renter, $popularListing, RentalStatus::Completed);
        $this->paidRental($owner, $renter, $otherListing, RentalStatus::Completed);

        $component = Livewire::actingAs($owner)->test(MemberDashboard::class);

        $this->assertSame($popularListing->id, $component->viewData('mostRentedListing')->id);
        $this->assertSame($popularListing->id, $component->viewData('mostProfitableListing')->id);
    }

    public function test_paid_cancellations_reconcile_totals_trends_and_listing_revenue(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(15));
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);

        $freeCancellation = $this->paidRental($owner, $renter, $listing, RentalStatus::Paid);
        $freeCancellation->update(['start_date' => now()->addDays(10)]);
        $chargedCancellation = $this->paidRental($owner, $renter, $listing, RentalStatus::Paid);
        $chargedCancellation->update(['start_date' => now()->startOfDay()->addDay()]);

        $this->actingAs($renter);
        RentalLifecycle::cancel($freeCancellation, 'Plans changed');
        RentalLifecycle::cancel($chargedCancellation, 'Plans changed');

        $unpaid = $this->paidRental($owner, $renter, $listing, RentalStatus::Cancelled);
        $unpaid->update(['paid_at' => null, 'cancellation_fee' => 60]);
        $legacyCancellation = $this->paidRental($owner, $renter, $listing, RentalStatus::Cancelled);
        $this->assertNull($legacyCancellation->cancellation_fee);

        $component = Livewire::actingAs($owner)->test(MemberDashboard::class)
            ->assertViewHas('totalEarnings', 60.0)
            ->assertViewHas('completedRentalsCount', 0)
            ->assertViewHas('completedRentalEarnings', 0.0);

        $this->assertSame(60.0, $component->viewData('monthlyEarnings')->last()['value']);
        $this->assertSame(60.0, $component->viewData('mostProfitableListing')->revenue);
        $this->assertSame(0.0, $freeCancellation->fresh()->ownerEarnings());
        $this->assertSame(0.0, $unpaid->fresh()->ownerEarnings());
        Livewire::actingAs($owner)->test(OwnerRentalShow::class, ['rental' => $chargedCancellation->fresh()])
            ->assertSee('Net rental earnings')->assertSee('₱60.00');
    }

    public function test_full_rental_refunds_are_excluded_but_open_refund_disputes_are_not(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $paymentRefund = $this->paidRental($owner, $renter, $listing, RentalStatus::Completed);
        Payment::create([
            'rental_id' => $paymentRefund->id,
            'transaction_reference' => Payment::generateReference(),
            'amount' => 830,
            'paid_at' => $paymentRefund->paid_at,
            'status' => 'refunded',
        ]);

        foreach ([DisputeStatus::Resolved, DisputeStatus::Open] as $status) {
            $rental = $this->paidRental($owner, $renter, $listing, RentalStatus::Completed);
            Dispute::create([
                'rental_id' => $rental->id,
                'raised_by' => $renter->id,
                'reason' => 'item_not_as_described',
                'description' => 'Refund requested',
                'status' => $status,
                'resolution' => DisputeResolution::FullRefund,
            ]);
        }

        Livewire::actingAs($owner)->test(MemberDashboard::class)
            ->assertViewHas('totalEarnings', 300.0)
            ->assertViewHas('completedRentalEarnings', 300.0)
            ->assertViewHas('completedRentalsCount', 3);
    }

    public function test_refunding_a_damage_deposit_does_not_reduce_rental_income(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->paidRental($owner, $renter, $listing, RentalStatus::Completed);
        $condition = ConditionRecord::create([
            'rental_id' => $rental->id, 'recorded_by' => $owner->id,
            'type' => 'after', 'condition' => 'good', 'has_damage' => true,
        ]);
        $damage = DamageReport::create([
            'rental_id' => $rental->id, 'condition_record_id' => $condition->id,
            'damage_type' => 'scratch', 'description' => 'Scratch',
            'estimated_repair_cost' => 100, 'proposed_deduction' => 100,
        ]);
        Dispute::create([
            'rental_id' => $rental->id, 'damage_report_id' => $damage->id,
            'raised_by' => $renter->id, 'reason' => 'item_damaged',
            'description' => 'Deposit refund', 'status' => DisputeStatus::Resolved,
            'resolution' => DisputeResolution::FullRefund,
        ]);

        Livewire::actingAs($owner)->test(MemberDashboard::class)
            ->assertViewHas('totalEarnings', 300.0)
            ->assertViewHas('completedRentalEarnings', 300.0);
    }

    public function test_net_earnings_change_the_most_profitable_listing_and_preserve_cents(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $cancelledListing = $this->listingFor($owner);
        $earningListing = $this->listingFor($owner);
        $cancelled = $this->paidRental($owner, $renter, $cancelledListing, RentalStatus::Cancelled);
        $cancelled->update(['rental_fee' => 1000, 'cancellation_fee' => 0]);
        $completed = $this->paidRental($owner, $renter, $earningListing, RentalStatus::Completed);
        $completed->update(['rental_fee' => 123.45, 'late_fee' => 900]);

        $component = Livewire::actingAs($owner)->test(MemberDashboard::class)
            ->assertViewHas('totalEarnings', 123.45);

        $this->assertSame($earningListing->id, $component->viewData('mostProfitableListing')->id);
        $this->assertSame(123.45, $component->viewData('mostProfitableListing')->revenue);
        $this->assertSame(123.45, $component->viewData('monthlyEarnings')->sum('value'));
    }

    public function test_monthly_earnings_restate_the_payment_month_and_handle_month_end(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31)->startOfDay());
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $older = $this->paidRental($owner, $renter, $listing, RentalStatus::Completed);
        $older->update(['paid_at' => '2026-04-30']);
        $may = $this->paidRental($owner, $renter, $listing, RentalStatus::Completed);
        $may->update(['paid_at' => '2026-05-01']);
        $september = $this->paidRental($owner, $renter, $listing, RentalStatus::Cancelled);
        $september->update(['paid_at' => '2026-09-30', 'cancelled_at' => now(), 'cancellation_fee' => 60.25]);

        $component = Livewire::actingAs($owner)->test(MemberDashboard::class)
            ->assertViewHas('totalEarnings', 660.25);
        $months = $component->viewData('monthlyEarnings');

        $this->assertSame(['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'], $months->pluck('label')->all());
        $this->assertSame([300.0, 0.0, 0.0, 0.0, 60.25, 0.0], $months->pluck('value')->all());
        $this->assertSame('₱60.25', $months[4]['display']);
    }

    public function test_completed_summary_is_owner_scoped_and_retains_removed_listings(): void
    {
        $owner = User::factory()->owner()->create();
        $otherOwner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $listing->update(['name' => 'Archived owner item']);
        $completed = collect(range(1, 6))->map(function ($daysAgo) use ($owner, $renter, $listing) {
            $rental = $this->paidRental($owner, $renter, $listing, RentalStatus::Completed);
            $rental->update(['completed_at' => now()->subDays($daysAgo)]);

            return $rental;
        });
        $this->paidRental($owner, $renter, $listing, RentalStatus::Active);
        $otherListing = $this->listingFor($otherOwner);
        $otherListing->update(['name' => 'Private other owner item']);
        $this->paidRental($otherOwner, $renter, $otherListing, RentalStatus::Completed);
        $listing->delete();

        $component = Livewire::actingAs($owner)->test(MemberDashboard::class)
            ->assertViewHas('completedRentalsCount', 6)
            ->assertViewHas('completedRentalEarnings', 1800.0)
            ->assertViewHas('totalEarnings', 2100.0)
            ->assertSee('Recent completed transactions')
            ->assertSee('Archived owner item')
            ->assertDontSee('Private other owner item');

        $this->assertSame($completed->take(5)->pluck('id')->all(), $component->viewData('completedRentals')->pluck('id')->all());
        $this->assertSame($listing->id, $component->viewData('mostProfitableListing')->id);
        Livewire::withQueryParams(['filter' => 'completed'])->actingAs($owner)->test(OwnerRentalsIndex::class)
            ->assertSet('filter', 'completed')
            ->assertViewHas('rentals', fn ($rentals) => $rentals->total() === 6);
    }

    public function test_owner_dashboard_shows_zero_earnings_for_only_cancelled_bookings(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $cancelled = $this->paidRental($owner, $renter, $listing, RentalStatus::Cancelled);
        $cancelled->update(['cancellation_fee' => 0]);

        Livewire::actingAs($owner)->test(MemberDashboard::class)
            ->assertViewHas('totalEarnings', 0.0)
            ->assertViewHas('mostProfitableListing', null)
            ->assertSee('No completed rentals yet');
    }

    public function test_owner_dashboard_has_no_most_rented_listing_when_there_are_no_rentals(): void
    {
        $owner = User::factory()->owner()->create();
        $this->listingFor($owner);

        $component = Livewire::actingAs($owner)->test(MemberDashboard::class);

        $this->assertNull($component->viewData('mostRentedListing'));
        $this->assertNull($component->viewData('mostProfitableListing'));
    }

    public function test_admin_dashboard_aggregates_platform_wide_figures(): void
    {
        $admin = User::factory()->admin()->create();
        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();

        $listingA = $this->listingFor($ownerA);
        $listingB = $this->listingFor($ownerB);

        $this->paidRental($ownerA, $renter, $listingA, RentalStatus::Completed);
        $this->paidRental($ownerB, $renter, $listingB, RentalStatus::Active);
        $this->paidRental($ownerA, $renter, $listingA, RentalStatus::Overdue);

        Dispute::create([
            'rental_id' => Rental::first()->id,
            'raised_by' => $renter->id,
            'reason' => 'item_not_as_described',
            'description' => 'x',
            'status' => DisputeStatus::Open,
        ]);

        $component = Livewire::actingAs($admin)->test(AdminDashboard::class);

        $component->assertViewHas('activeRentalsCount', 1);
        $component->assertViewHas('completedRentalsCount', 1);
        $component->assertViewHas('overdueRentalsCount', 1);
        $component->assertViewHas('openDisputesCount', 1);
        // 3 paid rentals x ₱830 total_amount each.
        $component->assertViewHas('transactionValue', 2490.0);
        // 3 paid rentals x ₱30 commission each.
        $component->assertViewHas('platformRevenue', 90.0);
    }

    public function test_only_admin_can_view_the_admin_dashboard(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get(route('admin.dashboard'))->assertForbidden();
    }
}
