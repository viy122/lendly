<?php

namespace App\Services;

use App\Enums\ListingCondition;
use App\Models\Listing;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ListingPublication
{
    public static function rules(): array
    {
        return [
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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'available_from' => ['required', 'date_format:Y-m-d'],
            'available_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:available_from', 'after_or_equal:today'],
            'pickup_available' => ['boolean'],
            'delivery_available' => ['boolean'],
            'rental_rules' => ['nullable', 'string', 'max:2000'],
            'max_rental_duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'is_available' => ['boolean'],
        ];
    }

    public static function messages(): array
    {
        return [
            'price_per_day.gt' => 'Rental price must be greater than ₱0.',
            'category_id.required' => 'Category is required.',
            'location.required' => 'Location is required.',
            'latitude.required' => 'Place a pin on the map for your item.',
            'longitude.required' => 'Place a pin on the map for your item.',
            'photos.min' => 'Add at least one photo of your item.',
            'available_from.required' => 'Choose the first available date.',
            'available_until.required' => 'Choose the last available date.',
            'available_until.after_or_equal' => 'The last available date must be today or later and on or after the first available date.',
        ];
    }

    public static function validate(Listing $listing): void
    {
        $data = $listing->getAttributes();
        $data['available_from'] = $listing->available_from?->toDateString();
        $data['available_until'] = $listing->available_until?->toDateString();
        $data['photos'] = $listing->images()->pluck('id')->all();

        $validator = Validator::make($data, [
            ...self::rules(),
            'photos' => ['array', 'min:1'],
        ], self::messages());

        $validator->after(function ($validator) use ($listing) {
            if (! $listing->pickup_available && ! $listing->delivery_available) {
                $validator->errors()->add('pickup_available', 'Select at least one option: pickup or delivery.');
            }
        });

        $validator->validate();
    }
}
