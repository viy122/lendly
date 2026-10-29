<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalStatus;
use App\Livewire\Admin\Listings\Index as AdminListings;
use App\Livewire\Listings\Show as ListingShow;
use App\Livewire\Owner\Listings\Form;
use App\Livewire\Owner\Listings\Index as OwnerListings;
use App\Livewire\RentalRequests\Create;
use App\Livewire\Renter\Cart\Index as Cart;
use App\Models\Category;
use App\Models\Listing;
use App\Models\OfflinePaymentSetting;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\ListingPublication;
use App\Services\RentalAgreement;
use App\Services\RentalCart;
use App\Services\RentalLifecycle;
use App\Services\RentalPayments;
use App\Services\RentalRequestSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class IdentityApprovalGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_alone_cannot_preview_or_submit_a_request(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->create();

        $this->rejects(fn () => RentalRequestSubmission::preview($renter, $listing->id, $this->details()), 'identity');
        $this->rejects(fn () => RentalRequestSubmission::submit($renter, $listing->id, $this->details(), RentalRequestSubmission::termsFor($listing), true), 'identity');
        $this->assertDatabaseCount('rental_requests', 0);
    }

    public function test_request_submission_checks_fresh_approval_for_both_participants(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();
        User::whereKey($renter->id)->update(['identity_verified_at' => null]);
        $this->rejects(fn () => RentalRequestSubmission::preview($renter, $listing->id, $this->details()), 'identity');

        User::whereKey($renter->id)->update(['identity_verified_at' => now()]);
        User::whereKey($listing->owner_id)->update(['identity_verified_at' => null]);
        $this->rejects(fn () => RentalRequestSubmission::submit($renter, $listing->id, $this->details(), RentalRequestSubmission::termsFor($listing), true), 'listing');
        $this->assertDatabaseCount('rental_requests', 0);

        User::whereKey($listing->owner_id)->update(['identity_verified_at' => now()]);
        $request = RentalRequestSubmission::submit($renter, $listing->id, $this->details(), RentalRequestSubmission::termsFor($listing), true);
        $this->assertSame($renter->id, $request->renter_id);
        $this->assertDatabaseCount('rental_requests', 1);
    }

    public function test_request_livewire_action_cannot_bypass_approval(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->create();

        Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])
            ->set('accept_terms', true)->call('submit')->assertHasErrors(['identity']);
        $this->assertDatabaseCount('rental_requests', 0);
    }

    public function test_unapproved_members_can_save_cart_drafts_but_cannot_checkout(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->create();
        $item = RentalCart::save($renter, $listing->id, $this->details());

        $this->rejects(fn () => RentalCart::checkout($renter, [$item->id], true), 'identity');
        Livewire::actingAs($renter)->test(Cart::class)->assertSee(route('identity.verify'), false)
            ->set('accept_terms', true)->call('checkout')->assertHasErrors(['identity']);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseCount('rental_requests', 0);
    }

    public function test_unapproved_owner_cannot_validate_or_reactivate_a_listing(): void
    {
        $owner = User::factory()->create();
        $listing = $this->listing($owner, ['status' => ListingStatus::Inactive]);

        $this->rejects(fn () => ListingPublication::validate($listing), 'identity');
        Livewire::actingAs($owner)->test(OwnerListings::class)->call('reactivate', $listing->id)->assertHasErrors(['identity']);
        $this->assertSame(ListingStatus::Inactive, $listing->fresh()->status);
    }

    public function test_listing_form_cannot_create_or_edit_without_fresh_approval(): void
    {
        Storage::fake('public');
        $owner = User::factory()->identityVerified()->create();
        $listing = $this->listing($owner);
        User::whereKey($owner->id)->update(['identity_verified_at' => null]);

        Livewire::actingAs($owner)->test(Form::class)->assertSee(route('identity.verify'), false)
            ->call('save')->assertHasErrors(['identity']);
        Livewire::actingAs($owner)->test(Form::class, ['listing' => $listing])->set('name', 'Changed name')
            ->call('save')->assertHasErrors(['identity']);
        $this->assertDatabaseCount('listings', 1);
        $this->assertSame('Camera', $listing->fresh()->name);
    }

    public function test_approved_owner_can_publish_a_new_listing(): void
    {
        Storage::fake('public');
        $owner = User::factory()->identityVerified()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);

        Livewire::actingAs($owner)->test(Form::class)->set('category_id', $category->id)->set('name', 'Camera')
            ->set('description', 'A working camera')->set('price_per_day', 100)->set('location', 'Manila')
            ->set('latitude', 0)->set('longitude', 0)->set('available_from', now()->addDay()->toDateString())
            ->set('available_until', now()->addDays(10)->toDateString())->set('photos', [$this->photo()])
            ->call('save')->assertHasNoErrors()->assertRedirect(route('owner.listings.index'));
        $this->assertSame(ListingStatus::Published, Listing::sole()->status);
    }

    public function test_admin_publication_requires_owner_approval_but_not_admin_identity(): void
    {
        $owner = User::factory()->create();
        $listing = $this->listing($owner, ['status' => ListingStatus::PendingApproval]);
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(AdminListings::class)->call('approve', $listing->id)->assertHasErrors(['identity']);
        $this->assertSame(ListingStatus::PendingApproval, $listing->fresh()->status);
        $owner->forceFill(['identity_verified_at' => now(), 'identity_verification_environment' => 'production'])->save();
        Livewire::actingAs($admin)->test(AdminListings::class)->call('approve', $listing->id)->assertHasNoErrors();
        $this->assertSame(ListingStatus::Published, $listing->fresh()->status);
    }

    public function test_listing_browsing_shows_approval_guidance_without_hiding_the_item(): void
    {
        $listing = $this->listing();
        Livewire::actingAs(User::factory()->create())->test(ListingShow::class, ['listing' => $listing])
            ->assertSee('Camera')->assertSee(route('identity.verify'), false)->assertSee('Verify your identity');
    }

    public function test_existing_payment_and_return_obligations_remain_available_without_approval(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listing($owner);
        $request = RentalRequest::create([
            'listing_id' => $listing->id, 'renter_id' => $renter->id, ...$this->details(),
            'rental_days' => 1, 'rental_fee' => 100, 'commission_rate' => 10, 'commission_amount' => 10,
            'security_deposit' => 0, 'total_amount' => 110, 'status' => 'approved',
            'agreement_terms' => ['item_name' => 'Camera'], 'owner_terms_accepted_at' => now(), 'renter_terms_accepted_at' => now(),
        ]);
        $rental = Rental::create([
            'rental_request_id' => $request->id, 'listing_id' => $listing->id,
            'owner_id' => $owner->id, 'renter_id' => $renter->id, ...$this->details(),
            'rental_days' => 1, 'rental_fee' => 100, 'commission_rate' => 10, 'commission_amount' => 10,
            'security_deposit' => 0, 'total_amount' => 110,
        ]);
        OfflinePaymentSetting::create(['enabled' => true, 'method' => 'bank_transfer', 'instructions' => 'Pay the platform account.']);
        $this->assertNotNull(RentalPayments::destinationFor($rental, $renter));
        $this->actingAs($renter)->get(route('renter.rentals.show', $rental))->assertOk();

        $rental->update(['status' => RentalStatus::Active]);
        RentalLifecycle::confirmReturn($rental, $owner);
        $this->assertSame(RentalStatus::Returned, $rental->fresh()->status);
    }

    private function listing(?User $owner = null, array $overrides = []): Listing
    {
        $owner ??= User::factory()->identityVerified()->create();
        $category = Category::firstOrCreate(['slug' => 'tools'], ['name' => 'Tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id, 'name' => 'Camera',
            'description' => 'A working camera', 'condition' => 'good', 'location' => 'Manila',
            'latitude' => 0, 'longitude' => 0, 'price_per_day' => 100, 'max_rental_duration_days' => 10,
            'available_from' => now()->addDay()->toDateString(), 'available_until' => now()->addDays(10)->toDateString(),
            'status' => ListingStatus::Published, ...$overrides,
        ]);
        $listing->images()->create(['path' => 'listings/camera.png', 'sort_order' => 0]);

        return $listing;
    }

    public function test_a_previously_approved_request_cannot_start_a_new_booking_after_identity_is_revoked(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();
        $request = RentalRequestSubmission::submit($renter, $listing->id, $this->details(), RentalRequestSubmission::termsFor($listing), true);
        $request->update(['status' => 'approved', 'agreement_terms' => RentalAgreement::termsFor($request), 'owner_terms_accepted_at' => now()]);
        User::whereKey($renter->id)->update(['identity_verified_at' => null]);
        $this->rejects(fn () => RentalAgreement::accept($request, $renter), 'identity');
        $this->assertDatabaseCount('rentals', 0);
    }

    private function details(): array
    {
        return ['start_date' => now()->addDays(3)->toDateString(), 'end_date' => now()->addDays(3)->toDateString(), 'fulfillment_method' => 'pickup'];
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('camera.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aDd8AAAAASUVORK5CYII='));
    }

    private function rejects(callable $operation, string $field): void
    {
        try {
            $operation();
            $this->fail('Unapproved identity must not authorize a new rental or listing.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}
