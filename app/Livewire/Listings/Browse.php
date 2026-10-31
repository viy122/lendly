<?php

namespace App\Livewire\Listings;

use App\Enums\ListingCondition;
use App\Models\Category;
use App\Models\Listing;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.marketplace')]
class Browse extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $keyword = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $condition = '';

    #[Url]
    public string $brand = '';

    #[Url]
    public string $location = '';

    #[Url]
    public ?float $minPrice = null;

    #[Url]
    public ?float $maxPrice = null;

    #[Url]
    public string $sort = 'recent';

    #[Url]
    public string $rentalUnit = 'day';

    public function updating($property): void
    {
        if (in_array($property, ['keyword', 'category', 'condition', 'brand', 'location', 'minPrice', 'maxPrice', 'sort', 'rentalUnit'])) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['keyword', 'category', 'condition', 'brand', 'location', 'minPrice', 'maxPrice', 'rentalUnit']);
        $this->sort = 'recent';
        $this->resetPage();
    }

    public function render(): View
    {
        $priceColumn = $this->rentalUnit === 'hour' ? 'price_per_hour' : 'price_per_day';
        $listings = Listing::query()
            ->when($this->rentalUnit === 'hour', fn ($query) => $query->where('price_per_hour', '>', 0), fn ($query) => $query->where('allows_daily_rentals', true))
            ->published()
            ->withAvailability()
            ->acceptingRequests()
            ->with(['category', 'images'])
            ->when($this->keyword, fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->keyword}%")
                ->orWhere('description', 'like', "%{$this->keyword}%")
                ->orWhere('brand', 'like', "%{$this->keyword}%")
            ))
            ->when($this->category, fn ($query) => $query->where(fn ($q) => $q
                ->where('category_id', $this->category)
                ->orWhere('subcategory_id', $this->category)
            ))
            ->when($this->condition, fn ($query) => $query->where('condition', $this->condition))
            ->when($this->brand, fn ($query) => $query->where('brand', $this->brand))
            ->when(trim($this->location) !== '', fn ($query) => $query->where('location', 'like', '%'.trim($this->location).'%'))
            ->when($this->minPrice !== null, fn ($query) => $query->where($priceColumn, '>=', $this->minPrice))
            ->when($this->maxPrice !== null, fn ($query) => $query->where($priceColumn, '<=', $this->maxPrice));

        $listings = match ($this->sort) {
            'cheapest' => $listings->orderBy($priceColumn),
            'popular' => $listings->orderByDesc('views_count'),
            default => $listings->orderByDesc('created_at'),
        };

        return view('livewire.listings.browse', [
            'listings' => $listings->paginate(12),
            'categories' => Category::whereNull('parent_id')->orderBy('name')->get(),
            'conditions' => ListingCondition::cases(),
            'brands' => Listing::published()->whereNotNull('brand')->distinct()->orderBy('brand')->pluck('brand'),
        ]);
    }
}
