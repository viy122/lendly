<?php

namespace App\Models;

use App\Enums\DamageReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DamageReport extends Model
{
    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'rental_id',
        'condition_record_id',
        'damage_type',
        'description',
        'estimated_repair_cost',
        'proposed_deduction',
        'status',
        'renter_response_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => DamageReportStatus::class,
            'estimated_repair_cost' => 'decimal:2',
            'proposed_deduction' => 'decimal:2',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function conditionRecord(): BelongsTo
    {
        return $this->belongsTo(ConditionRecord::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(DamageReportPhoto::class)->orderBy('sort_order');
    }

    public function isPending(): bool
    {
        return $this->status === DamageReportStatus::Pending;
    }
}
