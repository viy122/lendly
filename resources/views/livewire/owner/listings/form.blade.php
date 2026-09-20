<div>
    <x-page-header
        eyebrow="Owning"
        :title="$listing?->exists ? 'Edit listing' : 'Create a listing'"
        :subtitle="$listing?->exists ? 'Changes will be resubmitted for admin approval.' : 'New listings are reviewed by an admin before they go live.'"
        maxWidth="max-w-4xl"
    />

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($listing?->status?->value === 'rejected' && $listing->rejection_reason)
            <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <p class="font-semibold">This listing was rejected</p>
                <p class="mt-1">{{ $listing->rejection_reason }}</p>
            </div>
        @endif

        <form wire:submit="save" class="space-y-8">
            <!-- Category -->
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="flex items-center gap-1.5 text-sm font-semibold text-slate-700"><x-icon name="folder" class="h-4 w-4 text-blue-500" /> Category</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="category_id" value="Category" />
                        <select wire:model.live="category_id" id="category_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select a category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="subcategory_id" value="Subcategory (optional)" />
                        <select wire:model.live="subcategory_id" id="subcategory_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" @if (! $category_id) disabled @endif>
                            <option value="">None</option>
                            @foreach ($subcategories as $subcategory)
                                <option value="{{ $subcategory->id }}">{{ $subcategory->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('subcategory_id')" class="mt-2" />
                    </div>
                </div>
            </section>

            <!-- Basic info -->
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="flex items-center gap-1.5 text-sm font-semibold text-slate-700"><x-icon name="tag" class="h-4 w-4 text-blue-500" /> Item details</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="name" value="Item name" />
                        <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="brand" value="Brand (optional)" />
                            <x-text-input wire:model="brand" id="brand" type="text" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="model" value="Model (optional)" />
                            <x-text-input wire:model="model" id="model" type="text" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" value="Description" />
                        <textarea wire:model="description" id="description" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="condition" value="Condition" />
                            <select wire:model="condition" id="condition" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach (\App\Enums\ListingCondition::cases() as $option)
                                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="purchase_year" value="Purchase year (optional)" />
                            <x-text-input wire:model="purchase_year" id="purchase_year" type="number" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="estimated_original_price" value="Original price (optional)" />
                            <x-text-input wire:model="estimated_original_price" id="estimated_original_price" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Pricing -->
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="flex items-center gap-1.5 text-sm font-semibold text-slate-700"><x-icon name="archive" class="h-4 w-4 text-blue-500" /> Pricing & deposit</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="price_per_day" value="Price per day (₱)" />
                        <x-text-input wire:model.live.debounce.500ms="price_per_day" id="price_per_day" type="number" step="0.01" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('price_per_day')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="security_deposit" value="Security deposit (₱)" />
                        <x-text-input wire:model="security_deposit" id="security_deposit" type="number" step="0.01" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('security_deposit')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="price_per_hour" value="Price per hour (optional)" />
                        <x-text-input wire:model="price_per_hour" id="price_per_hour" type="number" step="0.01" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('price_per_hour')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="price_per_week" value="Price per week (optional)" />
                        <x-text-input wire:model="price_per_week" id="price_per_week" type="number" step="0.01" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('price_per_week')" class="mt-2" />
                    </div>
                </div>

                @php $insight = $this->marketInsight(); @endphp
                @if ($category_id)
                    <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Market insight</p>
                        @if ($insight['count'] > 0)
                            <div class="mt-2 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                                <div>
                                    <p class="text-slate-400">Similar listings</p>
                                    <p class="font-semibold text-slate-800">{{ $insight['count'] }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-400">Average</p>
                                    <p class="font-semibold text-slate-800">₱{{ number_format($insight['average'], 0) }}/day</p>
                                </div>
                                <div>
                                    <p class="text-slate-400">Price range</p>
                                    <p class="font-semibold text-slate-800">₱{{ number_format($insight['min'], 0) }}&ndash;{{ number_format($insight['max'], 0) }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-400">Suggested price</p>
                                    <p class="font-semibold text-slate-800">₱{{ number_format($insight['suggested'], 0) }}/day</p>
                                </div>
                            </div>
                            <p class="mt-3 text-xs text-slate-400">This is an estimated market insight based on similar listings on the platform. You can still choose your own price.</p>

                            @if ($this->isPriceSignificantlyHigh())
                                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
                                    <p class="font-semibold">Your price is significantly higher than similar listings.</p>
                                    <p class="mt-1">Similar items average ₱{{ number_format($insight['min'], 0) }}&ndash;₱{{ number_format($insight['max'], 0) }}.</p>
                                    <p class="mt-1">Suggested price: ₱{{ number_format($insight['suggested'], 0) }}/day.</p>
                                </div>
                            @endif
                        @else
                            <p class="mt-2 text-sm text-slate-500">No similar published listings yet to compare against.</p>
                        @endif
                    </div>
                @endif
            </section>

            <!-- Location & logistics -->
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="flex items-center gap-1.5 text-sm font-semibold text-slate-700"><x-icon name="map-pin" class="h-4 w-4 text-blue-500" /> Location & logistics</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="location" value="Location" />
                        <x-text-input wire:model="location" id="location" type="text" placeholder="e.g. Nasugbu, Batangas" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('location')" class="mt-2" />
                    </div>

                    <div wire:ignore x-data="locationPicker(@js($latitude), @js($longitude))">
                        <div class="flex items-center justify-between">
                            <x-input-label value="Pin the exact location (optional, powers the map search)" />
                            <button type="button" @click="useMyLocation" class="text-xs font-medium text-blue-600 hover:text-blue-800">Use my location</button>
                        </div>
                        <div x-ref="map" class="mt-2 h-56 w-full rounded-lg border border-slate-200"></div>
                        <p class="mt-1 text-xs text-slate-400">Click on the map to drop a pin, or drag it to adjust.</p>
                    </div>
                    <x-input-error :messages="$errors->get('latitude')" class="mt-2" />

                    <div>
                        <x-input-label value="Fulfillment options" />
                        <div class="mt-2 flex gap-6">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" wire:model="pickup_available" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                Pickup available
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" wire:model="delivery_available" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                Delivery available
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('pickup_available')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="max_rental_duration_days" value="Maximum rental duration (days)" />
                        <x-text-input wire:model="max_rental_duration_days" id="max_rental_duration_days" type="number" class="mt-1 block w-full sm:w-48" />
                        <x-input-error :messages="$errors->get('max_rental_duration_days')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="rental_rules" value="Rental rules (optional)" />
                        <textarea wire:model="rental_rules" id="rental_rules" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. Must be returned clean. No outdoor use in rain."></textarea>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="is_available" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        This item is currently available for rent
                    </label>
                </div>
            </section>

            <!-- Photos -->
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="flex items-center gap-1.5 text-sm font-semibold text-slate-700"><x-icon name="camera" class="h-4 w-4 text-blue-500" /> Photos</h2>

                @if (($this->existingPhotoCount() + count($photos)) < 2)
                    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
                        We recommend adding at least 2 photos — listings with more photos get more rental requests.
                    </div>
                @endif

                @if ($listing?->exists && $listing->images->isNotEmpty())
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ($listing->images as $image)
                            <div class="group relative overflow-hidden rounded-lg border border-slate-200" wire:key="existing-{{ $image->id }}">
                                <img src="{{ $image->url() }}" class="h-24 w-full object-cover">
                                <button type="button" wire:click="removeExistingImage({{ $image->id }})" wire:confirm="Remove this photo?" class="absolute right-1 top-1 rounded-full bg-white/90 px-1.5 text-xs font-bold text-rose-600 shadow">
                                    &times;
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-4">
                    <input type="file" wire:model="photos" multiple accept="image/*" class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100">
                    <div wire:loading wire:target="photos" class="mt-2 text-xs text-slate-400">Uploading...</div>
                    <x-input-error :messages="$errors->get('photos.*')" class="mt-2" />
                </div>

                @if (count($photos) > 0)
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ($photos as $index => $photo)
                            <div class="group relative overflow-hidden rounded-lg border border-slate-200" wire:key="pending-{{ $index }}">
                                <img src="{{ $photo->temporaryUrl() }}" class="h-24 w-full object-cover">
                                <button type="button" wire:click="removePendingPhoto({{ $index }})" class="absolute right-1 top-1 rounded-full bg-white/90 px-1.5 text-xs font-bold text-rose-600 shadow">
                                    &times;
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('owner.listings.index') }}" wire:navigate class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</a>
                <x-primary-button>
                    {{ $listing?->exists ? 'Save & resubmit' : 'Submit for approval' }}
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
