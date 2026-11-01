<?php

namespace Tests\Feature;

use App\Livewire\Owner\Listings\Form;
use App\Livewire\Owner\Listings\Index as OwnerListings;
use App\Livewire\Owner\Payments\Settings;
use App\Livewire\Owner\RentalRequests\Index as OwnerRequests;
use App\Livewire\Owner\Rentals\Show as OwnerRental;
use App\Livewire\RentalRequests\Agreement;
use App\Livewire\RentalRequests\Create;
use App\Livewire\Rentals\ExchangeSchedule;
use App\Livewire\Renter\Cart\Index as Cart;
use App\Livewire\Renter\Rentals\Show as RenterRental;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\FaceComparison;
use App\Services\ManualIdentityReview;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesPaymentProof;
use Tests\TestCase;

class PhaseSevenLifecycleTest extends TestCase
{
    use CreatesPaymentProof;
    use RefreshDatabase;

    public static function journeys(): array
    {
        return ['hourly pickup direct request' => ['hour', 'pickup', false],
            'daily delivery through cart' => ['day', 'delivery', true]];
    }

    private function walletQr(): UploadedFile
    {
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $png = "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', 1, 1, 8, 6, 0, 0, 0))
            .$chunk('IDAT', gzcompress("\0\0\0\0\xff"))
            .$chunk('IEND', '');

        return UploadedFile::fake()->createWithContent('synthetic-wallet.png', $png);
    }

    private function signIn(User $user, string $interface): void
    {
        auth()->logout();
        Volt::test('pages.auth.login')->set('form.email', $user->email)->set('form.password', 'password')
            ->call('login')->assertHasNoErrors()->assertSet('showInterfacePicker', true)
            ->call('chooseInterface', $interface)->assertHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->get(route($interface.'.dashboard'))->assertOk();
    }

