<?php

namespace Database\Seeders;

use App\Enums\ListingCondition;
use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BulkDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Category::count() === 0) {
            $this->call(CategorySeeder::class);
        }

        $members = collect();
        $password = Hash::make('password');

        for ($i = 1; $i <= 100; $i++) {
            $members->push(User::updateOrCreate(
                ['email' => "demo{$i}@tala.test"],
                [
                    'name' => "Demo Member {$i}",
                    'role' => 'member',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'password' => $password,
                ],
            ));
        }

        $categories = Category::whereNotNull('parent_id')->get();
        $conditions = ListingCondition::cases();
        $locations = ['Batangas City', 'Lipa City', 'Tanauan City', 'Malvar', 'Santo Tomas', 'Nasugbu'];

        $existing = Listing::where('name', 'like', 'Demo Item %')->count();
        $target = 250;

        for ($i = $existing + 1; $i <= $target; $i++) {
            $category = $categories[($i - 1) % $categories->count()];
            $owner = $members[($i - 1) % $members->count()];
            $price = 150 + (($i * 73) % 1800);

            Listing::create([
                'owner_id' => $owner->id,
                'category_id' => $category->parent_id,
                'subcategory_id' => $category->id,
                'name' => "Demo Item {$i} - {$category->name}",
                'brand' => ['Tala', 'Lendly', 'DemoTech', 'Batangas Rentals'][$i % 4],
                'description' => "Demo listing {$i} for testing search, browsing, listing details, and rental workflows.",
                'condition' => $conditions[($i - 1) % count($conditions)],
                'estimated_original_price' => $price * 20,
                'price_per_day' => $price,
                'price_per_week' => $price * 5,
                'security_deposit' => $price * 2,
                'location' => $locations[($i - 1) % count($locations)],
                'latitude' => 13.75 + (($i % 30) / 100),
                'longitude' => 121.05 + (($i % 30) / 100),
                'max_rental_duration_days' => 3 + ($i % 12),
                'pickup_available' => true,
                'delivery_available' => $i % 3 === 0,
                'is_available' => true,
                'status' => ListingStatus::Published,
                'views_count' => $i * 7,
            ]);
        }
    }
}
