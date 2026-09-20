<?php

namespace App\Livewire\Owner\Listings;

use App\Enums\ListingCondition;
use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Services\MarketInsight;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
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
            $this->latitude = $listing->latitude ? (float) $listing->latitude : null;
            $this->longitude = $listing->longitude ? (float) $listing->longitude : null;
            $this->pickup_available = $listing->pickup_available;
            $this->delivery_available = $listing->delivery_available;
            $this->rental_rules = (string) $listing->rental_rules;
            $this->max_rental_duration_days = $listing->max_rental_duration_days;
            $this->is_available = $listing->is_available;
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
        $image = ListingImage::where('listing_id', $this->listing?->id)->findOrFail($imageId);

        Storage::disk('public')->delete($image->path);
        $image->delete();
    }

    public function removePendingPhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function existingPhotoCount(): int
    {
        return $this->listing?->images()->count() ?? 0;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'condition' => ['required', Rule::enum(ListingCondition::class)],
            'purchase_year' => ['nullable', 'integer', 'min:1970', 'max:'.date('Y')],
            'estimated_original_price' => ['nullable', 'numeric', 'min:0'],
            'price_per_day' => ['required', 'numeric', 'gt:0'],
            'price_per_hour' => ['nullable', 'numeric', 'gt:0'],
            'price_per_week' => ['nullable', 'numeric', 'gt:0'],
            'security_deposit' => ['required', 'numeric', 'min:0'],
            'location' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'pickup_available' => ['boolean'],
            'delivery_available' => ['boolean'],
            'rental_rules' => ['nullable', 'string', 'max:2000'],
            'max_rental_duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'is_available' => ['boolean'],
            'photos.*' => ['nullable', 'image', 'max:4096'],
        ], [
            'price_per_day.gt' => 'Rental price must be greater than ₱0.',
            'category_id.required' => 'Category is required.',
            'location.required' => 'Location is required.',
        ]);

        if (! $this->pickup_available && ! $this->delivery_available) {
            $this->addError('pickup_available', 'Select at least one option: pickup or delivery.');

            return;
        }

        $validated['owner_id'] = auth()->id();
        $validated['status'] = ListingStatus::PendingApproval;
        $validated['rejection_reason'] = null;

        $isUpdate = (bool) $this->listing?->exists;

        if ($isUpdate) {
            $this->listing->update($validated);
            $listing = $this->listing;
        } else {
            $listing = Listing::create($validated);
        }

        foreach ($this->photos as $photo) {
            $path = $photo->store('listings', 'public');

            ListingImage::create([
                'listing_id' => $listing->id,
                'path' => $path,
                'sort_order' => $listing->images()->count(),
            ]);
        }

        session()->flash('status', $isUpdate
            ? 'Listing updated and resubmitted for approval.'
            : 'Listing submitted for approval.');

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
