<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    protected $fillable = [
        'commission_rate',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
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
