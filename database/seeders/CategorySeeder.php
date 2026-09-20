<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Tools & Equipment' => ['Power Tools', 'Pressure Washers', 'Ladders'],
            'Photography & Video' => ['Cameras', 'Lenses', 'Lighting'],
            'Events & Party' => ['Tents & Canopies', 'Sound Systems', 'Decor'],
            'Outdoor & Sports' => ['Camping Gear', 'Bicycles', 'Water Sports'],
            'Vehicles' => ['Cars', 'Motorcycles'],
            'Electronics' => ['Projectors', 'Drones'],
            'Home Appliances' => ['Cleaning Equipment', 'Kitchen Appliances'],
        ];

        foreach ($tree as $name => $subcategories) {
            $category = Category::create(['name' => $name, 'slug' => Str::slug($name)]);

            foreach ($subcategories as $subcategoryName) {
                Category::create([
                    'name' => $subcategoryName,
                    'slug' => Str::slug($subcategoryName),
                    'parent_id' => $category->id,
                ]);
            }
        }
    }
}
