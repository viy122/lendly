<div class="listing-form-page bg-[radial-gradient(circle_at_94%_8%,rgba(191,219,254,0.32),transparent_28%),#f8fafc]">
    <x-page-header
        eyebrow="Owning"
        :title="$listing?->exists ? 'Edit listing' : 'Create a listing'"
        :subtitle="$listing?->exists ? 'Save your changes after checking the required details.' : 'Complete the required details to publish your item immediately.'"
    />

    <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
        <div class="listing-form-content min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-6 sm:px-6 lg:px-8" role="region" aria-label="Listing details" tabindex="0">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('owner.listings.index') }}" wire:navigate class="inline-flex min-h-10 items-center gap-2 rounded-lg text-sm font-semibold text-slate-600 transition hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                    <span aria-hidden="true">←</span> Back to my listings
                </a>
                <p class="text-xs text-slate-500">Optional fields are marked.</p>
            </div>
            @if ($listing?->status?->value === 'rejected' && $listing->rejection_reason)
                <div class="mb-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 shadow-sm">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-600">!</span>
                    <div>
                        <p class="font-bold">This listing was rejected</p>
                        <p class="mt-1 leading-6">{{ $listing->rejection_reason }}</p>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                    <p class="font-semibold">Please check the highlighted fields before submitting.</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="listing-form-grid">
                <div class="min-w-0 space-y-6">
                    <!-- Category -->
                    <section class="listing-form-card rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100"><x-icon name="folder" class="h-5 w-5" /></span>
                            <div><h2 class="text-base font-bold text-[#071a3d]">Category</h2><p class="mt-0.5 text-xs leading-5 text-slate-500">Help renters find your item quickly.</p></div>
                        </div>
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
                    <section class="listing-form-card rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-100"><x-icon name="tag" class="h-5 w-5" /></span>
                            <div><h2 class="text-base font-bold text-[#071a3d]">Item details</h2><p class="mt-0.5 text-xs leading-5 text-slate-500">Describe what makes your item useful.</p></div>
                        </div>
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

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                                    <x-input-label for="estimated_original_price" value="Original price (₱, optional)" />
                                    <x-text-input wire:model="estimated_original_price" id="estimated_original_price" type="number" step="0.01" class="mt-1 block w-full" />
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Location & logistics -->
                    <section class="listing-form-card rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100"><x-icon name="map-pin" class="h-5 w-5" /></span>
                            <div><h2 class="text-base font-bold text-[#071a3d]">Location & logistics</h2><p class="mt-0.5 text-xs leading-5 text-slate-500">Choose where and how renters receive it.</p></div>
                        </div>
                        <div class="mt-4 space-y-4">
                            <div>
                                <x-input-label for="location" value="Location" />
                                <x-text-input wire:model="location" id="location" type="text" placeholder="e.g. Nasugbu, Batangas" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('location')" class="mt-2" />
                            </div>

                            <div wire:ignore x-data="locationPicker(@js($latitude), @js($longitude))">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <x-input-label value="Map pin (required)" />
                                    <button type="button" @click="useMyLocation" class="inline-flex min-h-10 items-center rounded-lg px-2 text-xs font-semibold text-blue-600 hover:bg-blue-50 hover:text-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-100">Use my location</button>
                                </div>
                                <div x-ref="map" class="relative isolate z-0 mt-2 h-64 w-full overflow-hidden rounded-2xl border border-slate-200 shadow-sm"></div>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Click the map to add a pin, or drag it to adjust. This helps renters find your item.</p>
                            </div>
                            <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
                            <x-input-error :messages="$errors->get('longitude')" class="mt-2" />

                            <div>
                                <x-input-label value="Fulfillment options" />
                                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:bg-blue-50">
                                        <input type="checkbox" wire:model="pickup_available" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                        Pickup available
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:bg-blue-50">
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
                                <p class="text-sm font-medium text-slate-700">Available dates</p>
                                <p class="mt-1 text-xs text-slate-500">Renters must start and finish their rental within this window.</p>
                                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="available_from" value="Available from" />
                                        <x-text-input wire:model="available_from" id="available_from" type="date" required class="mt-1 block w-full" />
                                        <x-input-error :messages="$errors->get('available_from')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="available_until" value="Available until" />
                                        <x-text-input wire:model="available_until" id="available_until" type="date" required min="{{ now()->toDateString() }}" class="mt-1 block w-full" />
                                        <x-input-error :messages="$errors->get('available_until')" class="mt-2" />
                                    </div>
                                </div>
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
                </div>
                <div class="min-w-0 space-y-6">
                    <!-- Pricing -->
                    <section class="listing-form-card rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100"><span class="text-base font-extrabold">₱</span></span>
                            <div><h2 class="text-base font-bold text-[#071a3d]">Pricing & deposit</h2><p class="mt-0.5 text-xs leading-5 text-slate-500">Set fair rates and protect your item.</p></div>
                        </div>
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
                            <div class="mt-5 rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50/90 to-sky-50/70 p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-blue-600">Market insight</p>
                                @if ($insight['count'] > 0)
                                    <div class="mt-3 grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <p class="text-xs leading-5 text-slate-500">Similar listings</p>
                                            <p class="font-semibold text-slate-800">{{ $insight['count'] }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs leading-5 text-slate-500">Average</p>
                                            <p class="font-semibold text-slate-800">₱{{ number_format($insight['average'], 0) }}/day</p>
                                        </div>
                                        <div>
                                            <p class="text-xs leading-5 text-slate-500">Price range</p>
                                            <p class="font-semibold text-slate-800">₱{{ number_format($insight['min'], 0) }}&ndash;{{ number_format($insight['max'], 0) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs leading-5 text-slate-500">Suggested price</p>
                                            <p class="font-semibold text-slate-800">₱{{ number_format($insight['suggested'], 0) }}/day</p>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-xs leading-5 text-slate-500">This is an estimated market insight based on similar listings on the platform. You can still choose your own price.</p>

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

                    <!-- Photos -->
                    <section class="listing-form-card rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-600 ring-1 ring-violet-100"><x-icon name="camera" class="h-5 w-5" /></span>
                            <div><h2 class="text-base font-bold text-[#071a3d]">Photos</h2><p class="mt-0.5 text-xs leading-5 text-slate-500">Show clear angles and important details.</p></div>
                        </div>

                        @if (($this->existingPhotoCount() + count($photos)) < 2)
                            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
                                We recommend adding at least 2 photos — listings with more photos get more rental requests.
                            </div>
                        @endif

                        @if ($listing?->exists && $listing->images->isNotEmpty())
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                @foreach ($listing->images->whereNotIn('id', $removedImageIds) as $image)
                                    <div class="group relative overflow-hidden rounded-lg border border-slate-200" wire:key="existing-{{ $image->id }}">
                                        <img src="{{ $image->url() }}" alt="{{ $listing->name }} photo {{ $loop->iteration }}" class="aspect-[4/3] w-full object-cover">
                                        <button type="button" wire:click="removeExistingImage({{ $image->id }})" wire:confirm="Remove this photo?" aria-label="Remove photo {{ $loop->iteration }}" class="absolute right-2 top-2 grid h-9 w-9 place-items-center rounded-full bg-white text-lg font-semibold text-rose-600 shadow transition hover:bg-rose-50 focus:outline-none focus:ring-4 focus:ring-rose-100">
                                            &times;
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-4 rounded-2xl border-2 border-dashed border-blue-200 bg-blue-50/50 p-4 transition hover:border-blue-300 hover:bg-blue-50">
                            <label for="listing-photos" class="mb-2 block text-sm font-semibold text-slate-700">Add photos</label>
                            <p id="listing-photos-help" class="mb-3 text-xs leading-5 text-slate-500">At least one photo is required. Choose clear images, up to 4 MB each. You can select multiple photos.</p>
                            <input id="listing-photos" aria-describedby="listing-photos-help" type="file" wire:model="photos" multiple accept="image/*" class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-blue-600 file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-white hover:file:bg-blue-700">
                            <div wire:loading wire:target="photos" class="mt-2 text-xs leading-5 text-slate-500">Uploading...</div>
                            <x-input-error :messages="collect($errors->get('photos.*'))->flatten()->all()" class="mt-2" />
                            <x-input-error :messages="$errors->get('photos')" class="mt-2" />
                        </div>

                        @if (count($photos) > 0)
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                @foreach ($photos as $index => $photo)
                                    <div class="group relative overflow-hidden rounded-lg border border-slate-200" wire:key="pending-{{ $index }}">
                                        @if ($photo->isPreviewable())
                                            <img src="{{ $photo->temporaryUrl() }}" alt="New listing photo {{ $loop->iteration }}" class="aspect-[4/3] w-full object-cover">
                                        @else
                                            <p class="p-4 text-sm text-rose-700">Choose an image file for this photo.</p>
                                        @endif
                                        <button type="button" wire:click="removePendingPhoto({{ $index }})" aria-label="Remove photo {{ $loop->iteration }}" class="absolute right-2 top-2 grid h-9 w-9 place-items-center rounded-full bg-white text-lg font-semibold text-rose-600 shadow transition hover:bg-rose-50 focus:outline-none focus:ring-4 focus:ring-rose-100">
                                            &times;
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>

        <footer class="listing-form-actions sticky bottom-4 z-10 mx-4 mt-3 flex shrink-0 flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white/95 px-4 py-4 backdrop-blur-xl sm:mx-6 sm:flex-row sm:items-center sm:justify-between sm:px-5 lg:mx-8 lg:px-6">
            <div class="hidden sm:block">
                <p class="text-sm font-semibold text-slate-800">Ready to publish?</p>
                <p class="mt-1 text-xs text-slate-500">Valid listings go live immediately. Paused listings stay paused when edited.</p>
            </div>
            <div class="flex w-full items-center gap-3 sm:w-auto">
                <a href="{{ route('owner.listings.index') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 sm:px-4">Cancel</a>
                <button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-[#075cf5] px-4 py-2.5 text-sm font-bold text-white shadow-[0_9px_24px_rgba(7,92,245,0.26)] transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100 disabled:cursor-wait disabled:opacity-60 sm:px-5">
                    <span wire:loading.remove wire:target="save">{{ $listing?->exists ? 'Save changes' : 'Publish listing' }}</span>
                    <span wire:loading wire:target="save">Saving…</span>
                    <span wire:loading.remove wire:target="save" aria-hidden="true" class="hidden sm:inline">→</span>
                </button>
            </div>
        </footer>
    </form>
</div>