    #[DataProvider('journeys')]
    public function test_verified_member_can_complete_the_entire_rental_journey(string $unit, string $method, bool $cart): void
    {
        // Frozen future time must not make Livewire delete newly uploaded test files as expired.
        config(['livewire.temporary_file_upload.cleanup' => false]);
        $this->travelTo(Carbon::parse('2026-11-05 09:00', 'Asia/Manila'));
        Storage::fake('public');
        Storage::fake('local');
        $this->mock(FaceComparison::class)->shouldReceive('compare')->andReturn(0.8);
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        foreach ([$owner, $renter] as $member) {
            $submission = app(ManualIdentityReview::class)->submit($member,
                UploadedFile::fake()->image('synthetic-front.jpg', 640, 480), null, UploadedFile::fake()->image('synthetic-selfie.jpg', 640, 480), true);
            app(ManualIdentityReview::class)->review($admin, $submission->id, 'approved', 'Synthetic QA documents', true, true, true);
            $this->assertTrue($member->fresh()->hasVerifiedIdentity());
            $this->assertSame(2, $member->notifications()->where('data->type', 'verification_update')->count());
        }
        $this->signIn($owner, 'owner');
        $category = Category::create(['name' => 'Journey gear', 'slug' => 'journey-gear']);
        Livewire::test(Form::class)->set('category_id', $category->id)->set('name', 'Phase seven camera')
            ->set('description', 'Synthetic QA item with case')->set('price_per_day', 200)->set('price_per_hour', 40)
            ->set('security_deposit', 500)->set('location', 'Manila')->set('latitude', 14.6)->set('longitude', 120.98)
            ->set('pickup_available', true)->set('delivery_available', true)
            ->set('available_from', '2026-11-05')->set('available_until', '2026-12-05')
            ->set('photoBatch', [UploadedFile::fake()->image('synthetic-item.jpg')])->call('save')->assertHasNoErrors();
        $listing = Listing::sole();
        $this->assertSame('published', $listing->status->value);
        $wallet = Livewire::test(Settings::class)->set('account_name', 'Synthetic QA wallet')->set('provider', 'gcash')
            ->set('qr_image', $this->walletQr())->set('enabled', true)->call('save');
        $this->assertSame([], $wallet->errors()->all(), json_encode($wallet->failedRules()));
        $this->signIn($renter, 'renter');
        $this->get(route('listings.index', ['q' => 'Phase seven camera']))->assertOk()->assertSee('Phase seven camera');
        $form = Livewire::test(Create::class, ['listing' => $listing])->set('rental_unit', $unit)
            ->set('start_date', '2026-11-08')->set('end_date', $unit === 'hour' ? '2026-11-08' : '2026-11-10')
            ->set('start_time', '10:00')->set('end_time', '12:00')->set('fulfillment_method', $method);
        if ($cart) {
            $form->call('addToCart')->assertHasNoErrors();
            Livewire::test(Cart::class)->set('accept_terms', true)->call('checkout')->assertHasNoErrors();
            $this->assertDatabaseCount('cart_items', 0);
        } else {
            $form->set('accept_terms', true)->call('submit')->assertHasNoErrors();
        }
        $request = RentalRequest::sole();
        $this->assertSame('requested', $request->status->value);
        $this->assertEquals($unit === 'hour' ? 80 : 600, $request->rental_fee);
        $this->signIn($owner, 'owner');
        Livewire::test(OwnerRequests::class)->call('startApproving', $request->id)->call('approve', $request->id)->assertHasNoErrors();
        $this->assertSame('awaiting_agreement', $request->fresh()->status->value);
        Livewire::test(Agreement::class, ['rentalRequest' => $request->fresh()])->set('accept_terms', true)->call('acceptTerms')->assertHasNoErrors();
        $this->assertDatabaseCount('rentals', 0);
        $this->signIn($renter, 'renter');
        Livewire::test(Agreement::class, ['rentalRequest' => $request->fresh()])->set('accept_terms', true)->call('acceptTerms')->assertHasNoErrors();
        $rental = Rental::sole();
        $this->assertSame('approved', $request->fresh()->status->value);
        Livewire::test(RenterRental::class, ['rental' => $rental])->set('payment_reference', 'PHASE7-SYNTHETIC')
            ->set('payment_proof', $this->paymentProof())->call('submitPaymentProof')->assertHasNoErrors();
        $this->assertSame('payment_pending', $rental->fresh()->status->value);
        $proof = $rental->paymentSubmissions()->sole();
        $this->signIn($owner, 'owner');
        Livewire::test(OwnerRental::class, ['rental' => $rental])->set('owner_funds_received', true)
            ->call('confirmPaymentReceipt', $proof->id)->assertHasNoErrors();
        $this->assertSame('paid', $rental->fresh()->status->value);
        $this->assertEquals($rental->total_amount, $rental->fresh()->payment->amount);
        Livewire::test(ExchangeSchedule::class, ['rental' => $rental])->call('startProposal')
            ->set('exchange_time', '2026-11-08T10:00')->set('address', 'Synthetic QA meeting point, Manila')->call('propose')->assertHasNoErrors();
        $scheduleId = $rental->fresh()->exchangeSchedule->id;
        Livewire::test(ExchangeSchedule::class, ['rental' => $rental])->call('confirm', $scheduleId)->assertHasNoErrors();
        $this->signIn($renter, 'renter');
        Livewire::test(ExchangeSchedule::class, ['rental' => $rental])->call('confirm', $scheduleId)->assertHasNoErrors();
        $this->assertNotNull($rental->fresh()->exchangeSchedule->confirmed_at);
        $this->travelTo(Carbon::parse('2026-11-08 10:00', 'Asia/Manila'));
        $this->signIn($owner, 'owner');
        Livewire::test(OwnerRental::class, ['rental' => $rental])->set('before_condition', 'good')->call('recordBeforeCondition')->assertHasNoErrors();
        Livewire::test(OwnerRental::class, ['rental' => $rental])->call('advanceFulfillment', 'packed')->assertHasNoErrors();
        if ($method === 'delivery') {
            Livewire::test(OwnerRental::class, ['rental' => $rental])->call('advanceFulfillment', 'waiting_for_courier')->call('advanceFulfillment', 'shipped')->assertHasNoErrors();
        }
        Livewire::test(OwnerRental::class, ['rental' => $rental])->call('confirmPickup')->assertHasNoErrors();
        $this->signIn($renter, 'renter');
        Livewire::test(RenterRental::class, ['rental' => $rental])->call('confirmPickup')->assertHasNoErrors();
        $this->assertSame('active', $rental->fresh()->status->value);
        $this->assertFalse($listing->fresh()->acceptsNewRequests());
        $this->travelTo(Carbon::parse(($unit === 'hour' ? '2026-11-08' : '2026-11-10').' 12:00', 'Asia/Manila'));
        Livewire::test(RenterRental::class, ['rental' => $rental])->call('confirmReturn')->assertHasNoErrors();
        $this->signIn($owner, 'owner');
        Livewire::test(OwnerRental::class, ['rental' => $rental])->call('confirmReturn')->set('after_condition', 'good')->call('recordAfterCondition')->assertHasNoErrors();
        $this->assertSame('completed', $rental->fresh()->status->value);
        $this->assertSame('return_eligible', $rental->fresh()->securityDeposit->status->value);
        $this->assertFalse($listing->fresh()->acceptsNewRequests());
        Livewire::test(OwnerRental::class, ['rental' => $rental])->call('releaseDeposit')->set('renter_rating', 5)->call('submitRenterReview')->assertHasNoErrors();
        $this->signIn($renter, 'renter');
        Livewire::test(RenterRental::class, ['rental' => $rental])->call('confirmDepositReceipt')
            ->set('owner_rating', 5)->call('submitOwnerReview')->set('listing_rating', 4)->call('submitListingReview')->assertHasNoErrors();
        $this->assertNotNull($rental->fresh()->securityDeposit->received_at);
        $this->assertDatabaseCount('reviews', 3);
        $this->signIn($owner, 'owner');
        Livewire::test(OwnerListings::class)->call('setAvailability', $listing->id, true)->assertHasNoErrors();
        $this->assertTrue($listing->fresh()->acceptsNewRequests());
        $this->assertSame(1, $renter->notifications()->where('data->type', 'booking_confirmed')->count());
        $this->assertSame(1, $renter->notifications()->where('data->type', 'payment_confirmed')->count());
        Volt::test('layout.sidebar')->call('logout')->assertRedirect('/');
        $this->assertGuest();
        $this->get(route('owner.rentals.show', $rental))->assertRedirect(route('login'));
    }
}
