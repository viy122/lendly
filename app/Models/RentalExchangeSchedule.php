<?php

namespace App\Models;

use App\Enums\FulfillmentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalExchangeSchedule extends Model
{
    protected $fillable = [
        'rental_id', 'proposed_by', 'fulfillment_method', 'scheduled_at',
        'address', 'notes', 'owner_confirmed_at', 'renter_confirmed_at', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'fulfillment_method' => FulfillmentMethod::class,
            'scheduled_at' => 'immutable_datetime',
            'owner_confirmed_at' => 'immutable_datetime',
            'renter_confirmed_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by')->withTrashed();
    }
}
