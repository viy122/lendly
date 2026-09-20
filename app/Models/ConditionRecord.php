<?php

namespace App\Models;

use App\Enums\ConditionRecordType;
use App\Enums\ListingCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConditionRecord extends Model
{
    protected $fillable = [
        'rental_id',
        'recorded_by',
        'type',
        'condition',
        'notes',
        'has_damage',
    ];

    protected function casts(): array
    {
        return [
            'type' => ConditionRecordType::class,
            'condition' => ListingCondition::class,
            'has_damage' => 'boolean',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ConditionRecordPhoto::class)->orderBy('sort_order');
    }
}
