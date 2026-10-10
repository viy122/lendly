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
    public ?float $minPrice = null;

    #[Url]
    public ?float $maxPrice = null;

    #[Url]
    public string $sort = 'recent';

    public function updating($property): void
    {
        if (in_array($property, ['keyword', 'category', 'condition', 'brand', 'minPrice', 'maxPrice', 'sort'])) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['keyword', 'category', 'condition', 'brand', 'minPrice', 'maxPrice']);
        $this->sort = 'recent';
        $this->resetPage();
    }

    public function render(): View
    {
        $listings = Listing::query()
            ->published()
            ->where('is_available', true)
            ->with(['category', 'images', 'rentals' => fn ($query) => $query->whereIn('status', ['paid', 'active', 'overdue'])])
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
            ->when($this->minPrice, fn ($query) => $query->where('price_per_day', '>=', $this->minPrice))
            ->when($this->maxPrice, fn ($query) => $query->where('price_per_day', '<=', $this->maxPrice));

        $listings = match ($this->sort) {
            'cheapest' => $listings->orderBy('price_per_day'),
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
