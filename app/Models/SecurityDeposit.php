<?php

namespace App\Models;

use App\Enums\SecurityDepositStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityDeposit extends Model
{
    protected $attributes = [
        'status' => 'held',
        'deducted_amount' => 0,
    ];

    protected $fillable = [
        'rental_id',
        'amount',
        'deducted_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => SecurityDepositStatus::class,
            'amount' => 'decimal:2',
            'deducted_amount' => 'decimal:2',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
