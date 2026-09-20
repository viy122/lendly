<?php

namespace App\Models;

use App\Enums\ReviewType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'rental_id',
        'type',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReviewType::class,
            'rating' => 'integer',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
