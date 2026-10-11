<?php

namespace App\Services;

use App\Enums\ReviewType;
use App\Models\Rental;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RentalReviews
{
    public static function submit(Rental $rental, User $reviewer, ReviewType $type, int $rating, ?string $comment): Review
    {
        return DB::transaction(function () use ($rental, $reviewer, $type, $rating, $comment) {
            // Serialize submissions from multiple open tabs and recheck saved completion.
            $rental = Rental::lockForUpdate()->findOrFail($rental->id);
            Gate::forUser($reviewer)->authorize('review', [$rental, $type]);

            validator(['rating' => $rating, 'comment' => $comment], [
                'rating' => ['required', 'integer', 'min:1', 'max:5'],
                'comment' => ['nullable', 'string', 'max:1000'],
            ])->validate();

            return Review::create([
                'rental_id' => $rental->id,
                'type' => $type,
                'rating' => $rating,
                'comment' => $comment,
            ]);
        });
    }
}
