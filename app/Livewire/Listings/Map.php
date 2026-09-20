<?php

namespace App\Livewire\Listings;

use App\Models\Category;
use App\Models\Listing;
use App\Support\Geo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.marketplace')]
class Map extends Component
{
    public string $keyword = '';

    public string $category = '';

    public ?float $centerLat = null;

    public ?float $centerLng = null;

    public ?int $radiusKm = null;

    /**
     * Map pin data, kept as a public property (not a computed value) so the
     * Alpine map component can reactively watch it via the Livewire wire object.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $markers = [];

    protected function computeMarkers(): array
    {
        $listings = Listing::query()
            ->published()
            ->where('is_available', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('images')
            ->when($this->keyword, fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->keyword}%")
                ->orWhere('description', 'like', "%{$this->keyword}%")
                ->orWhere('brand', 'like', "%{$this->keyword}%")
            ))
            ->when($this->category, fn ($query) => $query->where(fn ($q) => $q
                ->where('category_id', $this->category)
                ->orWhere('subcategory_id', $this->category)
            ))
            ->latest()
            ->get();

        $markers = $listings->map(function (Listing $listing) {
            $distanceKm = ($this->centerLat && $this->centerLng)
                ? Geo::distanceKm($this->centerLat, $this->centerLng, (float) $listing->latitude, (float) $listing->longitude)
                : null;

            return [
                'id' => $listing->id,
                'name' => $listing->name,
                'price' => number_format($listing->price_per_day, 2),
                'image' => $listing->images->first()?->url(),
                'lat' => (float) $listing->latitude,
                'lng' => (float) $listing->longitude,
                'url' => route('listings.show', $listing),
                'distanceKm' => $distanceKm,
                'distance' => $distanceKm !== null ? number_format($distanceKm, 1).' km away' : null,
            ];
        });

        if ($this->radiusKm && $this->centerLat && $this->centerLng) {
            $markers = $markers->filter(fn (array $marker) => $marker['distanceKm'] <= $this->radiusKm);
        }

        if ($this->centerLat && $this->centerLng) {
            $markers = $markers->sortBy('distanceKm');
        }

        return $markers->values()->all();
    }

    public function render(): View
    {
        $this->markers = $this->computeMarkers();

        return view('livewire.listings.map', [
            'categories' => Category::whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }
}
