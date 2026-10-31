<?php

// Test-only fixtures for the module-level Selenium specs. Run by tests/e2e/run.sh against the
// throwaway SQLite DB (never MySQL). Each scenario is created through the app's own services
// (agreement, payments, lifecycle) so the states are real, then the specs drive the UI from there.
// Writes the created ids to tests/e2e/artifacts/fixtures.json for the specs to read.

use App\Enums\ListingStatus;
use App\Enums\RentalRequestStatus;
use App\Models\Category;
use App\Models\CommissionSetting;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\OfflinePaymentSetting;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\RentalAgreement;
use App\Services\RentalLifecycle;
use App\Services\RentalPayments;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (config('database.default') !== 'sqlite') {
    fwrite(STDERR, "Refusing to seed fixtures: DB_CONNECTION is not sqlite.\n");
    exit(1);
}

$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
Storage::disk('public')->put('listings/fx.png', $png);

// email_verified_at is not mass-assignable, so it is set with forceFill.
$user = fn (string $email, string $name) => tap(User::firstOrNew(['email' => $email]))->forceFill([
    'name' => $name, 'role' => 'member', 'status' => 'active',
    'email_verified_at' => now(), 'password' => Hash::make('password'),
])->save() ? User::where('email', $email)->first() : null;

$admin = User::where('email', 'admin@tala.test')->firstOrFail();
$owner = $user('fx.owner@tala.test', 'Fixture Owner');
$renter = $user('fx.renter@tala.test', 'Fixture Renter');
$renter2 = $user('fx.renter2@tala.test', 'Fixture Renter Two');
$lockout = $user('fx.lockout@tala.test', 'Fixture Lockout');
$suspend = $user('fx.suspend@tala.test', 'Fixture Suspend');

OfflinePaymentSetting::updateOrCreate(['id' => 1], [
    'method' => 'bank_transfer', 'enabled' => true, 'updated_by' => $admin->id,
    'instructions' => 'QA: Transfer the full amount to BDO 0000-1111-2222 (Tala QA) and upload the receipt.',
]);
$commission = (float) CommissionSetting::current()->commission_rate;
$categoryId = Category::whereNull('parent_id')->orderBy('id')->value('id');

$listing = function (string $name, float $price, float $deposit = 1000) use ($owner, $categoryId) {
    $listing = Listing::create([
        'owner_id' => $owner->id, 'category_id' => $categoryId, 'name' => $name,
        'brand' => 'QA', 'model' => 'Fixture', 'condition' => 'good',
        'description' => "Fixture item {$name} for module tests.",
        'price_per_day' => $price, 'security_deposit' => $deposit,
        'location' => 'Nasugbu, Batangas', 'latitude' => 14.0723, 'longitude' => 120.6328,
        'pickup_available' => true, 'delivery_available' => false,
        'rental_rules' => 'Return clean.', 'max_rental_duration_days' => 7, 'is_available' => true,
        'available_from' => today()->toDateString(), 'available_until' => today()->addDays(60)->toDateString(),
        'status' => ListingStatus::Published,
    ]);
    ListingImage::create(['listing_id' => $listing->id, 'path' => 'listings/fx.png', 'sort_order' => 0]);

    return $listing;
};

$request = function (Listing $listing, User $renter, int $startIn, int $days) use ($commission) {
    $fee = round((float) $listing->price_per_day * $days, 2);
    $commissionAmount = round($fee * $commission / 100, 2);

    return RentalRequest::create([
        'listing_id' => $listing->id, 'renter_id' => $renter->id,
        'start_date' => today()->addDays($startIn)->toDateString(),
        'end_date' => today()->addDays($startIn + $days - 1)->toDateString(),
        'rental_days' => $days, 'fulfillment_method' => 'pickup',
        'rental_fee' => $fee, 'commission_rate' => $commission, 'commission_amount' => $commissionAmount,
        'security_deposit' => $listing->security_deposit,
        'total_amount' => round($fee + $commissionAmount + (float) $listing->security_deposit, 2),
        'status' => RentalRequestStatus::Requested,
    ]);
};

$approve = function (RentalRequest $request) {
    $request->update([
        'status' => RentalRequestStatus::Approved,
        'agreement_terms' => RentalAgreement::termsFor($request->fresh('listing')),
    ]);

    return $request->fresh();
};

