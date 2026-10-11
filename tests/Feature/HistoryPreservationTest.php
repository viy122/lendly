<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ConditionRecord;
use App\Models\DamageReport;
use App\Models\Dispute;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\Review;
use App\Models\SecurityDeposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HistoryPreservationTest extends TestCase
{
    use RefreshDatabase;

    private function booking(User $owner, User $renter): Rental
    {
        $category = Category::firstOrCreate(['slug' => 'history-tools'], ['name' => 'History tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id,
            'name' => 'Retained camera', 'description' => 'Camera rental.',
            'condition' => 'good', 'price_per_day' => 100, 'security_deposit' => 500,
            'location' => 'Manila', 'max_rental_duration_days' => 10, 'status' => 'published',
        ]);
        $request = RentalRequest::create([
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->subDays(5), 'end_date' => now()->subDays(3),
            'rental_days' => 3, 'fulfillment_method' => 'pickup',
            'rental_fee' => 300, 'commission_rate' => 10, 'commission_amount' => 30,
            'security_deposit' => 500, 'total_amount' => 830, 'status' => 'approved',
        ]);

        return Rental::create(array_merge($request->only([
            'listing_id', 'renter_id', 'start_date', 'end_date', 'rental_days',
            'fulfillment_method', 'rental_fee', 'commission_rate', 'commission_amount',
            'security_deposit', 'total_amount',
        ]), ['rental_request_id' => $request->id, 'owner_id' => $owner->id, 'status' => 'completed', 'paid_at' => now()]));
    }

    public static function parties(): array
    {
        return ['owner closes' => ['owner'], 'renter closes' => ['renter']];
    }

    #[DataProvider('parties')]
    public function test_account_closure_keeps_counterparty_history_and_anonymizes_the_profile(string $party): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $closing = $party === 'owner' ? $owner : $renter;
        $remaining = $party === 'owner' ? $renter : $owner;
        $originalEmail = $closing->email;
        $closing->update(['phone' => '09171234567', 'address' => 'Private address', 'avatar_path' => 'avatars/private.jpg']);
        Storage::disk('public')->put('avatars/private.jpg', 'private avatar');
        DB::table('password_reset_tokens')->insert(['email' => $originalEmail, 'token' => 'unused', 'created_at' => now()]);
        $rental = $this->booking($owner, $renter);
        $payment = Payment::create(['rental_id' => $rental->id, 'transaction_reference' => 'HISTORY-1', 'amount' => 830, 'paid_at' => now()]);
        $deposit = SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 500, 'status' => 'released']);
        $condition = ConditionRecord::create(['rental_id' => $rental->id, 'recorded_by' => $closing->id, 'type' => 'after', 'condition' => 'good']);
        $damage = DamageReport::create(['rental_id' => $rental->id, 'condition_record_id' => $condition->id, 'damage_type' => 'scratch', 'description' => 'Settled scratch', 'estimated_repair_cost' => 50, 'proposed_deduction' => 50, 'status' => 'accepted']);
        $dispute = Dispute::create(['rental_id' => $rental->id, 'raised_by' => $closing->id, 'reason' => 'incorrect_charge', 'description' => 'Settled charge', 'status' => 'resolved']);
        $review = Review::create(['rental_id' => $rental->id, 'type' => 'renter_to_owner', 'rating' => 5, 'comment' => 'Retained review']);
        $message = Message::create(['rental_request_id' => $rental->rental_request_id, 'sender_id' => $closing->id, 'receiver_id' => $remaining->id, 'body' => 'Retained conversation']);

        Volt::actingAs($closing)->test('profile.delete-user-form')
            ->set('password', 'password')->call('deleteUser')->assertHasNoErrors()->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull(User::find($closing->id));
        $this->assertDatabaseHas('rentals', ['id' => $rental->id]);
        foreach ([$payment, $deposit, $condition, $damage, $dispute, $review, $message] as $record) {
            $this->assertNotNull($record->fresh());
        }
        $closed = User::withTrashed()->findOrFail($closing->id);
        $this->assertTrue($closed->trashed());
        $this->assertSame('Deleted account', $closed->name);
        $this->assertNotSame($originalEmail, $closed->email);
        $this->assertNull($closed->phone);
        $this->assertNull($closed->address);
        $this->assertNull($closed->avatar_path);
        $this->assertNull($closed->remember_token);
        $this->assertNull($closed->email_verified_at);
        $this->assertFalse(Hash::check('password', $closed->password));
        Storage::disk('public')->assertMissing('avatars/private.jpg');
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $originalEmail]);
        $this->assertFalse(Auth::attempt(['email' => $originalEmail, 'password' => 'password']));
        User::factory()->create(['email' => $originalEmail]);

        $historyRole = $party === 'owner' ? 'renter' : 'owner';
        $this->actingAs($remaining)->get(route($historyRole.'.rentals.index'))->assertOk()->assertSee('Retained camera');
        $this->get(route($historyRole.'.rentals.show', $rental))->assertOk()->assertSee('Deleted account');
        $this->get(route('messages.index'))->assertOk()->assertSee('Deleted account');
        $this->get(route('rental-requests.chat', $rental->rental_request_id))->assertOk()->assertSee('Retained conversation');
        if ($party === 'owner') {
            $this->assertNull(Listing::find($rental->listing_id));
            $this->get(route('listings.show', $rental->listing_id))->assertNotFound();
        }
    }

    public static function outstandingObligations(): array
    {
        $cases = [];
        foreach (['owner', 'renter'] as $party) {
            foreach (['payment_pending', 'paid', 'active', 'overdue', 'returned'] as $status) {
                $cases[$party.' rental '.$status] = [$party, 'rental', $status];
            }
            foreach (['requested', 'approved'] as $status) {
                $cases[$party.' request '.$status] = [$party, 'request', $status];
            }
            foreach (['held', 'return_eligible', 'damage_claim'] as $status) {
                $cases[$party.' deposit '.$status] = [$party, 'deposit', $status];
            }
            foreach (['pending', 'disputed'] as $status) {
                $cases[$party.' damage '.$status] = [$party, 'damage', $status];
            }
            $cases[$party.' open dispute'] = [$party, 'dispute', 'open'];
        }

        return $cases;
    }

    #[DataProvider('outstandingObligations')]
    public function test_account_closure_is_blocked_until_obligations_are_settled(string $party, string $type, string $status): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter);
        $closing = $party === 'owner' ? $owner : $renter;
        $originalEmail = $closing->email;
        if ($type === 'rental') {
            $rental->update(['status' => $status]);
        } elseif ($type === 'request') {
            $rental->rentalRequest->update(['status' => $status]);
            $rental->delete();
        } elseif ($type === 'deposit') {
            SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 500, 'status' => $status]);
        } elseif ($type === 'damage') {
            DamageReport::create(['rental_id' => $rental->id, 'damage_type' => 'scratch', 'description' => 'Unsettled claim', 'estimated_repair_cost' => 50, 'proposed_deduction' => 50, 'status' => $status]);
        } else {
            Dispute::create(['rental_id' => $rental->id, 'raised_by' => $renter->id, 'reason' => 'incorrect_charge', 'description' => 'Unsettled charge']);
        }

        Volt::actingAs($closing)->test('profile.delete-user-form')
            ->set('password', 'password')->call('deleteUser')->assertHasErrors('account')->assertNoRedirect();

        $this->assertAuthenticatedAs($closing);
        $this->assertDatabaseHas('users', ['id' => $closing->id, 'email' => $originalEmail]);
        $this->assertDatabaseHas('listings', ['id' => $rental->listing_id, 'status' => 'published']);
    }

    public function test_closed_accounts_cannot_use_an_existing_authenticated_session(): void
    {
        $user = User::factory()->create();
        Volt::actingAs($user)->test('profile.delete-user-form')->set('password', 'password')->call('deleteUser');

        $this->actingAs(User::withTrashed()->findOrFail($user->id))->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
