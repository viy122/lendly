<?php

namespace App\Models;

use App\Enums\RentalAdminActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalAdminAction extends Model
{
    protected $fillable = ['rental_id', 'performed_by', 'action', 'notes', 'before_state', 'after_state'];

    protected function casts(): array
    {
        return [
            'action' => RentalAdminActionType::class,
            'before_state' => 'array',
            'after_state' => 'array',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by')->withTrashed();
    }
}
