<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $attributes = [
        'status' => 'paid',
    ];

    protected $fillable = [
        'rental_id',
        'transaction_reference',
        'amount',
        'status',
        'paid_at',
        'method',
        'external_reference',
        'payment_submission_id',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public static function generateReference(): string
    {
        return 'LENDLY-'.strtoupper(Str::random(10));
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