$book = function (RentalRequest $request) use ($approve, $owner, $renter): Rental {
    $request = $approve($request);
    RentalAgreement::accept($request, $request->renter);
    return RentalAgreement::accept($request->fresh(), $owner);
};

$proof = function () use ($png) {
    $path = tempnam(sys_get_temp_dir(), 'fxproof').'.png';
    file_put_contents($path, $png);

    return new UploadedFile($path, 'proof.png', 'image/png', null, true);
};

$pay = function (Rental $rental, string $reference) use ($proof, $admin) {
    $submission = RentalPayments::submit($rental, $rental->renter, $reference, $proof());
    RentalPayments::review($rental, $admin, $submission->id, true, 'QA fixture: funds received.', true);

    return $rental->fresh();
};

// Browse / search module: two items with distinct names and prices.
$tent = $listing('FX Camping Tent', 300);
$drone = $listing('FX Camera Drone', 1500);

// Listings module: an item the owner edits, pauses and reactivates.
$ladder = $listing('FX Aluminum Ladder', 250);

// Requests module: decline, overlap auto-decline and renter cancellation, all on one item.
$projector = $listing('FX Projector', 400);
$reqDecline = $request($projector, $renter, 5, 2);
$reqOverlapA = $request($projector, $renter, 10, 3);
$reqOverlapB = $request($projector, $renter2, 11, 3);
$reqCancel = $request($projector, $renter2, 20, 2);

// Agreement module: approved, waiting for both parties to accept.
$speaker = $listing('FX Party Speaker', 350);
$reqAgree = $approve($request($speaker, $renter, 3, 2));

// Payments module: booking confirmed, no payment yet.
$sony = $listing('FX Mirrorless Camera', 500);
$rentalPay = $book($request($sony, $renter, 4, 2));

// Cancellation module: paid bookings starting tomorrow (fee) and in 10 days (free).
$bike = $listing('FX Mountain Bike', 600);
$rentalCancelFee = $pay($book($request($bike, $renter, 1, 2)), 'FXPAYBIKE');
$kayak = $listing('FX Kayak', 700);
$rentalCancelFree = $pay($book($request($kayak, $renter, 10, 2)), 'FXPAYKAYAK');

// Return / damage / dispute module: an active rental (paid and handed over).
$guitar = $listing('FX Acoustic Guitar', 450);
$rentalReturn = $pay($book($request($guitar, $renter, 1, 2)), 'FXPAYGUITAR');
RentalLifecycle::confirmPickup($rentalReturn, $owner);
RentalLifecycle::confirmPickup($rentalReturn->fresh(), $renter);

// Messaging and admin moderation modules.
$tripod = $listing('FX Camera Tripod', 150);
$suspicious = $listing('FX Suspicious Listing', 99);

$ids = [
    'users' => [
        'owner' => $owner->only('id', 'email', 'name'), 'renter' => $renter->only('id', 'email', 'name'),
        'renter2' => $renter2->only('id', 'email', 'name'), 'lockout' => $lockout->only('id', 'email'),
        'suspend' => $suspend->only('id', 'email', 'name'),
    ],
    'listings' => collect(compact('tent', 'drone', 'ladder', 'projector', 'speaker', 'sony', 'bike', 'kayak', 'guitar', 'tripod', 'suspicious'))
        ->map(fn ($l) => ['id' => $l->id, 'name' => $l->name])->all(),
    'requests' => collect(compact('reqDecline', 'reqOverlapA', 'reqOverlapB', 'reqCancel', 'reqAgree'))->map->id->all(),
    'rentals' => [
        'pay' => $rentalPay->id, 'cancelFee' => $rentalCancelFee->id,
        'cancelFree' => $rentalCancelFree->id, 'return' => $rentalReturn->id,
    ],
];

$out = $root.'/tests/e2e/artifacts/fixtures.json';
@mkdir(dirname($out), 0777, true);
file_put_contents($out, json_encode($ids, JSON_PRETTY_PRINT));
echo "Fixtures ready: ".count($ids['listings'])." listings, ".count($ids['requests'])." requests, ".count($ids['rentals'])." rentals\n";
