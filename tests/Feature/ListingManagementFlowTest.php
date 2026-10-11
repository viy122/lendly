<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalRequestStatus;
use App\Livewire\Listings\Browse;
use App\Livewire\Listings\Map;
use App\Livewire\Listings\Show as ListingShow;
use App\Livewire\Owner\Listings\Form;
use App\Livewire\Owner\Listings\Index;
use App\Livewire\Owner\RentalRequests\Index as OwnerRequests;
use App\Livewire\RentalRequests\Create;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\RentalAgreement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ListingManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    private function photo(string $name = 'item.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aDd8AAAAASUVORK5CYII='
        ));
    }

    private function listing(User $owner, array $overrides = []): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'tools'], ['name' => 'Tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Camera',
            'description' => 'Camera with a carrying case.',
            'condition' => 'good',
            'price_per_day' => 100,
            'location' => 'Manila',
            'latitude' => 0,
            'longitude' => 0,
            'available_from' => now()->addDays(3)->toDateString(),
            'available_until' => now()->addDays(10)->toDateString(),
            'max_rental_duration_days' => 10,
            'status' => ListingStatus::Published,
            ...$overrides,
        ]);
        Storage::disk('public')->put('listings/original.png', $this->photo()->getContent());
        $listing->images()->create(['path' => 'listings/original.png', 'sort_order' => 0]);

        return $listing->refresh();
    }

    private function request(Listing $listing, User $renter, string $status = 'requested'): RentalRequest
    {
        return RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 0,
            'total_amount' => 330,
            'status' => $status,
        ]);
    }

    public function test_creation_requires_a_photo_map_pin_and_availability_dates(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);
        Livewire::actingAs($owner)->test(Form::class)
            ->set('category_id', $category->id)->set('name', 'Camera')
            ->set('description', 'A camera')->set('price_per_day', 100)->set('location', 'Manila')
            ->call('save')->assertHasErrors(['photos', 'latitude', 'longitude', 'available_from', 'available_until']);
        $this->assertDatabaseCount('listings', 0);
    }

    public static function invalidFields(): array
    {
        return [
            ['latitude', 91], ['longitude', -181], ['available_from', 'invalid'],
            ['available_until', '2020-01-01'], ['available_until', ''],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_listing_details_cannot_be_saved(string $field, mixed $value): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $listing = $this->listing($owner);
        Livewire::actingAs($owner)->test(Form::class, ['listing' => $listing])
            ->set($field, $value)->call('save')->assertHasErrors([$field]);
        $this->assertSame($listing->getAttributes(), $listing->fresh()->getAttributes());
    }

    public function test_reversed_and_expired_availability_windows_are_rejected(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $listing = $this->listing($owner);
        Livewire::actingAs($owner)->test(Form::class, ['listing' => $listing])
            ->set('available_from', now()->addDays(11)->toDateString())
            ->call('save')->assertHasErrors(['available_until']);
    }

    public function test_edit_keeps_existing_photo_and_zero_coordinates_and_publishes_immediately(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $listing = $this->listing($owner, ['status' => ListingStatus::PendingApproval]);
        Livewire::actingAs($owner)->test(Form::class, ['listing' => $listing])
            ->assertSet('latitude', 0)->assertSet('longitude', 0)
            ->set('name', 'Updated Camera')->call('save')->assertHasNoErrors();
        $listing->refresh();
        $this->assertSame(ListingStatus::Published, $listing->status);
        $this->assertSame('Updated Camera', $listing->name);
        $this->assertCount(1, $listing->images);
        $this->get(route('listings.show', $listing))->assertOk()->assertSee('Available '.$listing->available_from->format('M d, Y'));
    }

    public function test_paused_listing_stays_paused_after_edit_then_resumes_without_admin_approval(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $listing = $this->listing($owner, ['status' => ListingStatus::Inactive]);
        Livewire::actingAs($owner)->test(Form::class, ['listing' => $listing])
            ->set('name', 'Edited while paused')->call('save')->assertHasNoErrors();
        $this->assertSame(ListingStatus::Inactive, $listing->fresh()->status);
        Livewire::actingAs($owner)->test(Index::class)->call('reactivate', $listing->id)->assertHasNoErrors();
        $this->assertSame(ListingStatus::Published, $listing->fresh()->status);
    }

    public function test_incomplete_legacy_listing_must_be_completed_before_resuming(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $listing = $this->listing($owner, ['status' => ListingStatus::Inactive, 'latitude' => null]);
        $listing->images()->delete();
        Livewire::actingAs($owner)->test(Index::class)->call('reactivate', $listing->id)
            ->assertHasErrors(['latitude', 'photos']);
        $this->assertSame(ListingStatus::Inactive, $listing->fresh()->status);
    }

    public function test_last_photo_removal_requires_a_replacement_and_only_commits_on_save(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $listing = $this->listing($owner);
        $image = $listing->images->first();
        $form = Livewire::actingAs($owner)->test(Form::class, ['listing' => $listing])
            ->call('removeExistingImage', $image->id);
        $this->assertDatabaseHas('listing_images', ['id' => $image->id]);
        Storage::disk('public')->assertExists($image->path);
        $form->call('save')->assertHasErrors(['photos']);
        $this->assertDatabaseHas('listing_images', ['id' => $image->id]);
        $form->set('photos', [$this->photo('replacement.png')])->call('save')->assertHasNoErrors();
        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->path);
        $this->assertCount(1, $listing->fresh()->images);
        Storage::disk('public')->assertExists($listing->fresh()->images->first()->path);
    }

    public function test_photo_removal_is_scoped_to_the_authorized_listing(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $listing = $this->listing($owner);
        $other = $this->listing(User::factory()->owner()->create());
        Livewire::actingAs($owner)->test(Form::class, ['listing' => $listing])
            ->call('removeExistingImage', $other->images->first()->id)->assertNotFound();
        $this->assertCount(1, $other->fresh()->images);
    }

    public function test_non_image_upload_cannot_replace_the_last_photo(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $listing = $this->listing($owner);
        Livewire::actingAs($owner)->test(Form::class, ['listing' => $listing])
            ->call('removeExistingImage', $listing->images->first()->id)
            ->set('photos', [UploadedFile::fake()->create('document.pdf', 10, 'application/pdf')])
            ->call('save')->assertHasErrors(['photos.0']);
        $this->assertCount(1, $listing->fresh()->images);
    }

    public function test_request_dates_are_limited_to_the_window_including_both_boundaries(): void
    {
        Storage::fake('public');
        Notification::fake();
        $listing = $this->listing(User::factory()->owner()->create());
        $renter = User::factory()->renter()->create();
        $form = Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->assertSet('start_date', $listing->available_from->toDateString())
            ->set('start_date', now()->addDays(2)->toDateString())->call('submit')->assertHasErrors(['start_date']);
        $this->assertDatabaseCount('rental_requests', 0);
        $form->set('start_date', $listing->available_from->toDateString())
            ->set('end_date', now()->addDays(11)->toDateString())->call('submit')->assertHasErrors(['end_date']);
        $this->assertDatabaseCount('rental_requests', 0);
        $form->set('end_date', $listing->available_until->toDateString())->call('submit')->assertHasNoErrors();
        $this->assertDatabaseCount('rental_requests', 1);
    }

    public function test_request_rechecks_changed_window_and_expired_window_has_no_request_button(): void
    {
        Storage::fake('public');
        $listing = $this->listing(User::factory()->owner()->create());
        $renter = User::factory()->renter()->create();
        $form = Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])->set('accept_terms', true);
        $listing->update(['available_from' => now()->addDays(4)->toDateString()]);
        $form->call('submit')->assertHasErrors(['start_date']);
        $listing->update(['available_until' => now()->subDay()->toDateString()]);
        Livewire::actingAs($renter)->test(ListingShow::class, ['listing' => $listing->fresh()])
            ->assertDontSeeLivewire(Create::class)->assertSee('Currently unavailable');
    }

    public function test_browse_and_map_exclude_expired_dates_and_include_upcoming_windows(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $expired = $this->listing($owner, [
            'name' => 'Expired Camera',
            'available_from' => now()->subDays(3)->toDateString(),
            'available_until' => now()->toDateString(),
        ]);
        $upcoming = $this->listing($owner, ['name' => 'Upcoming Camera']);
        Livewire::test(Browse::class)->assertSee('Upcoming Camera')->assertDontSee('Expired Camera');
        $markers = Livewire::test(Map::class)->get('markers');
        $this->assertSame([$upcoming->id], array_column($markers, 'id'));
        $this->assertFalse($expired->hasRequestableDates());
    }

    public function test_approval_rechecks_the_window_and_removal(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $listing = $this->listing($owner);
        $request = $this->request($listing, User::factory()->renter()->create());
        $listing->update(['available_from' => now()->addDays(4)->toDateString()]);
        Livewire::actingAs($owner)->test(OwnerRequests::class)->call('approve', $request->id)->assertHasErrors(['approve']);
        $this->assertSame(RentalRequestStatus::Requested, $request->fresh()->status);
        $listing->delete();
        Livewire::actingAs($owner)->test(OwnerRequests::class)->call('approve', $request->id)->assertHasErrors(['approve']);
    }

    public function test_availability_edit_cannot_exclude_approved_bookings(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $listing = $this->listing($owner);
        $this->request($listing, User::factory()->renter()->create(), 'approved');
        Livewire::actingAs($owner)->test(Form::class, ['listing' => $listing])
            ->set('available_from', now()->addDays(4)->toDateString())
            ->call('save')->assertHasErrors(['available_until']);
        $this->assertSame(now()->addDays(3)->toDateString(), $listing->fresh()->available_from->toDateString());
    }

    public function test_owner_removal_retains_existing_bookings_photos_and_agreements(): void
    {
        Storage::fake('public');
        Notification::fake();
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listing($owner);
        $request = $this->request($listing, $renter, 'approved');
        $request->update(['agreement_terms' => RentalAgreement::termsFor($request)]);
        Livewire::actingAs($owner)->test(Index::class)->call('delete', $listing->id)->assertHasNoErrors();
        $this->assertSoftDeleted($listing);
        $this->assertSame('Camera', $request->fresh()->listing->name);
        $this->assertCount(1, $request->fresh()->listing->images);
        Storage::disk('public')->assertExists('listings/original.png');
        $this->get(route('listings.show', $listing->id))->assertNotFound();
        RentalAgreement::accept($request, $owner);
        $rental = RentalAgreement::accept($request, $renter);
        $this->assertInstanceOf(Rental::class, $rental);
        $this->assertSame('Camera', $rental->fresh()->listing->name);
        $this->actingAs($renter)->get(route('renter.rentals.show', $rental))->assertOk()->assertSee('Camera');
    }
}
