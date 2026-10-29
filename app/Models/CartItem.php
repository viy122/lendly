<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['user_id', 'listing_id', 'start_date', 'end_date', 'fulfillment_method', 'terms', 'rental_unit', 'rental_hours', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'rental_hours' => 'integer', 'start_date' => 'date', 'end_date' => 'date', 'terms' => 'array'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class)->withTrashed();
    }

    public function rentalDays(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function rentalFee(): float
    {
        return $this->rental_unit === 'hour'
            ? round((float) $this->terms['price_per_hour'] * $this->rental_hours, 2)
            : round((float) $this->terms['price_per_day'] * $this->rentalDays(), 2);
    }

    public function commissionAmount(): float
    {
        return round($this->rentalFee() * (float) $this->terms['commission_rate'] / 100, 2);
    }

    public function totalAmount(): float
    {
        return round($this->rentalFee() + $this->commissionAmount() + (float) $this->terms['security_deposit'], 2);
    }
}
