<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

class ListingCategories
{
    public static function resolve(string $name, ?int $parentId = null): Category
    {
        $name = Str::squish($name);
        $normalized = mb_strtolower($name);
        $existing = Category::where('parent_id', $parentId)->get()
            ->first(fn (Category $category) => mb_strtolower(Str::squish($category->name)) === $normalized);

        // A deterministic, globally unique slug also protects concurrent submissions.
        return $existing ?? Category::firstOrCreate(
            ['slug' => 'custom-'.substr(hash('sha256', ($parentId ?? 'root').':'.$normalized), 0, 32)],
            ['parent_id' => $parentId, 'name' => $name],
        );
    }
}
