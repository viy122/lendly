<?php

namespace App\Models;

use App\Enums\DisputeReason;
use App\Enums\DisputeResolution;
use App\Enums\DisputeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    protected $attributes = [
        'status' => 'open',
    ];

    protected $fillable = [
        'rental_id',
        'damage_report_id',
        'raised_by',
        'reason',
        'description',
        'status',
        'resolution',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'reason' => DisputeReason::class,
            'status' => DisputeStatus::class,
            'resolution' => DisputeResolution::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function damageReport(): BelongsTo
    {
        return $this->belongsTo(DamageReport::class);
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by')->withTrashed();
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by')->withTrashed();
    }

    public function isOpen(): bool
    {
        return $this->status === DisputeStatus::Open;
    }
}
