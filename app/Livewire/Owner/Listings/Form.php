<?php

namespace App\Livewire\Owner\Listings;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Services\ListingPublication;
use App\Services\MarketInsight;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Form extends Component
{
    use WithFileUploads;

    public ?Listing $listing = null;

    public ?int $category_id = null;

    public ?int $subcategory_id = null;

    public string $name = '';

    public string $brand = '';

    public string $model = '';

    public string $description = '';

    public string $condition = 'good';

    public ?int $purchase_year = null;

    public ?float $estimated_original_price = null;

    public ?float $price_per_day = null;

    public ?float $price_per_hour = null;

    public ?float $price_per_week = null;

    public float $security_deposit = 0;

    public string $location = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public bool $pickup_available = true;

    public bool $delivery_available = false;

    public string $rental_rules = '';

    public ?int $max_rental_duration_days = 7;

    public bool $is_available = true;

    public string $available_from = '';

    public string $available_until = '';

    #[Locked]
    public array $removedImageIds = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $photos = [];

    public function mount(?Listing $listing = null): void
    {
        if ($listing?->exists) {
            $this->authorize('update', $listing);

            $this->listing = $listing;
            $this->category_id = $listing->category_id;
            $this->subcategory_id = $listing->subcategory_id;
            $this->name = $listing->name;
            $this->brand = (string) $listing->brand;
            $this->model = (string) $listing->model;
            $this->description = $listing->description;
            $this->condition = $listing->condition->value;
            $this->purchase_year = $listing->purchase_year;
            $this->estimated_original_price = $listing->estimated_original_price ? (float) $listing->estimated_original_price : null;
            $this->price_per_day = (float) $listing->price_per_day;
            $this->price_per_hour = $listing->price_per_hour ? (float) $listing->price_per_hour : null;
            $this->price_per_week = $listing->price_per_week ? (float) $listing->price_per_week : null;
            $this->security_deposit = (float) $listing->security_deposit;
            $this->location = $listing->location;
            $this->latitude = $listing->latitude !== null ? (float) $listing->latitude : null;
            $this->longitude = $listing->longitude !== null ? (float) $listing->longitude : null;
            $this->pickup_available = $listing->pickup_available;
            $this->delivery_available = $listing->delivery_available;
            $this->rental_rules = (string) $listing->rental_rules;
            $this->max_rental_duration_days = $listing->max_rental_duration_days;
            $this->is_available = $listing->is_available;
            $this->available_from = $listing->available_from?->toDateString() ?? '';
            $this->available_until = $listing->available_until?->toDateString() ?? '';
        }
    }

    public function subcategories(): Collection
    {
        if (! $this->category_id) {
            return collect();
        }

        return Category::where('parent_id', $this->category_id)->orderBy('name')->get();
    }

    public function updatedCategoryId(): void
    {
        $this->subcategory_id = null;
    }

    public function marketInsight(): array
    {
        if (! $this->category_id) {
            return ['count' => 0, 'average' => null, 'min' => null, 'max' => null, 'suggested' => null];
        }

        return MarketInsight::forListing($this->category_id, $this->subcategory_id, $this->listing?->id);
    }

    public function isPriceSignificantlyHigh(): bool
    {
        $insight = $this->marketInsight();

        return $this->price_per_day && $insight['average']
            ? MarketInsight::isSignificantlyHigher((float) $this->price_per_day, $insight['average'])
            : false;
    }

    public function removeExistingImage(int $imageId): void
    {
        abort_unless($this->listing?->exists, 404);
        $this->authorize('update', $this->listing);
        abort_unless($this->listing->images()->whereKey($imageId)->exists(), 404);
        $this->removedImageIds = array_values(array_unique([...$this->removedImageIds, $imageId]));
    }

    public function removePendingPhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function existingPhotoCount(): int
    {
        return $this->listing?->images()->whereNotIn('id', $this->removedImageIds)->count() ?? 0;
    }

    public function save(): void
    {
        if ($this->listing?->exists) {
            $this->listing = Listing::findOrFail($this->listing->id);
            $this->authorize('update', $this->listing);
        }

        $validated = $this->validate([
            ...ListingPublication::rules(),
            'photos' => ['array', 'min:'.($this->existingPhotoCount() > 0 ? 0 : 1)],
            'photos.*' => ['required', 'image', 'max:4096'],
        ], ListingPublication::messages());
        unset($validated['photos']);

        if (! $this->pickup_available && ! $this->delivery_available) {
            $this->addError('pickup_available', 'Select at least one option: pickup or delivery.');

            return;
        }

        $validated['owner_id'] = auth()->id();
        $validated['rejection_reason'] = null;

        $isUpdate = (bool) $this->listing?->exists;

        $storedPaths = [];
        $removedPaths = [];
        try {
            DB::transaction(function () use ($validated, $isUpdate, &$storedPaths, &$removedPaths) {
                $listing = $isUpdate ? Listing::lockForUpdate()->findOrFail($this->listing->id) : new Listing;
                if ($isUpdate) {
                    $this->authorize('update', $listing);
                    if ($listing->reservingRequests()
                        ->where(fn ($query) => $query->whereDate('start_date', '<', $this->available_from)
                            ->orWhereDate('end_date', '>', $this->available_until))->exists()) {
                        throw ValidationException::withMessages([
                            'available_until' => 'Your availability window must include existing approved bookings.',
                        ]);
                    }
                }
                if ($listing->images()->whereNotIn('id', $this->removedImageIds)->count() + count($this->photos) < 1) {
                    throw ValidationException::withMessages(['photos' => 'Add at least one photo of your item.']);
                }
                $validated['status'] = $listing->status === ListingStatus::Inactive
                    ? ListingStatus::Inactive : ListingStatus::Published;
                $listing->fill($validated)->save();

                $removedPaths = $listing->images()->whereIn('id', $this->removedImageIds)->pluck('path')->all();
                $listing->images()->whereIn('id', $this->removedImageIds)->delete();
                $sortOrder = ($listing->images()->max('sort_order') ?? -1) + 1;
                foreach ($this->photos as $photo) {
                    $path = $photo->store('listings', 'public');
                    if (! $path) {
                        throw ValidationException::withMessages(['photos' => 'The photo could not be saved. Please try again.']);
                    }
                    $storedPaths[] = $path;
                    ListingImage::create([
                        'listing_id' => $listing->id,
                        'path' => $path,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }
        Storage::disk('public')->delete($removedPaths);

        session()->flash('status', $isUpdate
            ? 'Listing updated.'
            : 'Listing published.');

        $this->redirect(route('owner.listings.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.owner.listings.form', [
            'categories' => Category::whereNull('parent_id')->orderBy('name')->get(),
            'subcategories' => $this->subcategories(),
        ]);
    }
}
