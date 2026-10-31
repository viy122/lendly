<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    protected $fillable = [
        'commission_rate', 'cancellation_window_hours', 'cancellation_fee_percentage', 'payment_window_hours',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2', 'cancellation_fee_percentage' => 'decimal:2',
            'cancellation_window_hours' => 'integer', 'payment_window_hours' => 'integer',
        ];
    }

    /**
     * The single active commission setting row (created with the default
     * rate the first time it's needed). Admin-editable UI arrives in Phase 5.
     */
    public static function current(): self
    {
        return self::query()->firstOrCreate([], ['commission_rate' => 10.00]);
    }
}
