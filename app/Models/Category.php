<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'category_id');
    }

    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Cosmetic icon + color pairing per top-level category, used wherever a
     * category needs a visual identity (browse chips, badges, cards). Keyed
     * on name rather than a stored column since this is presentation-only
     * and the category tree is small/seeded, not user-editable.
     */
    public function style(): array
    {
        $styles = [
            'Tools & Equipment' => ['icon' => 'wrench', 'accent' => 'amber'],
            'Photography & Video' => ['icon' => 'camera', 'accent' => 'violet'],
            'Events & Party' => ['icon' => 'sparkles', 'accent' => 'fuchsia'],
            'Outdoor & Sports' => ['icon' => 'mountain', 'accent' => 'emerald'],
            'Vehicles' => ['icon' => 'car', 'accent' => 'rose'],
            'Electronics' => ['icon' => 'cpu', 'accent' => 'cyan'],
            'Home Appliances' => ['icon' => 'home', 'accent' => 'teal'],
        ];

        return $styles[$this->name] ?? ['icon' => 'tag', 'accent' => 'slate'];
    }
}
