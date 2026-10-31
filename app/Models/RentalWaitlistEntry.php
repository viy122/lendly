<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalWaitlistEntry extends Model
{
    protected $fillable = ['listing_id', 'user_id', 'details', 'notified_at'];

    protected function casts(): array
    {
        return ['details' => 'array', 'notified_at' => 'datetime'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class)->withTrashed();
    }
}
